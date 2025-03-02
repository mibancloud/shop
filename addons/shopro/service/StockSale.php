<?php

namespace addons\shopro\service;

use app\admin\model\shopro\goods\Goods;
use app\admin\model\shopro\goods\SkuPrice;
use app\admin\model\shopro\app\ScoreSkuPrice;
use app\admin\model\shopro\activity\SkuPrice as ActivitySkuPriceModel;
use app\admin\model\shopro\order\OrderItem;
use addons\shopro\facade\ActivityRedis as ActivityRedisFacade;
use addons\shopro\facade\Redis;
use addons\shopro\traits\StockWarning;

/**
 * 库存销量
 */
class StockSale
{
    use StockWarning;


    /**
     * 下单锁定库存
     *
     * @param array $buyInfo
     * @return void
     */
    public function stockLock($buyInfo)
    {
        $goods = $buyInfo['goods'];

        // 普通商品还是积分商品
        $order_type = request()->param('order_type', '');
        $order_type = $order_type ?: 'goods';

        $buy_num = $buyInfo['goods_num'];

        // 记录缓存的 hash key
        $keyCacheStockHash = $this->getCacheStockHashKey();

        if ($order_type == 'score') {
            // 普通商品销量处理 (积分和普通商品的缓存 key 不相同)
            $keyScoreGoodsLockedNum = $this->getLockedGoodsKey($buyInfo['goods_id'], $buyInfo['goods_sku_price_id'], 'score');
            $score_locked_num = redis_cache($keyScoreGoodsLockedNum);

            // 验证积分商品库存是否充足(mysql 悲观锁，此方法可靠，但如果大规模秒杀，容易mysql 死锁，请将商品添加为秒杀)
            $stock = ScoreSkuPrice::where('goods_id', $buyInfo['goods_id'])->where('goods_sku_price_id', $buyInfo['current_sku_price']->id)->lock(true)->value('stock');
            if (($stock - $score_locked_num) < $buy_num) {
                error_stop('积分商品库存不足');
            }

            // 锁积分库存
            redis_cache()->INCRBY($keyScoreGoodsLockedNum, $buy_num);

            // 记录已经缓存的库存的key,如果下单出现异常，将所有锁定的库存退回
            redis_cache()->HSET($keyCacheStockHash, $keyScoreGoodsLockedNum, $buy_num);
        }

        // 普通商品销量处理 (积分和普通商品的缓存 key 不相同)
        $keyGoodsLockedNum = $this->getLockedGoodsKey($buyInfo['goods_id'], $buyInfo['goods_sku_price_id'], 'goods');
        $locked_num = redis_cache($keyGoodsLockedNum);

        // 验证商品库存是否充足(mysql 悲观锁，此方法可靠，但如果大规模秒杀，容易mysql 死锁，请将商品添加为秒杀)
        $stock = SkuPrice::where('id', $buyInfo['current_sku_price']->id)->lock(true)->value('stock');
        if (($stock - $locked_num) < $buy_num) {
            error_stop('商品库存不足');
        }

        // 锁库存
        redis_cache()->INCRBY($keyGoodsLockedNum, $buy_num);

        // 记录已经缓存的库存的key,如果下单出现异常，将所有锁定的库存退回
        redis_cache()->HSET($keyCacheStockHash, $keyGoodsLockedNum, $buy_num);
    }


    
    /**
     * 下单中断释放锁定的库存
     *
     * @return void
     */
    public function faildStockUnLock()
    {
        // 记录缓存的 hash key
        $keyCacheStockHash = $this->getCacheStockHashKey();

        $cacheStocks = redis_cache()->HGETALL($keyCacheStockHash);

        foreach ($cacheStocks as $key => $num) {
            $this->unLockCache($key, $num);
        }

        redis_cache()->delete($keyCacheStockHash);
    }


    /**
     * 下单成功，删除锁定库存标记
     *
     * @return void
     */
    public function successDelHashKey()
    {
        // 记录缓存的 hash key
        $keyCacheStockHash = $this->getCacheStockHashKey();

        redis_cache()->delete($keyCacheStockHash);
    }


    /**
     * 库存解锁
     */
    public function stockUnLock($order)
    {
        $items = $order->items;

        foreach ($items as $key => $item) {
            $this->stockUnLockItem($order, $item);
        }
    }

    public function stockUnLockItem($order, $item)
    {
        if ($order['type'] == 'score') {
            $keyScoreGoodsLockedNum = $this->getLockedGoodsKey($item['goods_id'], $item['goods_sku_price_id'], 'score');
            $this->unLockCache($keyScoreGoodsLockedNum, $item->goods_num);
        }

        $keyGoodsLockedNum = $this->getLockedGoodsKey($item['goods_id'], $item['goods_sku_price_id'], 'goods');
        $this->unLockCache($keyGoodsLockedNum, $item->goods_num);
    }


    private function unLockCache($key, $num)
    {
        $locked_num = redis_cache()->DECRBY($key, $num);

        if ($locked_num < 0) {
            $locked_num = redis_cache()->set($key, 0);
        }
    }



    // 真实正向 减库存加销量（支付成功扣库存，加销量）
    public function forwardStockSale($order) {
        $items = OrderItem::where('order_id', $order['id'])->select();

        foreach ($items as $key => $item) {
            // 增加商品销量
            Goods::where('id', $item->goods_id)->setInc('sales', $item->goods_num);

            $skuPrice = SkuPrice::where('id', $item->goods_sku_price_id)->find();
            if ($skuPrice) {
                SkuPrice::where('id', $item->goods_sku_price_id)->setDec('stock', $item->goods_num);         // 减少库存
                SkuPrice::where('id', $item->goods_sku_price_id)->setInc('sales', $item->goods_num);         // 增加销量
                // 库存预警检测
                $this->checkStockWarning($skuPrice);
            }

            if ($item->item_goods_sku_price_id) {
                if ($order['type'] == 'score') {
                    // 积分商城商品，扣除积分规格库存
                    ScoreSkuPrice::where('id', $item->item_goods_sku_price_id)->setDec('stock', $item->goods_num);     // 减少库存
                    ScoreSkuPrice::where('id', $item->item_goods_sku_price_id)->setInc('sales', $item->goods_num);
                } else {
                    // 扣除活动库存
                    ActivitySkuPriceModel::where('id', $item->item_goods_sku_price_id)->setDec('stock', $item->goods_num);     // 减少库存
                    ActivitySkuPriceModel::where('id', $item->item_goods_sku_price_id)->setInc('sales', $item->goods_num);
                }
            }

            // 真实库存已减，库存解锁(非活动)
            if (!$item['activity_id']) {
                $this->stockUnLockItem($order, $item);
            }
        }
    }


    // 真实反向 加库存减销量（订单退全款）
    public function backStockSale($order, $items = [])
    {
        if (!$items) {
            $items = OrderItem::where('order_id', $order['id'])->select();
        }
        foreach ($items as $key => $item) {
            // 返还商品销量
            Goods::where('id', $item->goods_id)->setDec('sales', $item->goods_num);

            // 返还规格库存
            $skuPrice = SkuPrice::where('id', $item->goods_sku_price_id)->find();
            if ($skuPrice) {
                SkuPrice::where('id', $item->goods_sku_price_id)->setInc('stock', $item->goods_num);         // 返还库存
                SkuPrice::where('id', $item->goods_sku_price_id)->setDec('sales', $item->goods_num);         // 减少销量
                // 库存预警检测
                $this->checkStockWarning($skuPrice);
            }

            if ($item->item_goods_sku_price_id) {
                if ($order['type'] == 'score') {
                    // 积分商城商品，扣除积分规格库存
                    ScoreSkuPrice::where('id', $item->item_goods_sku_price_id)->setInc('stock', $item->goods_num);     // 返还库存
                    ScoreSkuPrice::where('id', $item->item_goods_sku_price_id)->setDec('sales', $item->goods_num);       // 减少销量

                } else {
                    // 扣除活动库存
                    ActivitySkuPriceModel::where('id', $item->item_goods_sku_price_id)->setInc('stock', $item->goods_num);     // 返还库存
                    ActivitySkuPriceModel::where('id', $item->item_goods_sku_price_id)->setDec('sales', $item->goods_num);      // 减少销量
                }
            }
        }
    }



    // cache 正向加销量，添加订单之前拦截
    public function cacheForwardSale($buyInfo) {
        $goods = $buyInfo['goods'];
        $activity = $goods['activity'];

        if (has_redis()) {
            $keys = ActivityRedisFacade::keysActivity([
                'goods_id' => $goods->id,
                'goods_sku_price_id' => $buyInfo['current_sku_price']->id,
            ], [
                'activity_id' => $activity['id'],
                'activity_type' => $activity['type'],
            ]);

            extract($keys);

            // 活动商品规格
            $goodsSkuPrice = Redis::HGET($keyActivity, $keyGoodsSkuPrice);
            $goodsSkuPrice = json_decode($goodsSkuPrice, true);
            // 活动商品库存
            $stock = $goodsSkuPrice['stock'] ?? 0;

            // 当前销量 + 购买数量 ，salekey 如果不存在，自动创建
            $sale = Redis::HINCRBY($keyActivity, $keySale, $buyInfo['goods_num']);

            if ($sale > $stock) {
                $sale = Redis::HINCRBY($keyActivity, $keySale, -$buyInfo['goods_num']);
                error_stop('活动商品库存不足');
            }
        }
    }


    // cache 反向减销量，取消订单/订单自动关闭 时候
    public function cacheBackSale($order) {
        $items = OrderItem::where('order_id', $order['id'])->select();

        foreach ($items as $key => $item) {
            $this->cacheBackSaleByItem($item);
        }
    }



    // 通过 OrderItem 减预库存
    private function cacheBackSaleByItem($item)
    {
        if (has_redis()) {
            $keys = ActivityRedisFacade::keysActivity([
                'goods_id' => $item['goods_id'],
                'goods_sku_price_id' => $item['goods_sku_price_id'],
            ], [
                'activity_id' => $item['activity_id'],
                'activity_type' => $item['activity_type'],
            ]);

            extract($keys);

            if (Redis::EXISTS($keyActivity) && Redis::HEXISTS($keyActivity, $keySale)) {
                $sale = Redis::HINCRBY($keyActivity, $keySale, -$item['goods_num']);
            }
    
            return true;
        }
    }


    /**
     * 获取库存锁定 key
     *
     * @param int $goods_id
     * @param int $goods_sku_price_id
     * @return string
     */
    private function getLockedGoodsKey($goods_id, $goods_sku_price_id, $order_type = 'goods')
    {
        $prefix = 'locked_goods_num:' . $order_type . ':' . $goods_id . ':' . $goods_sku_price_id;
        return $prefix;
    }


    private function getCacheStockHashKey()
    {
        $params = request()->param();
        $goodsList = $params['goods_list'] ?? [];

        $key = client_unique() . ':' . json_encode($goodsList);

        return md5($key);
    }
}
