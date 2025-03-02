<?php

namespace addons\shopro\library\activity\provider;

use addons\shopro\service\StockSale;
use addons\shopro\exception\ShoproException;

/**
 * 秒杀
 */
class Seckill extends Base
{
    protected $rules = [
        "is_commission" => "require|boolean",
        "is_free_shipping" => "require|boolean",
        "sales_show_type" => "require",
        "limit_num" => "number|egt:0",
        "order_auto_close" => "float|egt:0",
    ];


    protected $message  =   [
    ];


    protected $default = [
        "is_commission" => 0,          // 是否参与分销
        "is_free_shipping" => 0,          // 是否包邮
        "sales_show_type" => "real",          // real=真实活动销量|goods=商品总销量（包含虚拟销量）
        "limit_num" => 0,                     // 每人限购数量 0:不限购
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

        $this->saveSkuPrice($goodsList, $activity);
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
                    $skuPrice->old_price = $skuPrice->price;        // 保存原始普通商品规格的价格（计算活动的优惠）
                    $skuPrice->stock = ($activitySkuPrice['stock'] > $stock) ? $stock : $activitySkuPrice['stock'];     // 活动库存不能超过商品库存
                    $skuPrice->sales = $activitySkuPrice['sales'];
                    $skuPrice->price = $activitySkuPrice['price'];
                    $skuPrice->status = $activitySkuPrice['status'];        // 采用活动的上下架
                    $skuPrice->min_price = $activitySkuPrice['price'];      // 当前活动规格最小价格，这里是秒杀价
                    $skuPrice->max_price = $activitySkuPrice['price'];      // 用作计算活动中最大价格

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
        // 秒杀
        $rules = $activity['rules'];

        $currentSkuPrice = $buyInfo['current_sku_price'];

        // 当前库存，小于要购买的数量
        $need_num = $buyInfo['goods_num'] + ($need_add_num ?? 0);
        if ($currentSkuPrice['stock'] < $need_num) {
            throw new ShoproException('商品库存不足');
        }

        $buyInfo['is_commission'] = $rules['is_commission'] ?? 0;        // 是否参与分销
        return $buyInfo;
    }



    public function buy($buyInfo, $activity)
    {
        $user = auth_user();

        // 判断 并 增加 redis 销量
        $stockSale = new StockSale();
        $stockSale->cacheForwardSale($buyInfo);

        return $buyInfo;
    }



    public function buyOk($order, $user)
    {
        // 不需要处理
    }


    /**
     * 购买失败
     *
     * @param array $order
     * @return void
     */
    public function buyFail($order, $type)
    {
        // 判断扣除预销量 (活动信息还在 redis)
        $stockSale = new StockSale();
        $stockSale->cacheBackSale($order);
    }
}