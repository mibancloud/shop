<?php

namespace app\admin\controller\shopro\goods;

use app\admin\controller\shopro\Common;
use think\Db;
use app\admin\model\shopro\goods\SkuPrice as SkuPriceModel;
use app\admin\model\shopro\goods\StockWarning as StockWarningModel;
use addons\shopro\traits\StockWarning as StockWarningTrait;

/**
 * 库存预警
 */
class StockWarning extends Common
{
    use StockWarningTrait;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new StockWarningModel;
    }


    /**
     * 库存预警列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $skuPriceTableName = (new SkuPriceModel())->getQuery()->getTable();
        $stockWarnings = $this->model->sheepFilter()->alias('g')->with(['goods' => function ($query) {
                $query->removeOption('soft_delete');
            }])
            ->join($skuPriceTableName . ' sp', 'g.goods_sku_price_id = sp.id', 'left')
            ->field('g.*,sp.stock')
            ->paginate($this->request->param('list_rows', 10));

        $warning_total = $this->model->sheepFilter(false, function ($filters) {
                $filters['stock_type'] = 'no_enough';
                return $filters;
            })->alias('g')
            ->join($skuPriceTableName . ' sp', 'g.goods_sku_price_id = sp.id', 'left')
            ->field('g.*,sp.stock')
            ->count();
        $over_total = $this->model->sheepFilter(false, function ($filters) {
                $filters['stock_type'] = 'over';
                return $filters;
            })->alias('g')
            ->join($skuPriceTableName . ' sp', 'g.goods_sku_price_id = sp.id', 'left')
            ->field('g.*,sp.stock')
            ->count();

        $result = [
            'rows' => $stockWarnings,
            'warning_total' => $warning_total,
            'over_total' => $over_total,
        ];

        $this->success('获取成功', null, $result);
    }




    /**
     * 补货
     *
     * @param [type] $ids
     * @param [type] $stock
     * @return void
     */
    public function addStock ($id) {
        if ($this->request->isAjax()) {
            $params = $this->request->only(['stock']);
            $this->svalidate($params, ".add");

            $stockWarning = $this->model->with(['sku_price'])->where('id', $id)->find();
            if (!$stockWarning) {
                $this->error(__('No Results were found'));
            }
            if (!$stockWarning->sku_price) {
                $this->error('库存规格不存在');
            }

            Db::transaction(function () use ($stockWarning, $params) {
                // 补货
                $this->addStockToSkuPrice($stockWarning->sku_price, $params['stock'], 'stock_warning');
            });

            $this->success('补货成功');
        }

        return $this->view->fetch();

    }


    public function recyclebin()
    {
        if ($this->request->isAjax()) {
            $skuPriceTableName = (new SkuPriceModel())->getQuery()->getTable();
            $stockWarnings = $this->model->onlyTrashed()->sheepFilter()->alias('g')->with(['goods' => function ($query) {
                    $query->removeOption('soft_delete');
                }])
                ->join($skuPriceTableName . ' sp', 'g.goods_sku_price_id = sp.id', 'left')
                ->field('g.*,sp.stock')
                ->paginate($this->request->param('list_rows', 10));

            $this->success('获取成功', null, $stockWarnings);
        }

        return $this->view->fetch();
    }
}
