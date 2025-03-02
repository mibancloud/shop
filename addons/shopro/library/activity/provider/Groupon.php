<?php

namespace addons\shopro\library\activity\provider;

use addons\shopro\library\activity\traits\Groupon as GrouponTrait;
use addons\shopro\service\StockSale;
use addons\shopro\exception\ShoproException;

/**
 * 普通拼团
 */
class Groupon extends Base
{
    use GrouponTrait;

    protected $rules = [
        "is_commission" => "require|boolean",
        "is_free_shipping" => "require|boolean",
        "sales_show_type" => "require",
        "team_num" => "require|number",
        "is_alone" => "require|boolean",
        "is_fictitious" => "require|boolean",
        "fictitious_time" => "requireIf:is_fictitious,1|float|egt:0",
        "is_team_card" => "require|boolean",
        "is_leader_discount" => "require|boolean",
        "valid_time" => "require|float|egt:0",
        "limit_num" => "number|egt:0",
        "refund_type" => "require",                // 退款方式 back=原路退回|money=退回到余额
        "order_auto_close" => "float|egt:0",
    ];


    protected $message  =   [
        'team_num.require'     => '请填写成团人数',
    ];


    protected $default = [
        "is_commission" => 0,          // 是否参与分销
        "is_free_shipping" => 0,          // 是否包邮
        "sales_show_type" => "real",          // real=真实活动销量|goods=商品总销量（包含虚拟销量）
        "team_num" => 2,                    // 成团人数，最少两人
        "is_alone" => 0,                      // 是否允许单独购买
        "is_fictitious" => 0,                 // 是否允许虚拟成团
        "fictitious_num" => 0,           // 最多虚拟人数 0:不允许虚拟 '' 不限制
        "fictitious_time" => 0,           // 开团多长时间自动虚拟成团
        "is_team_card" => 0,                     // 参团卡显示
        "is_leader_discount" => 0,                     // 团长优惠
        "valid_time" => 0,                      // 组团有效时间, 0：一直有效
        "limit_num" => 0,                     // 每人限购数量 0:不限购
        "refund_type" => "back",                // 退款方式 back=原路退回|money=退回到余额
        "order_auto_close" => 0,               // 订单自动关闭时间，如果为 0 将使用系统级订单自动关闭时间
    ];


    public function check($params, $activity_id = 0)
    {
        // 数据验证
        $params = parent::check($params);

        // 验证添加的活动商品是否至少设置了一个活动规格
        $this->checkActivitySkuPrice($params['goods_list']);
        
        // 检测活动之间是否存在冲突
        $this->checkActivityConflict($params, $params['goods_list'], $activity_id);

        return $params;
    }


    public function save($activity, $params = []) 
    {
        $goodsList = $params['goods_list'];

        $this->saveSkuPrice($goodsList, $activity, function ($skuPrice) use ($activity) {
            // 处理 团长优惠
            $rules = $activity->rules;
            $is_leader_discount = $rules['is_leader_discount'] ?? 0;
            $leader_price = $skuPrice['price'];
            if ($is_leader_discount && isset($skuPrice['leader_price'])) {
                $leader_price = $skuPrice['leader_price'];
            }
            $ext = [
                'is_leader_discount' => $is_leader_discount, 
                'leader_price' => number_format(floatval($leader_price), 2, '.', '')
            ];
            unset($skuPrice['leader_price']);
            $skuPrice['ext'] = $ext;

            return $skuPrice;
        });
    }


    public function showSkuPrice($skuPrice)
    {
        $ext = $skuPrice['ext'] ?? [];

        $skuPrice['leader_price'] = $ext['leader_price'] ?? $skuPrice['price'];

        return $skuPrice;
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
                if ($skuPrice->id == $activitySkuPrice['goods_sku_price_id']) {
                    // 采用活动的 规格内容
                    $is_leader_discount = $activitySkuPrice['ext']['is_leader_discount'];
                    $leader_price = $activitySkuPrice['ext']['leader_price'];
                    $skuPrice->old_price = $skuPrice->price;        // 保存原始普通商品规格的价格（计算活动的优惠）
                    $skuPrice->stock = ($activitySkuPrice['stock'] > $stock) ? $stock : $activitySkuPrice['stock'];     // 活动库存不能超过商品库存
                    $skuPrice->sales = $activitySkuPrice['sales'];
                    $skuPrice->groupon_price = $activitySkuPrice['price'];      // 不覆盖原来规格价格，用作单独购买，将活动的价格设置为新的拼团价格
                    $skuPrice->is_leader_discount = $is_leader_discount;            // 是否团长优惠
                    $skuPrice->leader_price = $leader_price;    // 团长优惠价格
                    $skuPrice->status = $activitySkuPrice['status'];        // 采用活动的上下架
                    $skuPrice->ext = $activitySkuPrice['ext'];        // 活动规格 ext, order_item 保存备用
                    $skuPrice->min_price = $activitySkuPrice['price'];        // 当前活动规格最小价格，这里是拼团价
                    $skuPrice->max_price = $activitySkuPrice['price'];        // 用作计算活动中最大价格

                    // 记录相关活动类型
                    $skuPrice->activity_type = $activity['type'];
                    $skuPrice->activity_id = $activity['id'];
                    // 下单的时候需要存活动 的 sku_price_id）
                    $skuPrice->item_goods_sku_price = $activitySkuPrice;
                    break;
                }
            }
        }

        return $skuPrices;
    }



    /**
     * 这里要使用 shoproException 抛出异常
     *
     * @param array $buyInfo
     * @param array $activity
     * @return array
     */
    public function buyCheck($buyInfo, $activity)
    {
        $buy_type = request()->param('buy_type', 'groupon');
        $groupon_id = request()->param('groupon_id', 0);
        // 拼团
        $rules = $activity['rules'];
        $is_alone = $rules['is_alone'] ?? 1;

        $currentSkuPrice = $buyInfo['current_sku_price'];
        $is_leader_discount = $currentSkuPrice['is_leader_discount'];

        // 成团人数
        $num = $rules['team_num'] ?? 1;
        // 额外需要的库存
        $need_add_num = 0;

        // 要单独购买
        if ($buy_type == 'alone') {
            // 不允许单独购买
            if (!$is_alone) {
                throw new ShoproException('该商品不允许单独购买');
            }
        } else {
            // 拼团，临时将拼团价设置为商品价格
            if (!$groupon_id && $is_leader_discount) {
                // 开新团，并且有团长优惠，使用优惠价格
                $buyInfo['current_sku_price']['price'] = $currentSkuPrice['leader_price'];
            } else {
                // 参与团，或者没有团长优惠
                $buyInfo['current_sku_price']['price'] = $currentSkuPrice['groupon_price'];
            }
        }

        // 如果是开新团
        if (!$groupon_id && $buy_type == 'groupon') {
            // 开团需要的最小库存
            $need_add_num = ($num - 1);
        }

        // 当前库存，小于要购买的数量
        $need_num = $buyInfo['goods_num'] + ($need_add_num ?? 0);
        if ($currentSkuPrice['stock'] < $need_num) {
            if ($need_add_num && $is_alone && !$groupon_id && $buy_type == 'groupon') {
                throw new ShoproException('商品库存不足以开团，请选择单独购买');
            } else if ($buy_type == 'alone') {
                throw new ShoproException('商品库存不足');
            } else {
                throw new ShoproException('该商品不允商品库存不足以开团许单独购买');
            }
        }

        $buyInfo['is_commission'] = $rules['is_commission'] ?? 0;        // 是否参与分销
        return $buyInfo;
    }



    public function buy($buyInfo, $activity)
    {
        $user = auth_user();
        $buy_type = request()->param('buy_type', 'groupon');
        $groupon_id = request()->param('groupon_id', 0);

        // 参与现有团
        if ($buy_type != 'alone' && $groupon_id) {
            // 检测并获取要参与的团
            $activityGroupon = $this->checkAndGetJoinGroupon($buyInfo, $user, $groupon_id);
        }

        // 判断 并 增加 redis 销量
        $stockSale = new StockSale();
        $stockSale->cacheForwardSale($buyInfo);

        // （开新团不判断）参与旧团 增加预拼团人数，上面加入团的时候已经判断过一次了，所以这里 99.99% 会加入成功的
        if (isset($activityGroupon) && $activityGroupon) {
            // 增加拼团预成员人数
            $goods = $buyInfo['goods'];
            $activity = $goods['activity'];
            $this->grouponCacheForwardNum($activityGroupon, $activity, $user);
        }

        return $buyInfo;
    }



    public function buyOk($order, $user)
    {
        $this->joinGroupon($order, $user, function ($activityRules, $itemExt) {
            $team_num = $activityRules['team_num'] ?? 1;

            return compact('team_num');
        });
    }



    /**
     * 拼团购买失败
     *
     * @param \think\Model $order
     * @param string $type
     * @return void
     */
    public function buyFail($order, $type)
    {
        if ($type == 'invalid') {
            if ($order->pay_mode == 'offline') {
                // 肯定是已经货到付款的订单取消订单，这时候已经添加了参团记录
                $this->refundGrouponLog($order);
            } else {
                // 订单失效，扣除预拼团人数(只处理正在进行中的团)
                $this->grouponCacheBackNum($order, $type);
            }
        } else {
            // type = refund 退款订单将参团标记为已退款
            $this->refundGrouponLog($order);
        }

        // 判断扣除预销量 (活动信息还在 redis)
        $stockSale = new StockSale();
        $stockSale->cacheBackSale($order);
    }
}