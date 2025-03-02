<?php

namespace app\admin\controller\shopro\traits;

use app\admin\model\shopro\goods\Goods as GoodsModel;
use app\admin\model\shopro\goods\Sku as SkuModel;
use app\admin\model\shopro\goods\SkuPrice as SkuPriceModel;
use addons\shopro\traits\StockWarning as StockWarningTrait;

trait SkuPrice
{
    use StockWarningTrait;

    /**
     * 编辑规格
     *
     * @param GoodsModel $goods
     * @param array $sku
     * @param string $type
     * @return void
     */
    private function editSku($goods, $type = 'add')
    {
        if ($goods['is_sku']) {
            // 多规格
            $this->editMultSku($goods, $type);
        } else {
            $this->editSimSku($goods, $type);
        }
    }


    /**
     * 添加编辑单规格
     *
     * @param GoodsModel $goods
     * @param string $type
     * @return void
     */
    protected function editSimSku($goods, $type = 'add')
    {
        $params = $this->request->only([
            'stock', 'stock_warning', 'sn', 'weight', 'cost_price', 'original_price', 'price'
        ]);

        $data = [
            "goods_sku_ids" => null,
            "goods_sku_text" => null,
            "image" => null,
            "goods_id" => $goods->id,
            "stock" => $params['stock'] ?? 0,
            "stock_warning" => isset($params['stock_warning']) && is_numeric($params['stock_warning'])
                ? $params['stock_warning'] : null,
            "sn" => $params['sn'] ?? "",
            "weight" => isset($params['weight']) ? floatval($params['weight']) : 0,
            "cost_price" => $params['cost_price'] ?? 0,
            "original_price" => $params['original_price'] ?? 0,
            "price" => $params['price'] ?? 0,
            "status" => 'up'
        ];

        if ($type == 'edit') {
            // 查询
            $skuPrice = SkuPriceModel::where('goods_id', $goods->id)->order('id', 'asc')->find();
            if ($skuPrice) {
                // 删除多余的这个商品的其他规格以及规格项(防止多规格改为了单规格，遗留一批多余的 sku_price)
                SkuPriceModel::where('goods_id', $goods->id)->where('id', '<>', $skuPrice->id)->delete();
                SkuModel::where('goods_id', $goods->id)->delete();
            }

            unset($data['stock']);      // 移除库存(库存只能通过补货增加)
        }

        if (!isset($skuPrice) || !$skuPrice) {
            $skuPrice = new SkuPriceModel();
        }

        $skuPrice->save($data);
        if ($type == 'add') {
            // 增加补货记录
            $this->addStockLog($skuPrice, 0, $data['stock'], $type);

            // 检测库存预警
            $this->checkStockWarning($skuPrice, $type);
        }

    }


    /**
     * 添加编辑多规格
     * 
     * @param GoodsModel $goods
     * @param string $type
     * @return void
     */
    protected function editMultSku($goods, $type = 'add')
    {
        $params = $this->request->only([
            'skus', 'sku_prices'
        ]);
        $skus = $params['skus'] ?? [];
        $skuPrices = $params['sku_prices'] ?? [];

        $this->checkMultSku($skus, $skuPrices);

        // 编辑保存规格项
        $allChildrenSku = $this->saveSkus($goods, $skus, $type);

        if ($type == 'edit') {
            // 编辑旧商品，先删除老的不用的 skuPrice
            $oldSkuPriceIds = array_column($skuPrices, 'id');
            // 删除当前商品老的除了在基础上修改的skuPrice
            SkuPriceModel::where('goods_id', $goods->id)
                ->whereNotIn('id', $oldSkuPriceIds)->delete();

            // 删除失效的库存预警记录
            $this->delNotStockWarning($oldSkuPriceIds, $goods->id);
        }

        $min_key = null;    // 最小加个对应的键值
        $min_price = min(array_column($skuPrices, 'price'));        // 规格最小价格
        $originPrices = array_filter(array_column($skuPrices, 'original_price'));
        $min_original_price = $originPrices ? min($originPrices) : 0;        // 规格最小原始价格
        foreach ($skuPrices as $key => &$skuPrice) {
            $skuPrice['goods_sku_ids'] = $this->getRealSkuIds($skuPrice['goods_sku_temp_ids'], $allChildrenSku);
            $skuPrice['goods_id'] = $goods->id;
            $skuPrice['goods_sku_text'] = is_array($skuPrice['goods_sku_text']) ? join(',', $skuPrice['goods_sku_text']) : $skuPrice['goods_sku_text'];
            $skuPrice['stock_warning'] = isset($skuPrice['stock_warning']) && is_numeric($skuPrice['stock_warning'])
                ? $skuPrice['stock_warning'] : null;        // null 为关闭商品库存预警， 采用默认库存预警

            // 移除无用 属性
            if ($type == 'add') {
                // 添加直接移除 id
                unset($skuPrice['id']);
            }
            unset($skuPrice['temp_id']);                  // 前端临时 id
            unset($skuPrice['goods_sku_temp_ids']);       // 前端临时规格 id,查找真实 id 用
            unset($skuPrice['createtime'], $skuPrice['updatetime']);      // 删除时间

            $skuPriceModel = new SkuPriceModel();
            if (isset($skuPrice['id']) && $skuPrice['id']) {
                // type == 'edit' 
                unset($skuPrice['stock']);      // 编辑商品 不能编辑库存，只能通过补货
                $skuPriceModel = $skuPriceModel->find($skuPrice['id']);
            }

            if ($skuPriceModel) {
                $skuPriceModel->allowField(true)->save($skuPrice);

                if ($type == 'add') {
                    // 增加补货记录
                    $this->addStockLog($skuPriceModel, 0, $skuPrice['stock'], 'add');      // 记录库存记录

                    // 检测库存预警
                    $this->checkStockWarning($skuPriceModel, $type);
                }
            }

            if (is_null($min_key) && $min_price == $skuPrice['price']) {
                $min_key = $key;
            }
        }

        // 重新赋值最小价格和原价
        $goods->original_price = $skuPrices[$min_key]['original_price'] ?? $min_original_price;  // 最小价格规格对应的原价
        $goods->price = $min_price;
        $goods->save();
    }


    /**
     * 校验多规格是否填写完整
     * 
     * @param array $skus
     * @param array $skuPrices
     * @return void
     */
    private function checkMultSku($skus, $skuPrices) 
    {
        if (count($skus) < 1) {
            error_stop('请填写规格列表');
        }
        foreach ($skus as $key => $sku) {
            if (count($sku['children']) <= 0) {
                error_stop('主规格至少要有一个子规格');
            }

            // 验证子规格不能为空
            foreach ($sku['children'] as $k => $child) {
                if (!isset($child['name']) || empty(trim($child['name']))) {
                    error_stop('子规格不能为空');
                }
            }
        }

        if (count($skuPrices) < 1) {
            error_stop('请填写规格价格');
        }

        foreach ($skuPrices as &$price) {
            // 校验多规格属性
            $this->svalidate($price, '.sku_params');
        }
    }


    /**
     * 根据前端临时 temp_id 获取真实的数据库 id
     *
     * @param array $newGoodsSkuIds
     * @param array $allChildrenSku
     * @return string
     */
    private function getRealSkuIds($newGoodsSkuIds, $allChildrenSku)
    {
        $newIdsArray = [];
        foreach ($newGoodsSkuIds as $id) {
            $newIdsArray[] = $allChildrenSku[$id];
        }
        return join(',', $newIdsArray);
    }


    /**
     * 差异更新 规格规格项（多的删除，少的添加）
     *
     * @param GoodsModel $goods
     * @param array $skus
     * @param string $type
     * @return array
     */
    private function saveSkus($goods, $skus, $type = 'add')
    {
        $allChildrenSku = [];

        if ($type == 'edit') {
            // 删除无用老规格
            // 拿出需要更新的老规格
            $oldSkuIds = [];
            foreach ($skus as $key => $sku) {
                $oldSkuIds[] = $sku['id'];

                $childSkuIds = [];
                if ($sku['children']) {
                    // 子项 id
                    $childSkuIds = array_column($sku['children'], 'id');
                }

                $oldSkuIds = array_merge($oldSkuIds, $childSkuIds);
                $oldSkuIds = array_unique($oldSkuIds);
            }

            // 删除老的除了在基础上修改的规格项
            SkuModel::where('goods_id', $goods->id)->whereNotIn('id', $oldSkuIds)->delete();
        }

        foreach ($skus as $s1 => &$k1) {
            //添加主规格
            $current_id = $k1['id'] ?? 0;
            if ($k1['id']) {
                // 编辑
                SkuModel::where('id', $k1['id'])->update([
                    'name' => $k1['name'],
                ]);
            } else {
                // 新增
                $k1Model = new SkuModel();
                $k1Model->save([
                    'name' => $k1['name'],
                    'parent_id' => 0,
                    'goods_id' => $goods->id
                ]);
                $k1['id'] = $current_id = $k1Model->id;
            }

            foreach ($k1['children'] as $s2 => &$k2) {
                $current_child_id = $k2['id'] ?? 0;
                if ($k2['id']) {
                    // 编辑
                    SkuModel::where('id', $k2['id'])->update([
                        'name' => $k2['name'],
                    ]);
                } else {
                    // 新增
                    $k2Model = new SkuModel();
                    $k2Model->save([
                        'name' => $k2['name'],
                        'parent_id' => $current_id,
                        'goods_id' => $goods->id
                    ]);
                    $current_child_id = $k2Model->id;
                }

                $allChildrenSku[$k2['temp_id']] = $current_child_id;
                $k2['id'] = $current_child_id;
                $k2['parent_id'] = $current_id;
            }
        }

        return $allChildrenSku;
    }
}