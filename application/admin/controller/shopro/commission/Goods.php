<?php

namespace app\admin\controller\shopro\commission;

use app\admin\controller\shopro\Common;
use app\admin\model\shopro\commission\CommissionGoods as CommissionGoodsModel;
use app\admin\model\shopro\goods\Goods as GoodsModel;
use think\Db;

class Goods extends Common
{
    protected $model = null;
    protected $goodsModel;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new CommissionGoodsModel();
        $this->goodsModel = new GoodsModel();
    }

    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $data = $this->goodsModel->sheepFilter()->with('commission_goods')->paginate($this->request->param('list_rows', 10));
        $this->success('分销商品列表', null, $data);
    }

    /**
     * 详情
     *
     * @param  $id
     */
    public function detail($id)
    {
        $goodsList = collection(GoodsModel::with(['commission_goods'])->whereIn('id', $id)->select())->each(function ($goods) {
            $goods->skus = $goods->skus;
            $goods->sku_prices = $goods->sku_prices;
        });

        $config = sheep_config('shop.commission');
        $this->success('分销商品详情', null, [
            'goods' => $goodsList,
            'config' => $config
        ]);
    }

    /**
     * 设置佣金(支持批量)
     *
     * @param  $id
     */
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        // 接受全部参数
        $params = $this->request->only(['status', 'self_rules', 'commission_order_status', 'commission_config', 'commission_rules']);

        $result = Db::transaction(function () use ($id, $params) {
            $count = 0;
            $ids = explode(',', $id);

            foreach ($ids as $goods_id) {
                if ($row = $this->model->get($goods_id)) {
                    $row->save($params);
                } else {
                    $model = new CommissionGoodsModel();
                    $params['goods_id'] = $goods_id;
                    $model->save($params);
                }
                $count++;
            }

            return $count;
        });

        if ($result) {
            $this->success('更新成功', null, $result);
        } else {
            $this->error('更新失败');
        }
    }
}
