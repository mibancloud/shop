<?php

namespace addons\shopro\job;

use think\queue\Job;
use think\Db;
use think\exception\HttpResponseException;
use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\activity\Groupon;
use addons\shopro\library\activity\traits\Groupon as GrouponTrait;

/**
 * 拼团自动操作
 */
class GrouponAutoOper extends BaseJob
{
    use GrouponTrait;

    /**
     * 拼团判断，将团结束, 
     */
    public function expire(Job $job, $data)
    {
        if (check_env('yansongda', false)) {
            set_addon_config('epay', ['version' => 'v3'], false);
            \think\Hook::listen('epay_config_init');
        }

        try {
            $activity = $data['activity'];
            $activity_groupon_id = $data['activity_groupon_id'];
            $activityGroupon = Groupon::where('id', $activity_groupon_id)->find();

            // 活动正在进行中， 走这里的说明人数 都没满
            if ($activityGroupon && $activityGroupon['status'] == 'ing') {
                Db::transaction(function () use ($activity, $activityGroupon) {
                    $rules = $activity['rules'];
                    // 是否虚拟成团
                    $is_fictitious = $rules['is_fictitious'] ?? 0;
                    // 最大虚拟人数 ,不填或者 "" 不限制人数，都允许虚拟成团， 0相当于不允许虚拟成团
                    $fictitious_num = (!isset($rules['fictitious_num']) || $rules['fictitious_num'] === '') ? 'no-limit' : $rules['fictitious_num'];
                    // 拼团剩余人数
                    $surplus_num = $activityGroupon['num'] - $activityGroupon['current_num'];

                    if ($is_fictitious && ($fictitious_num == 'no-limit' || $fictitious_num >= $surplus_num)) {
                        // 虚拟成团，如果虚拟用户不够，则自动解散
                        $this->finishFictitiousGroupon($activity, $activityGroupon);
                    } else {
                        // 解散退款
                        $this->invalidRefundGroupon($activityGroupon);
                    }
                });
            }

            // 删除 job
            $job->delete();
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'GrouponAutoOper.expire.HttpResponseException', $message);
        } catch (\Exception $e) {
            // 队列执行失败
            format_log_error($e, 'GrouponAutoOper.expire');
        }
    }


    /**
     * 拼团判断，提前虚拟成团
     */
    public function fictitious(Job $job, $data)
    {
        try {
            $activity = $data['activity'];
            $activity_groupon_id = $data['activity_groupon_id'];
            $activityGroupon = Groupon::where('id', $activity_groupon_id)->find();

            // 活动正在进行中， 走这里的说明人数 都没满
            if ($activityGroupon && $activityGroupon['status'] == 'ing') {
                Db::transaction(function () use ($activity, $activityGroupon) {
                    $rules = $activity['rules'];
                    // 是否虚拟成团
                    $is_fictitious = $rules['is_fictitious'] ?? 0;
                    // 最大虚拟人数 ,不填或者 "" 不限制人数，都允许虚拟成团， 0相当于不允许虚拟成团
                    $fictitious_num = (!isset($rules['fictitious_num']) || $rules['fictitious_num'] === '') ? 'no-limit' : $rules['fictitious_num'];
                    // 拼团剩余人数
                    $surplus_num = $activityGroupon['num'] - $activityGroupon['current_num'];

                    if ($is_fictitious && ($fictitious_num == 'no-limit' || $fictitious_num >= $surplus_num)) {
                        // 虚拟成团，如果不符合成团条件，则不处理
                        $this->finishFictitiousGroupon($activity, $activityGroupon, false);
                    }
                });
            }

            // 删除 job
            $job->delete();
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'GrouponAutoOper.fictitious.HttpResponseException', $message);
        } catch (\Exception $e) {
            // 队列执行失败
            format_log_error($e, 'GrouponAutoOper.fictitious');
        }
    }
}
