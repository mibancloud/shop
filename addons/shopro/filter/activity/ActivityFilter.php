<?php

namespace addons\shopro\filter\activity;

use addons\shopro\filter\BaseFilter;
use think\db\Query;

/**
 * 商品筛选
 */
class ActivityFilter extends BaseFilter
{
    protected $keywordFields = [];


    /**
     * 活动状态
     *
     * @param Query $query
     * @param string|array $status   查询数据
     * @return void
     */
    public function status($query, $status)
    {
        $status = $this->getValue($status);

        if ($status == 'ing') {
            $query->where('start_time', '<', time())->where('end_time', '>', time());
        } else if ($status == 'nostart') {
            $query->where('start_time', '>', time());
        } else if ($status == 'ended') {
            $query->where('end_time', '<', time());
        } else if ($status == 'noend') {
            // 未结束,不管是否开始了（店铺装修时用）
            $query->where('end_time', '>', time());
        }

        return $query;
    }



    /**
     * 活动时间
     *
     * @param Query $query
     * @param string|array $status   查询数据
     * @return void
     */
    public function activityTime($query, $activity_time)
    {
        $activity_time = $this->getValue($activity_time);
        $activityTime = array_filter(explode(' - ', $activity_time));

        if ($activityTime) {
            $query->where('start_time', '>=', strtotime($activityTime[0]))->where('end_time', '<=', strtotime($activityTime[1]));
        }

        return $query;
    }
}
