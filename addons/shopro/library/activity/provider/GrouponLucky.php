<?php

namespace addons\shopro\library\activity\provider;

/**
 * 幸运拼团
 */
class GrouponLucky extends Base
{
    protected $rules = [
        // "is_commission" => "require|bool",
        // "is_free_shipping" => "require|bool",
        // "sales_show_type" => "require",
        // "team_num" => "require|number",
        // "lucky_num" => "require|number",
        // "is_fictitious" => "require|bool",
        // "fictitious_num" => "number|gt:0",
        // "fictitious_time" => "require|float|egt:0",
        // "part_gift" => "require|array",
        // "is_team_card" => "require|bool",
        // "is_leader_discount" => "require|bool",
        // "valid_time" => "require|float|gt:0",
        // "limit_num" => "number|gt:0",
        // "limit_team_buy" => "number|gt:0",
        // "refund_type" => "back",                // 退款方式 back=原路退回|money=退回到余额
        // "order_auto_close" => "float|gt:0",
    ];


    protected $message  =   [
        // 'team_num.require'     => '请填写成团人数',
        // 'is_alone.require'     => '请选择单独购买',
        // 'stock.gt'     => '请填写补货数量'
    ];


    protected $default = [
        "is_commission" => 0,          // 是否参与分销
        "is_free_shipping" => 0,          // 是否包邮
        "sales_show_type" => "real",          // real=真实活动销量|goods=商品总销量（包含虚拟销量）
        "team_num" => 2,                    // 成团人数，最少两人
        "lucky_num" => 1,                    // 拼中人数，最少一人
        "is_fictitious" => 0,                 // 是否允许虚拟成团
        "fictitious_num" => 0,           // 最多虚拟人数
        "fictitious_time" => 0,           // 开团多长时间自动虚拟成团
        "part_gift" => [],                     // {"types": "coupon=优惠券|score=积分|money=余额","coupon_ids":"赠优惠券时存在","total":"赠送优惠券总金额","score":"积分","money":"余额"}
        "is_team_card" => 0,                     // 参团卡显示
        "is_leader_discount" => 0,                     // 团长优惠
        "valid_time" => 0,                      // 组团有效时间
        "limit_num" => 0,                     // 每人限购数量
        "limit_team_buy" => 0,                     // 每人每团可参与次数
        "refund_type" => "back",                // 退款方式 back=原路退回|money=退回到余额
        "order_auto_close" => 0,               // 订单自动关闭时间
    ];


    public function check($params, $activity_id = 0)
    {
        // 数据验证
        $params = parent::check($params);

        // 验证添加的活动商品是否至少设置了一个活动规格
        $this->checkActivitySkuPrice($params['goods_list']);

        // 验证赠送规则字段
        $this->checkLuckyPartGift($params['rules']['part_gift']);

        // 检测活动之间是否存在冲突
        $this->checkActivityConflict($params, $params['goods_list'], $activity_id);

        return $params;
    }


    public function save($activity, $params = [])
    {
        $goodsList = $params['goods_list'];

        $this->saveSkuPrice($goodsList, $activity->id);
    }



    public function recoverSkuPrices($goods, $activity)
    {
        $activitySkuPrices = $activity['activity_sku_prices'];
        $skuPrices = $goods->sku_prices;

        foreach ($skuPrices as $key => &$skuPrice) {
            $stock = $skuPrice->stock;      // 下面要用
            $skuPrice->stock = 0;
            $skuPrice->sales = 0;
            foreach ($activitySkuPrices as $activitySkuPrice) {
                if ($skuPrice['id'] == $activitySkuPrice['goods_sku_price_id']) {
                    // 采用活动的 规格内容
                    $skuPrice->stock = ($activitySkuPrice['stock'] > $stock) ? $stock : $activitySkuPrice['stock'];     // 活动库存不能超过商品库存
                    $skuPrice->sales = $activitySkuPrice['sales'];
                    $skuPrice->groupon_price = $activitySkuPrice['price'];      // 不覆盖原来规格价格，用作单独购买，将活动的价格设置为新的拼团价格
                    $skuPrice->status = $activitySkuPrice['status'];        // 采用活动的上下架

                    // 记录相关活动类型
                    $skuPrice->activity_type = $activity['type'];
                    $skuPrice->activity_id = $activity['id'];
                    // 下单的时候需要存活动 的 sku_price_id）
                    $skuPrice->item_goods_sku_price = $activitySkuPrice;
                    break;
                }
            }
        }

        return $skuPrice;
    }
}