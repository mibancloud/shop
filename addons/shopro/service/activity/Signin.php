<?php

namespace addons\shopro\service\activity;

use think\Db;
use app\admin\model\shopro\activity\Signin as SigninModel;
use addons\shopro\service\Wallet as WalletService;
use addons\shopro\facade\Activity as ActivityFacade;


class Signin
{

    protected $user = null;

    protected $activity = null;

    protected $rules = [];

    public function __construct()
    {
        $this->user = auth_user();

        $this->activity = $this->getSigninActivity();
        $rules = $this->activity['rules'];
        $this->rules = [
            'everyday' => $rules['everyday'] ?? 0,
            'is_inc' => $rules['is_inc'] ?? 0,
            'inc_num' => $rules['inc_num'] ?? 0,
            'until_day' => $rules['until_day'] ?? 0,
            'discounts' => $rules['discounts'] ?? [],
            'is_replenish' => $rules['is_replenish'] ?? 1,
            'replenish_days' => $rules['replenish_days'] ?? 1,
            'replenish_limit' => $rules['replenish_limit'] ?? 0,
            'replenish_num' => $rules['replenish_num'] ?? 1
        ];
    }

    
    public function getRules() 
    {
        return $this->rules;
    }

    /**
     * 获取签到日历
     *
     * @param string $month
     * @return array
     */
    public function getList($month)
    {
        $signins = SigninModel::where('user_id', $this->user->id)
            ->where('activity_id', $this->activity['id'])
            ->where('date', 'like', $month . '%')
            ->order('date', 'asc')
            ->select();

        $signin_dates = array_column($signins, 'date');

        $today = date('Y-m-d');

        // 要查询的是否是当前月
        $is_current = ($month == date('Y-m')) ? true : false;
        // 所选月开始时间戳
        $month_start_time = strtotime($month);
        // 所选月总天数
        $month_days = date('t', $month_start_time);

        $days = [];
        for ($i = 1; $i <= $month_days; $i++) {
            $for_time = $month_start_time + (($i - 1) * 86400);
            $for_date = date('Y-m-d', $for_time);

            // 如果不是当前月，全是 before, 如果是当前月判断 日期是当前日期的 前面，还是后面
            $current = !$is_current ? ($month > date('Y-m') ? 'after' : 'before') : ($for_date == $today ? 'today' : ($for_date < $today ? 'before' : 'after'));
            $is_signin = in_array($for_date, $signin_dates);        // 是否签到，判断循环的日期，是否在查询的签到记录里面

            $days[] = [
                'is_sign' => $is_signin ? 1 : 0,
                'is_replenish' => ($is_signin || !$this->rules['is_replenish']) ? 0 : ($this->isReplenish($for_date, false) ? 1 : 0),
                'date' => $for_date,
                'time' => $for_time,
                'day' => $i,
                'week' => date('w', $for_time),
                'current' => $current,
            ];
        }

        return $days;
    }


    /**
     * 获取连续签到天数
     *
     * @param boolean $is_today    是否包含今天
     * @return integer
     */
    public function getContinueDays($is_today = true)
    {
        $totime = time();
        $continue_days = 0;     // 连续签到天数    
        $chunk = 0;             // 第几次 chunk;
        $chunk_num = 20;        // 每次查 10 条
        SigninModel::where('user_id', $this->user->id)
            ->where('activity_id', $this->activity['id'])
            ->where('date', '<>', date('Y-m-d'))        // 这里不查今天，今天另算
            ->chunk($chunk_num, function ($signins) use ($totime, &$continue_days, &$chunk, $chunk_num) {
                foreach ($signins as $key => $signin) {
                    $pre_time = $totime - (86400 * (($key + 1) + ($chunk * $chunk_num)));
                    $pre_date = date('Y-m-d', $pre_time);
                    if ($signin->date == $pre_date) {
                        $continue_days++;
                    } else {
                        return false;
                    }
                }
                $chunk++;
            }, 'date', 'desc');     // 如果 date 重复，有坑 (date < 2020-03-28)

        if ($is_today) {
            $todaySign = SigninModel::where('user_id', $this->user->id)
                ->where('activity_id', $this->activity['id'])
                ->where('date', date('Y-m-d'))->find();

            if ($todaySign) {
                $continue_days++;
            }
        }

        return $continue_days;
    }



    /**
     * 签到
     *
     * @return \think\Model
     */
    public function signin()
    {
        $signin = Db::transaction(function () {
            // 当前时间戳，避免程序执行中间，刚好跨天
            $totime = time();

            $signin = SigninModel::where('user_id', $this->user->id)
                ->where('activity_id', $this->activity['id'])
                ->where('date', date('Y-m-d', $totime))
                ->lock(true)->find();
            if ($signin) {
                error_stop('您今天已经签到，明天再来吧');
            }

            $fullDays = array_column($this->rules['discounts'], null, 'full');
            $score = $this->rules['everyday'];      // 每日积分基数

            // 获取连续签到天数
            $continue_days = $this->getContinueDays(false);          // 这里查询历史的连续签到天数
            $continue_days++;          // 算上今天（默认签到）

            if ($this->rules['is_inc']) {
                // 连续签到天数超出最大连续天数，按照最大连续天数计算
                $continue_effec_days = (($continue_days - 1) > $this->rules['until_day']) ? $this->rules['until_day'] : ($continue_days - 1);

                // 计算今天应得积分  连续签到两天，第二天所得积分为 $everyday + ((2 - 1) * $inc_value)
                $until_add = $continue_effec_days * $this->rules['inc_num'];       // 连续签到累加必须大于 0 ，小于 0 舍弃
                if ($until_add > 0) {    // 避免 until_day 填写小于 等于 0 
                    $score += $until_add;
                }
            }

            if (isset($fullDays[$continue_days])) {
                // 今天是连续奖励天数，加上连续签到奖励
                $discount = $fullDays[$continue_days];
                if (isset($discount['value']) && $discount['value'] > 0) {
                    $score += $discount['value'];
                }
            }

            // 插入签到记录
            $signin = SigninModel::create([
                'user_id' => $this->user->id,
                'activity_id' => $this->activity['id'],
                'date' => date('Y-m-d', $totime),
                'score' => $score >= 0 ? $score : 0,
                'is_replenish' => 0,
                'rules' => $this->rules
            ]);

            // 赠送积分
            if ($score > 0) {
                WalletService::change($this->user, 'score', $score, 'signin', [
                    'date' => date('Y-m-d', $totime)
                ]);
            }

            return $signin;
        });

        return $signin;
    }



    /**
     * 补签
     *
     * @param array $params
     * @return \think\Model
     */
    public function replenish($params)
    {
        $signin = Db::transaction(function () use ($params) {
            if (!$this->rules['is_replenish']) {
                error_stop('当前签到活动不允许补签');
            }

            $date = $params['date'];
            $signin = SigninModel::where('user_id', $this->user->id)
                ->where('activity_id', $this->activity['id'])
                ->where('date', $date)
                ->lock(true)->find();
            if ($signin) {
                error_stop('您当天已经签到过了，不需要补签');
            }

            $this->isReplenish($date);

            // 补签
            $signin = SigninModel::create([
                'user_id' => $this->user->id,
                'activity_id' => $this->activity['id'],
                'date' => $params['date'],
                'score' => -$this->rules['replenish_num'],
                'is_replenish' => 1,
                'rules' => $this->rules
            ]);

            // 扣除补签积分
            if ($this->rules['replenish_num'] > 0) {
                WalletService::change($this->user, 'score', -$this->rules['replenish_num'], 'replenish_signin', [
                    'date' => $params['date']
                ]);
            }

            return $signin;
        });

        return $signin;
    }



    /**
     * 判断日期是否可以补签
     *
     * @param string $date
     * @param boolean $is_throw
     * @return boolean
     */
    private function isReplenish($date, $is_throw = true)
    {
        $today = date('Y-m-d');
        $today_unix = strtotime($today);
        $replenish_unix = strtotime($date);
        $interval_days = ($today_unix - $replenish_unix) / 86400;
        if ($interval_days <= 0) {
            return $this->exception('只能补签今天之前的日期', $is_throw);
        }
        if ($this->rules['replenish_limit'] && $interval_days > $this->rules['replenish_limit']) {
            return $this->exception('已经超出最大可补签日期', $is_throw);
        }

        // 实际签到的天数
        $real_days = SigninModel::where('user_id', $this->user->id)
            ->where('activity_id', $this->activity['id'])
            ->where('date', '>', $date)
            ->where('date', '<', $today)
            ->order('date', 'desc')->count();

        // 已补签的天数
        $replenish_days = SigninModel::where('user_id', $this->user->id)
            ->where('activity_id', $this->activity['id'])
            ->where('date', '>', $date)
            ->where('date', '<', $today)
            ->where('is_replenish', 1)
            ->order('date', 'desc')->count();
        $need_days = $interval_days - 1;       // 如果时间间隔中没有断签，应该的签到天数
        if (($need_days - $real_days) >= ($this->rules['replenish_days'] - $replenish_days)) {
            return $this->exception('最多可补签最近' . $this->rules['replenish_days'] . '天，当前所选日期不可补签', $is_throw);
        }

        return true;
    }


    /**
     * 抛出异常or false
     *
     * @param string $msg
     * @param boolean $is_throw
     * @return mixed
     */
    public function exception($msg, $is_throw = true)
    {
        if ($is_throw) {
            error_stop($msg);
        } else {
            return false;
        }
    }


    /**
     * 获取签到活动
     *
     * @return array
     */
    private function getSigninActivity()
    {
        $activities = ActivityFacade::getActivities(['signin'], ['ing']);
        $activity = $activities[0] ?? null;

        if (!$activity) {
            error_stop('签到活动还未开始');
        }

        return $activity;
    }
}
