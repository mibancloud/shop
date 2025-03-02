<?php

namespace addons\shopro\listener;

use addons\shopro\library\notify\Notify;
use app\admin\model\shopro\Admin;
use app\admin\model\shopro\goods\Goods as GoodsModel;

class Goods
{

    public function goodsStockWarning($params)
    {
        $goodsSkuPrice = $params['goodsSkuPrice'];
        $stock_warning = $params['stock_warning'];

        $goods = GoodsModel::get($goodsSkuPrice['goods_id']);

        // 商品库存不足，请及时补货
        $admins = collection(Admin::select())->filter(function ($admin) {
            return $admin->hasAccess($admin, [      // 商品列表补货 和 库存预警补货
                'shopro/goods/goods/addstock',
                'shopro/goods/stock_warning/addstock',
            ]);
        });
        if (!$admins->isEmpty()) {
            Notify::send(
                $admins,
                new \addons\shopro\notification\goods\StockWarning([
                    'goods' => $goods,
                    'goodsSkuPrice' => $goodsSkuPrice,
                    'stock_warning' => $stock_warning
                ])
            );
        }
    }
}
