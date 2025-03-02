<?php

namespace addons\shopro\library\activity\traits;

trait CheckActivity
{

    /**
     * 检测活动商品必须至少设置一个规格为活动规格
     *
     * @param array $goodsList
     * @return void
     */
    public function checkActivitySkuPrice($goodsList) 
    {
        foreach ($goodsList as $key => $goods) {
            $activitySkuPrice = $goods['activity_sku_prices'] ?? [];
            if (!$activitySkuPrice) {
                error_stop('请至少将商品一个规格设置为活动规格');
            }
        }
    }



    /**
     * 检测满赠规则设置
     *
     * @param array $discounts
     * @return void
     */
    public function checkGiftDiscount($discounts)
    {
        foreach ($discounts as $discount) {
            $types = $discount['types'];

            if (in_array('coupon', $types) && (!isset($discount['coupon_ids']) || empty($discount['coupon_ids']))) {
                // 验证优惠券
                error_stop('请选择要赠送的优惠券');
            }
            if (in_array('money', $types) && (!isset($discount['money']) || empty($discount['money']))) {
                // 赠送余额
                error_stop('请填写要赠送的余额');
            }
            if (in_array('score', $types) && (!isset($discount['score']) || empty($discount['score']))) {
                // 赠送积分
                error_stop('请填写要赠送的积分');
            }

            if (in_array('goods', $types) && (!isset($discount['goods_ids']) || empty($discount['goods_ids']))) {
                // 赠送优惠券
                error_stop('请选择要赠送的商品');
            }
        }
    }



    /**
     * 幸运拼团参与奖校验
     *
     * @param array $part_gift
     * @return void
     */
    public function checkLuckyPartGift($part_gift)
    {
        $types = $part_gift['types'];

        if (in_array('coupon', $types) && (!isset($part_gift['coupon_ids']) || empty($part_gift['coupon_ids']))) {
            // 验证优惠券
            error_stop('请选择要赠送的优惠券');
        }
        if (in_array('money', $types) && (!isset($discount['money']) || empty($discount['money']))) {
            // 赠送余额
            error_stop('请填写要赠送的余额');
        }
        if (in_array('score', $types) && (!isset($discount['score']) || empty($discount['score']))) {
            // 赠送积分
            error_stop('请填写要赠送的积分');
        }
    }



    /**
     * 检测活动商品是否重合
     *
     * @return void
     */
    public function checkActivityConflict($params, $goodsList = [], $activity_id = 0)
    {
        if ($params['classify'] == 'activity') {
            // 活动可以共存，不检测冲突
            return true;
        }
        $start_time = strtotime($params['start_time']);
        $end_time = strtotime($params['end_time']);
        $prehead_time = isset($params['prehead_time']) && $params['prehead_time'] ? strtotime($params['prehead_time']) : $start_time;
        $goodsIds = array_column($goodsList, 'id');     // 获取活动提交过来的所有商品的 id
        $goodsList = array_column($goodsList, null, 'id');

        // 获取所有时间有交叉的活动
        $activities = $this->getActivities($params['type'], [$prehead_time, $end_time]);

        foreach ($activities as $key => $activity) {
            if ($activity_id && $activity_id == $activity['id']) {
                // 编辑的时候，把自己排除在外
                continue;
            }

            $intersect = [];    // 两个活动重合的商品Ids
            if ($goodsIds) {
                $activityGoodsIds = array_filter(explode(',', $activity['goods_ids']));
                // 不是全部商品，并且不重合
                if ($activityGoodsIds && !$intersect = array_intersect($activityGoodsIds, $goodsIds)) {
                    // 商品不重合，继续验证下个活动
                    continue;
                }
            }

            $goods_names = '';
            foreach ($intersect as $id) {
                if (isset($goodsList[$id]) && isset($goodsList[$id]['title'])) {
                    $goods_names .= $goodsList[$id]['title'] . ',';
                }
            }

            if ($goods_names) {
                $goods_names = mb_strlen($goods_names) > 40 ? mb_substr($goods_names, 0, 37) . '...' : $goods_names;
            }

            if (!$goodsIds && !$intersect) {
                // 没有商品
                $msg = '活动时间与 “' . $activity['type_text'] . ' 活动的 ' . $activity['title'] . '” 冲突';
            } else {
                $msg = ((count($intersect) > 1 || !$goodsIds) ? '部分商品' : '该商品') . ($goods_names ? ' ' . $goods_names . ' ' : '') . '已在 “' . $activity['title'] . '” 活动中设置';
            }

            error_stop($msg);
        }
    }



    /**
     * 获取所有活动
     *
     * @param string $current_activity_type 
     * @param array $range  要查询的时间区间 
     * @return array
     */
    private function getActivities($current_activity_type, $range) {
        // 获取当前活动的互斥活动
        $activityTypes = $this->manager->model->getMutexActivityTypes($current_activity_type);

        $activities = $this->manager->getActivitiesByRange($range, $activityTypes);

        return $activities;
    }
}