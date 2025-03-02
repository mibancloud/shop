<?php

namespace addons\shopro\traits;

use app\admin\model\shopro\Config;
use app\admin\model\shopro\goods\StockWarning as StockWarningModel;
use app\admin\model\shopro\goods\StockLog as StockLogModel;
use app\admin\model\shopro\goods\SkuPrice as SkuPriceModel;

/**
 * 库存预警
 */
trait StockWarning
{
    /**
     * 获取全局库存配置
     *
     * @return void
     */
    public function getStockConfig() 
    {
        $stock_warning = Config::getConfigField('shop.goods.stock_warning');

        return intval($stock_warning);
    }


    /**
     * 获取库存预警阀值
     *
     * @param [type] $goodsSkuPrice
     * @return void
     */
    public function getStockWarning($goodsSkuPrice) 
    {
        if (!is_null($goodsSkuPrice['stock_warning'])) {
            // 商品存在库存预警值
            $stock_warning = $goodsSkuPrice['stock_warning'];
        } else {
            // 默认库存预警值
            $stock_warning = $this->getStockConfig();
        }

        return $stock_warning;
    }



    /**
     * 检测库存是否低于预警阀值，并且记录
     *
     * @param [type] $goodsSkuPrice
     * @return void
     */
    public function checkStockWarning($goodsSkuPrice, $type = 'edit') 
    {
        $stock_warning = $this->getStockWarning($goodsSkuPrice);
        // 读取系统配置库存预警值
        if ($goodsSkuPrice['stock'] < $stock_warning) {
            // 增加库存不足记录
            $this->addStockWarning($goodsSkuPrice, $stock_warning);
        } else {
            if ($type == 'edit') {
                // 如果编辑了并且库存大于预警值需要检查并把记录删除
                $this->delStockWarning($goodsSkuPrice['id'], $goodsSkuPrice['goods_id']);
            }
        }
    }


    /**
     * 检测这个商品的所有规格库存预警
     *
     * @param [type] $goodsSkuPrices
     * @return void
     */
    public function checkAllStockWarning($goodsSkuPrices, $type = 'add') 
    {
        foreach ($goodsSkuPrices as $key => $goodsSkuPrice) {
            $this->checkStockWarning($goodsSkuPrice, $type);
        }
    }


    /**
     * 记录库存低于预警值
     *
     * @param [type] $goodsSkuPrice
     * @param [type] $stock_warning
     * @return void
     */
    public function addStockWarning($goodsSkuPrice, $stock_warning) 
    {
        $stockWarning = StockWarningModel::where('goods_sku_price_id', $goodsSkuPrice['id'])
                                    ->where('goods_id', $goodsSkuPrice['goods_id'])->find();

        if ($stockWarning) {
            if ($stockWarning['stock_warning'] != $stock_warning
                || $stockWarning->goods_sku_text != $goodsSkuPrice['goods_sku_text']
                ) {
                $stockWarning->goods_sku_text = is_array($goodsSkuPrice['goods_sku_text']) ? join(',', $goodsSkuPrice['goods_sku_text']) : $goodsSkuPrice['goods_sku_text'];;
                $stockWarning->stock_warning = $stock_warning;

                $stockWarning->save();
            }
        } else {
            $stockWarning = new StockWarningModel();

            $stockWarning->goods_id = $goodsSkuPrice['goods_id'];
            $stockWarning->goods_sku_price_id = $goodsSkuPrice['id'];
            $stockWarning->goods_sku_text = is_array($goodsSkuPrice['goods_sku_text']) ? join(',', $goodsSkuPrice['goods_sku_text']) : $goodsSkuPrice['goods_sku_text'];
            $stockWarning->stock_warning = $stock_warning;
            $stockWarning->save();
        }

        // 库存预警变动事件
        $data = ['goodsSkuPrice' => $goodsSkuPrice, 'stock_warning' => $stock_warning];
        \think\Hook::listen('goods_stock_warning', $data);
        
        return $stockWarning;
    }


    /**
     * 删除规格预警，比如：多规格编辑之后，作废的规格预警，补充库存之后的规格预警
     *
     * @param array $ids
     * @param integer $goods_id
     * @return void
     */
    public function delStockWarning($goodsSkuPriceIds = [], $goods_id = 0) 
    {
        $goodsSkuPriceIds = is_array($goodsSkuPriceIds) ? $goodsSkuPriceIds : [$goodsSkuPriceIds];

        StockWarningModel::destroy(function ($query) use ($goods_id, $goodsSkuPriceIds) {
            $query->where('goods_id', $goods_id)
                    ->where('goods_sku_price_id', 'in', $goodsSkuPriceIds);
        });
    }


    /**
     * 删除商品除了这些规格之外的规格预警
     *
     * @param array $ids
     * @param integer $goods_id
     * @return void
     */
    public function delNotStockWarning($goodsSkuPriceIds = [], $goods_id = 0) 
    {
        $goodsSkuPriceIds = is_array($goodsSkuPriceIds) ? $goodsSkuPriceIds : [$goodsSkuPriceIds];

        StockWarningModel::destroy(function ($query) use ($goods_id, $goodsSkuPriceIds) {
            $query->where('goods_id', $goods_id)
                    ->where('goods_sku_price_id', 'not in', $goodsSkuPriceIds);
        });
    }



    /**
     * 补货
     *
     * @param think\model $goodsSkuPrice
     * @param integer $stock
     * @return void
     */
    public function addStockToSkuPrice($goodsSkuPrice, $stock, $type) 
    {
        $before = $goodsSkuPrice->stock;

        // 补充库存
        $goodsSkuPrice->setInc('stock', $stock);

        // 添加补货记录
        $this->addStockLog($goodsSkuPrice, $before, $stock, $type);

        // 检测库存预警
        $goodsSkuPrice = SkuPriceModel::find($goodsSkuPrice->id);       // 重新获取 skuPrice
        $this->checkStockWarning($goodsSkuPrice);
    }



    /**
     * 添加补货记录
     *
     * @param array $goodsSkuPrice
     * @param int $stock
     * @return void
     */
    public function addStockLog($goodsSkuPrice, $before, $stock, $type = 'add') 
    {
        $admin = auth_admin();
        $stockWarning = new StockLogModel();

        $stockWarning->goods_id = $goodsSkuPrice['goods_id'];
        $stockWarning->admin_id = $admin['id'];
        $stockWarning->goods_sku_price_id = $goodsSkuPrice['id'];
        $stockWarning->goods_sku_text = is_array($goodsSkuPrice['goods_sku_text']) ? join(',', $goodsSkuPrice['goods_sku_text']) : $goodsSkuPrice['goods_sku_text'];
        $stockWarning->before = $before;
        $stockWarning->stock = $stock;
        
        switch ($type) {
            case 'add': 
                $msg = '添加商品';
                break;
            case 'goods':
                $msg = '商品列表补库存';
                break;
            case 'stock_warning':
                $msg = '库存预警补库存';
                break;
            default :
                $msg = '';
                break;
        }

        $stockWarning->msg = $msg;
        $stockWarning->save();
    }
}
