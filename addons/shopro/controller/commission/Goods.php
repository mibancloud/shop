<?php

namespace addons\shopro\controller\commission;

use think\Request;
use app\admin\model\shopro\goods\Goods as GoodsModel;
use app\admin\model\shopro\commission\CommissionGoods;
use addons\shopro\service\commission\Goods as CommissionGoodsService;

class Goods extends Commission
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $goods_table_name = (new GoodsModel)->getQuery()->getTable();
        $list = GoodsModel
            ::hasWhere('commissionGoods', ['status' => CommissionGoods::GOODS_COMMISSION_STATUS_ON])
            ->where($goods_table_name . '.status', 'up')
            ->order('weigh desc, id desc')
            ->paginate($this->request->param('list_rows', 8))
            ->each(function ($goods) {
                $this->caculateMyCommission($goods);
            });

        $this->success("", $list);
    }

    private function caculateMyCommission($goods)
    {
        $commissionGoodsService = new CommissionGoodsService($goods->commission_goods, $goods->sku_prices[0]['id']);
        $commissionRule = $commissionGoodsService->getCommissionLevelRule($this->service->agent->level ?? 1);
        $goods->commission = $commissionGoodsService->caculateGoodsCommission($commissionRule, $goods->sku_prices[0]['price']);
    }
}
