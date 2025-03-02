<?php

namespace addons\shopro\library\activity\getter;

class Db extends Base
{

    /**
     * 获取所有给定类型给定状态的活动
     *
     * @param array $activityTypes
     * @return array
     */
    public function getActivities($activityTypes, $status = 'all')
    {
        $status = is_array($status) ? $status : [$status];

        $activities = $this->model->where('type', 'in', $activityTypes);

        if (!in_array('all', $status)) {
            $activities = $activities->statusComb($status);
        }

        $activities = $activities->select();
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
        $activities = $this->model->where('type', 'in', $activityTypes);

        if ($range_type == 'overlap') {
            $activities = $activities->where('prehead_time', '<=', $range[1])->where('end_time', '>=', $range[0]);
        } elseif ($range_type == 'contain') {
            $activities = $activities->where('prehead_time', '>=', $range[0])->where('end_time', '<=', $range[1]);
        }

        $activities = $activities->select();

        return $activities;
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

        $activities = $this->model->show()->where('find_in_set(:id,goods_ids)', ['id' => $goods_id])
            ->with(['activity_sku_prices' => function ($query) use ($goods_id) {
                $query->where('goods_id', $goods_id)
                ->where(
                    'status',
                    'up'
                );
            }])
            ->where('type', 'in', $activityTypes)
            ->order('start_time', 'asc')     // 优先查询最先开始的活动（允许商品同时存在多个活动中， 只要开始结束时间不重合）
            ->select();

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

        $promos = $this->model->show()
            ->where(function ($query) use ($goods_id) {
                // goods_ids 里面有当前商品，或者 goods_ids 为null(所有商品都参与)
                $query->where('find_in_set('. $goods_id .',goods_ids)')
                    ->whereOr('goods_ids', null)
                    ->whereOr('goods_ids', '');
            })
            ->where('type', 'in', $activityTypes)
            ->order('start_time', 'asc')     // 优先查询最先开始的活动（允许商品同时存在多个活动中， 只要开始结束时间不重合）
            ->select();

        return $promos;
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
        $classify = $this->model->classifies();
        $activityTypes = array_keys($classify['activity']);

        $activity = $this->model->where('id', $activity_id)->find();

        if ($activity) {
            $goods_ids = array_values(array_filter(explode(',', $activity->goods_ids)));
            if (!in_array($goods_id, $goods_ids) && !empty($goods_ids)) {
                return null;
            }
            
            if (in_array($activity['type'], $activityTypes)) {
                // 活动规格
                $activity->activity_sku_prices = $activity->activity_sku_prices;
            }

            $activity = $activity->toArray();
        }

        return $activity;
    }
}