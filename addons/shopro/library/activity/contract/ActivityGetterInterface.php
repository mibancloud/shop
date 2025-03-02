<?php

namespace addons\shopro\library\activity\contract;

interface ActivityGetterInterface
{
    /**
     * 获取所有给定类型给定状态的活动
     *
     * @param array $activityTypes
     * @return array
     */
    public function getActivities($activityTypes, $status = 'all');


    /**
     * 获取时间区间内的所有给定类型的活动
     *
     * @param array $range
     * @param array $activityTypes
     * @param string $range_type         overlap=只要区间有重叠的就算|contain=包含，必须在这个区间之内的
     * @return array
     */
    public function getActivitiesByRange($range, $activityTypes = [], $range_type = 'overlap');


    /**
     * 获取商品的所有正在进行，或正在预售的活动
     *
     * @param integer $goods_id
     * @return array
     */
    public function getGoodsActivitys($goods_id);

    /**
     * 获取商品的所有正在进行，或正在预售的营销
     *
     * @param integer $goods_id
     * @return array
     */
    public function getGoodsPromos($goods_id);


    
}