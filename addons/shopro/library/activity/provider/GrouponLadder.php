<?php

namespace addons\shopro\library\activity\provider;

use addons\shopro\library\activity\traits\Groupon as GrouponTrait;
use addons\shopro\service\StockSale;
use addons\shopro\exception\ShoproException;

/**
 * 阶梯拼团
 */
class GrouponLadder extends Base
{
    use GrouponTrait;

    protected $rules = [
        "is_commission" => "require|boolean",
        "is_free_shipping" => "require|boolean",
        "sales_show_type" => "require",
        "ladders" => "require|array",
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
        'ladders.require'     => '请填写拼团阶梯',
        'ladders.array'     => '请填写拼团阶梯',
    ];


    protected $default = [
        "is_commission" => 0,          // 是否参与分销
        "is_free_shipping" => 0,          // 是否包邮
        "sales_show_type" => "real",          // real=真实活动销量|goods=商品总销量（包含虚拟销量）
        "ladders" => [],                    // {ladder_one:2,ladder_two:2,ladder_three:3}
        "is_alone" => 0,                      // 是否允许单独购买
        "is_fictitious" => 0,                 // 是否允许虚拟成团
        "fictitious_num" => 0,           // 最多虚拟人数    0:不允许虚拟 '' 不限制
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
            // 处理 阶梯价格，团长优惠
            $rules = $activity->rules;
            $is_leader_discount = $rules['is_leader_discount'] ?? 0;
            $ladders = $rules['ladders'] ?? 0;

            $ext = [
                'is_leader_discount' => $is_leader_discount,
                'ladders' => []
            ];
            foreach ($ladders as $ladder_level => $ladder) {
                $ladder_price = isset($skuPrice[$ladder_level]) ? number_format(floatval($skuPrice[$ladder_level]), 2, '.', '') : 0;
                $leader_ladder_price = (isset($skuPrice[$ladder_level . '_leader']) && $skuPrice[$ladder_level . '_leader'] > 0) ? number_format(floatval($skuPrice[$ladder_level . '_leader']), 2, '.', '') : $ladder_price;     // 默认当前阶梯参团价

                $current = [
                    'ladder_level' => $ladder_level,
                    'ladder' => $ladder,
                    'ladder_price' => $ladder_price,
                    'leader_ladder_price' => $leader_ladder_price
                ];
                unset($skuPrice[$ladder_level], $skuPrice[$ladder_level . '_leader']);
                $ext['ladders'][] = $current;
            }

            $skuPrice['ext'] = $ext;
            return $skuPrice;
        });
    }


    public function showSkuPrice($skuPrice) 
    {
        $ext = $skuPrice['ext'] ?? [];
        $ladders = $ext['ladders'] ?? [];

        if ($ladders) {
            foreach ($ladders as $ladder) {
                $ladder_level = $ladder['ladder_level'];
                $skuPrice[$ladder_level] = $ladder['ladder_price'];
                $skuPrice[$ladder_level . '_leader'] = $ladder['leader_ladder_price'];
            }
        } else {
            // 全部初始化为 0 
            $skuPrice['ladder_one'] = 0;
            $skuPrice['ladder_two'] = 0;
            $skuPrice['ladder_three'] = 0;
            $skuPrice['ladder_one_leader'] = 0;
            $skuPrice['ladder_two_leader'] = 0;
            $skuPrice['ladder_three_leader'] = 0;
        }

        return $skuPrice;
    }


    public function recoverSkuPrices($goods, $activity)
    {
        $groupon_num = request()->param('groupon_num', 0);     // 是否传了开团人数（这里不再使用阶梯,前端没反）
        $activitySkuPrices = $activity['activity_sku_prices'];
        $skuPrices = $goods->sku_prices;
        
        foreach ($skuPrices as $key => &$skuPrice) {
            $stock = $skuPrice->stock;      // 下面要用
            $skuPrice->stock = 0;
            $skuPrice->sales = 0;
            foreach ($activitySkuPrices as $activitySkuPrice) {
                if ($skuPrice['id'] == $activitySkuPrice['goods_sku_price_id']) {
                    // 采用活动的 规格内容
                    $is_leader_discount = $activitySkuPrice['ext']['is_leader_discount'];
                    $ladders = $activitySkuPrice['ext']['ladders'];
                    $skuPrice->old_price = $skuPrice->price;        // 保存原始普通商品规格的价格（计算活动的优惠）
                    $skuPrice->stock = ($activitySkuPrice['stock'] > $stock) ? $stock : $activitySkuPrice['stock'];     // 活动库存不能超过商品库存
                    $skuPrice->sales = $activitySkuPrice['sales'];
                    $skuPrice->is_leader_discount = $is_leader_discount;            // 是否团长优惠
                    $skuPrice->ladders = $ladders;    // 阶梯价格,包含团长优惠
                    $skuPrice->status = $activitySkuPrice['status'];        // 采用活动的上下架
                    $skuPrice->ext = $activitySkuPrice['ext'];        // 活动规格 ext, order_item 保存备用
                    $skuPrice->min_price = min(array_column($ladders, 'ladder_price'));        // 当前活动规格最小价格，这里是阶梯最低拼团价（不要团长价）
                    $skuPrice->max_price = max(array_column($ladders, 'ladder_price'));        // 当前活动规格最大价格，这里是阶梯最低拼团价（不要团长价）

                    $ladders = array_column($ladders, null, 'ladder');
                    $currentLadder = $ladders[$groupon_num] ?? current($ladders);
                    $skuPrice->ladder_price = $currentLadder['ladder_price'];        // 当前阶梯价格（默认是 ladder_one）
                    $skuPrice->leader_ladder_price = $currentLadder['leader_ladder_price'];        // 当前阶梯团长价（默认是 ladder_one）
                    $skuPrice->price = $is_leader_discount ? $skuPrice->leader_ladder_price : $skuPrice->ladder_price;      // 默认是计算好的价格，团长价或者普通价

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
        $groupon_num = request()->param('groupon_num', 0);
        // 拼团
        $rules = $activity['rules'];
        $is_alone = $rules['is_alone'] ?? 1;
        
        $currentSkuPrice = $buyInfo['current_sku_price'];
        $is_leader_discount = $currentSkuPrice['is_leader_discount'];       // 是否团长优惠
        $ladders = $currentSkuPrice['ladders'];                             // 阶梯数据
        $ladders = array_column($ladders, null, 'ladder');
        $currentLadder = $ladders[$groupon_num] ?? current($ladders);       // 当前阶梯的 价格数据

        // 开新团,并且没有找到要参与的阶梯数据
        if (!$groupon_id && (!$currentLadder || $currentLadder['ladder'] <= 1)) {
            throw new ShoproException('请选择正确的开团阶梯');
        }

        $buyInfo['ladder'] = $currentLadder;        // 存储当前购买的拼团阶梯 ladder 

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
                $buyInfo['current_sku_price']['price'] = $currentLadder['leader_ladder_price'];
            } else {
                // 参与团，或者没有团长优惠
                $buyInfo['current_sku_price']['price'] = $currentSkuPrice['ladder_price'];
            }
        }

        // 如果是开新团
        if (!$groupon_id && $buy_type == 'groupon') {
            // 成团人数
            $num = $currentLadder['ladder'] ?? 1;
            
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
                throw new ShoproException('商品库存不足以开团');
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
            // 处理拼团特殊的数据
            $ladder = $itemExt['ladder'];
            $team_num = $ladder['ladder'];

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