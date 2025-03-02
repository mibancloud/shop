<?php

namespace app\admin\model\shopro\activity;

use app\admin\model\shopro\Common;
use traits\model\SoftDelete;
use app\admin\model\shopro\goods\Goods;
use addons\shopro\facade\Activity as ActivityFacade;
use app\admin\model\shopro\activity\SkuPrice as ActivitySkuPriceModel;

class Activity extends Common
{
    use SoftDelete;

    protected $name = 'shopro_activity';

    protected $deleteTime = 'deletetime';

    protected $type = [
        'rules' => 'json',
        'prehead_time' => 'timestamp',
        'start_time' => 'timestamp',
        'end_time' => 'timestamp',
    ];

    // 追加属性
    protected $append = [
        'status',
        'status_text',
        'type_text',
        // 'end_time_unix'     // 不需要了
    ];


    public function classifies()
    {
        return [
            'activity' => [
                'groupon' => '拼团',
                'groupon_ladder' => '阶梯拼团',
                // 'groupon_lucky' => '幸运拼团',
                'seckill' => '秒杀',
            ],
            'promo' => [
                'full_reduce' => '满减',
                'full_discount' => '满折',
                'full_gift' => '满赠',
                'free_shipping' => '满邮',
            ],
            'app' => [
                'signin' => '签到'
            ]
        ];
    }

    public function typeList()
    {
        return [
            'groupon' => '拼团',
            'groupon_ladder' => '阶梯拼团',
            // 'groupon_lucky' => '幸运拼团',
            'seckill' => '秒杀',
            'full_reduce' => '满减',
            'full_discount' => '满折',
            'full_gift' => '满赠',
            'free_shipping' => '满邮',
            'signin' => '签到',
        ];
    }


    /**
     * 获取活动的互斥活动
     *
     * @param string $current_activity_type
     * @return array
     */
    public function getMutexActivityTypes($current_activity_type)
    {
        $activityTypes = [];
        switch ($current_activity_type) {
            case 'groupon':
                $activityTypes = ['groupon'];
                break;
            case 'groupon_ladder':
                $activityTypes = ['groupon_ladder'];
                break;
            case 'groupon_lucky':
                $activityTypes = ['groupon_lucky'];
                break;
            case 'seckill':
                $activityTypes = ['seckill'];
                break;
            case 'full_reduce':
                $activityTypes = ['full_reduce', 'full_discount'];
                break;
            case 'full_discount':
                $activityTypes = ['full_reduce', 'full_discount'];
                break;
            case 'free_shipping':
                $activityTypes = ['free_shipping'];
                break;
            case 'full_gift':
                $activityTypes = ['full_gift'];
                break;
            case 'signin':
                $activityTypes = ['signin'];
                break;
        }

        return $activityTypes;
    }


    /**
     * 根据类型获取 classify
     *
     * @param string $type
     * @return string
     */
    public function getClassify($type)
    {
        $classifys = $this->classifies();
        $activitys = array_keys($classifys['activity']);
        $promos = array_keys($classifys['promo']);
        $apps = array_keys($classifys['app']);

        $classify = null;
        if (in_array($type, $activitys)) {
            $classify = 'activity';
        } else if (in_array($type, $promos)) {
            $classify = 'promo';
        } else if (in_array($type, $apps)) {
            $classify = 'app';
        }

        return $classify;
    }



    /**
     * status 组合 (在thinkphp5 where Closure 中，不能直接使用 scope，特殊场景下用来代替下面的 scopeNostart scopePrehead 等)
     *
     * @param [type] $query
     * @param [type] $status
     * @return void
     */
    public function scopeStatusComb($query, $status)
    {
        return $query->where(function ($query) use ($status) {
            foreach ($status as $st) {
                $query->whereOr(function ($query) use ($st) {
                    switch($st) {
                        case 'nostart':
                            $query->where('start_time', '>', time());
                            break;
                        case 'prehead':
                            $query->where('prehead_time', '<=', time())->where('start_time', '>', time());
                            break;
                        case 'ing':
                            $query->where('start_time', '<=', time())->where('end_time', '>=', time());
                            break;
                        case 'show':
                            $query->where('prehead_time', '<=', time())->where('end_time', '>=', time());
                            break;
                        case 'ended':
                            $query->where('end_time', '<', time());
                            break;
                        default:
                            error_stop('status 状态错误');
                    }
                });
            }
        });
    }


    /**
     * 未开始的活动
     *
     * @param think\query\Query $query
     * @return void
     */
    public function scopeNostart($query)
    {
        return $query->where('start_time', '>', time());
    }


    /**
     * 预售的活动
     *
     * @param think\query\Query $query
     * @return void
     */
    public function scopePrehead($query)
    {
        return $query->where('prehead_time', '<=', time())->where('start_time', '>', time());
    }

    /**
     * 进行中的活动
     *
     * @param think\query\Query $query
     * @return void
     */
    public function scopeIng($query)
    {
        return $query->where('start_time', '<=', time())->where('end_time', '>=', time());
    }

    /**
     * 已经开始预售，并且没有结束的活动
     *
     * @param think\query\Query $query
     * @return void
     */
    public function scopeShow($query)
    {
        return $query->where('prehead_time', '<=', time())->where('end_time', '>=', time());
    }

    /**
     * 已经结束的活动
     *
     * @param think\query\Query $query
     * @return void
     */
    public function scopeEnded($query)
    {
        return $query->where('end_time', '<', time());
    }



    /**
     * 修改器 classify
     *
     * @param string $value
     * @param array $data
     * @return integer|null
     */
    public function setClassifyAttr($value, $data)
    {
        $classify = $value ?: ($data['classify'] ?? null);
        if (!$classify) {
            $type = $data['type'] ?? null;        // 活动类型

            $classify = $this->getClassify($type);
        }

        return $classify;
    }


    /**
     * 修改器 预热时间
     *
     * @param string $value
     * @param array $data
     * @return integer|null
     */
    public function setPreheadTimeAttr($value, $data)
    {
        // promo 类型 prehead_time 永远等于 start_time
        $value = (isset($data['classify']) && $data['classify'] == 'promo') ? $data['start_time'] : ($value ?: $data['start_time']);
        return $this->attrFormatUnix($value);
    }

    /**
     * 修改器 开始时间
     *
     * @param string $value
     * @return integer|null
     */
    public function setStartTimeAttr($value)
    {
        return $this->attrFormatUnix($value);
    }

    /**
     * 修改器 结束时间
     *
     * @param string $value
     * @return integer|null
     */
    public function setEndTimeAttr($value)
    {
        return $this->attrFormatUnix($value);
    }


    public function getStatusAttr($value, $data)
    {
        return $this->getStatusCode($data['prehead_time'], $data['start_time'], $data['end_time']);
    }


    public function getStatusTextAttr($value, $data)
    {
        return $this->getStatusText($this->status);
    }


    public function getGoodsListAttr($value, $data)
    {
        if ($data['goods_ids']) {
            
            $goods = Goods::field('id,title,price,sales,image,status')->whereIn('id', $data['goods_ids'])->select();
            $goods = collection($goods)->toArray();             // 全部转数组
            
            $goodsIds = array_column($goods, 'id');
            $activitySkuPrices = ActivitySkuPriceModel::where('activity_id', $data['id'])->whereIn('goods_id', $goodsIds)->order('id', 'asc')->select();
            $activitySkuPrices = collection($activitySkuPrices)->toArray();
            
            // 后台编辑活动时，防止不编辑规格无法提交问题
            foreach ($goods as &$gd) {
                // 处理 $gd['activity_sku_prices']
                $gd['activity_sku_prices'] = [];
                foreach ($activitySkuPrices as $skuPrice) {
                    if ($skuPrice['goods_id'] == $gd['id']) {
                        $gd['activity_sku_prices'][] = $skuPrice;
                    }
                }

                // 处理活动规格,数据
                foreach ($gd['activity_sku_prices'] as $key => $skuPrice) {
                    $skuPrice = ActivityFacade::showSkuPrice($data['type'], $skuPrice);
                    $gd['activity_sku_prices'][$key] = $skuPrice;
                }
            }
        }

        return $goods ?? [];
    }


    public function getRulesAttr($value, $data)
    {
        $rules = $data['rules'] ? json_decode($data['rules'], true) : [];
        $type = $data['type'];

        // 获取各个活动规则相关的特殊数据
        $rules = ActivityFacade::rulesInfo($type, $rules);

        return $rules;
    }


    /**
     * 通过时间判断活动状态
     *
     * @param integer $prehead_time 预热时间
     * @param integer $start_time    开始时间
     * @param integer $end_time      结束时间
     * @return string
     */
    public static function getStatusCode($prehead_time, $start_time, $end_time)
    {
        // 转为时间戳,（从 redis 中取出来的是 时间格式）
        if (($prehead_time && $prehead_time > time()) || (!$prehead_time && $start_time > time())) {
            $status = 'nostart';        // 未开始
        } else if ($prehead_time && $prehead_time < time() && $start_time > time()) {
            $status = 'prehead';        // 预热
        } else if ($start_time < time() && $end_time > time()) {
            $status = 'ing';
        } else if ($end_time < time()) {
            $status = 'ended';
        }

        return $status ?? 'ended';
    }

    /**
     * 判断活动状态中文
     *
     * @param string $status      活动状态
     * @return string
     */
    public static function getStatusText($status)
    {
        if ($status == 'nostart') {
            $status_text = '未开始';
        } elseif ($status == 'prehead') {
            $status_text = '预热中';
        } elseif ($status == 'ing') {
            $status_text = '进行中';
        } elseif ($status == 'ended') {
            $status_text = '已结束';
        }

        return $status_text ?? '已结束';
    }


    public function getEndTimeUnixAttr($value, $data)
    {
        return isset($data['end_time']) ? $this->getData('end_time') : 0;
    }


    public function activitySkuPrices()
    {
        return $this->hasMany(SkuPrice::class, 'activity_id');
    }
}
