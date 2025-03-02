<?php

namespace addons\shopro\library\activity\getter;

class Redis extends Base
{

    /**
     * 获取所有给定类型给定状态的活动
     *
     * @param array $activityTypes
     * @return array
     */
    public function getActivities($activityTypes, $status = 'all')
    {
        $activities = $this->redis->getActivityList($activityTypes, $status, 'clear');

        return $activities;
    }




    /**
     * 获取指定时间区间内的活动
     *
     * @param array $range
     * @param array $activityTypes
     * @param string $range_type        overlap=只要区间有重叠的就算|contain=包含，必须在这个区间之内的
     * @return array
     */
    public function getActivitiesByRange($range, $activityTypes = [], $range_type = 'overlap')
    {
        $activities = $this->redis->getActivityList($activityTypes);

        $newActivities = [];
        foreach ($activities as $key => $activity) {
            if ($this->rangeCompare($range, [$activity['prehead_time'], $activity['end_time']], $range_type)) {
                $newActivities[] = $activity;
            }
        }

        return $newActivities;
    }



    /**
     * 获取商品的所有正在进行，或正在预售的活动
     *
     * @param integer $goods_id
     * @return array
     */
    public function getGoodsActivitys($goods_id) 
    {
        $classify = $this->model->classifies();
        $activityTypes = array_keys($classify['activity']);

        $activities = $this->redis->getGoodsActivitys($goods_id, $activityTypes, ['prehead', 'ing']);

        return $activities;
    }



    /**
     * 获取商品的所有正在进行，或正在预售的营销
     *
     * @param integer $goods_id
     * @return array
     */
    public function getGoodsPromos($goods_id) 
    {
        $classify = $this->model->classifies();
        $activityTypes = array_keys($classify['promo']);

        $activities = $this->redis->getGoodsActivitys($goods_id, $activityTypes, ['prehead', 'ing'], 'promo');

        return $activities;
    }


    /**
     * 通过 活动 id 获取指定活动
     *
     * @param integer $goods_id
     * @param integer $activity_id
     * @return array
     */
    public function getGoodsActivityByActivity($goods_id, $activity_id)
    {
        $activities = $this->redis->getGoodsActivityByActivity($goods_id, $activity_id);

        return $activities;
    }



    /**
     * 比较时间区间
     *
     * @param array $range
     * @param array $activityRange
     * @param string $type
     * @return bool
     */
    private function rangeCompare($range, $activityRange, $type = 'overlap')
    {
        if ($type == 'overlap') {
            if ($range[1] >= $activityRange[0] && $range[0] <= $activityRange[1]) {     // 时间相等也算没有交集
                // 两个时间区间有交集
                return true;
            }
            return false;
        } elseif ($type == 'contain') {
            if ($range[0] <= $activityRange[0] && $range[1] >= $activityRange[1]) {       // 时间相等算包含
                // activityRange 是 range 的子集
                return true;
            }
            return false;
        }
    }
}