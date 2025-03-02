<?php

namespace app\admin\controller\shopro\goods;

use app\admin\controller\shopro\Common;
use think\Db;
use app\admin\model\shopro\goods\SkuPrice as SkuPriceModel;

class SkuPrice extends Common
{

    protected $noNeedRight = ['index'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new SkuPriceModel;
    }

    /**
     * skuPrices列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $goods_id = $this->request->param('goods_id');
        $skuPrices = $this->model->where('goods_id', $goods_id)->select();

        $this->success('获取成功', null, $skuPrices);
    }



    /**
     * skuPrices编辑
     *
     * @param  $id
     * @return \think\Response
     */
    public function edit($id = null)
    {
        $params = $this->request->only([
            'status',
        ]);

        $id = explode(',', $id);
        $items = $this->model->whereIn('id', $id)->select();
        Db::transaction(function () use ($items, $params) {
            foreach ($items as $skuPrice) {
                $skuPrice->save($params);
            }
        });

        $this->success('更新成功');
    }
}
