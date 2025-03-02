<?php

namespace app\admin\controller\shopro\goods;

use app\admin\controller\shopro\Common;
use think\Db;
use app\admin\model\shopro\goods\SkuPrice as SkuPriceModel;
use app\admin\model\shopro\goods\StockLog as StockLogModel;
use addons\shopro\library\Operator;

/**
 * 补库存记录
 */
class StockLog extends Common
{

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new StockLogModel;
    }


    /**
     * 库存补货列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $skuPriceTableName = (new SkuPriceModel())->getQuery()->getTable();
        $stockLogs = $this->model->sheepFilter()->alias('g')->with(['goods' => function ($query) {
                $query->removeOption('soft_delete');
            }, 'oper'])
            ->join($skuPriceTableName . ' sp', 'g.goods_sku_price_id = sp.id', 'left')
            ->field('g.*,sp.stock as total_stock')
            ->paginate($this->request->param('list_rows', 10))->toArray();
                    // 解析操作人信息
        foreach ($stockLogs['data'] as &$log) {
            $log['oper'] = Operator::info('admin', $log['oper'] ?? null);
        }
        $this->success('获取成功', null, $stockLogs);
    }
}
