<?php

namespace app\admin\controller\shopro\app;

use think\Db;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\app\ScoreSkuPrice;
use app\admin\model\shopro\goods\Goods as GoodsModel;
use app\admin\model\shopro\goods\Sku as SkuModel;
use app\admin\model\shopro\goods\SkuPrice as SkuPriceModel;

/**
 * 积分商城
 */
class ScoreShop extends Common
{

    protected $noNeedRight = ['skuPrices', 'select', 'skus'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new ScoreSkuPrice;
        $this->goodsModel = new GoodsModel();
    }


    /**
     * 积分商城商品列表
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $scoreGoodsIds = $this->model->group('goods_id')->column('goods_id');

        $scoreGoods = $this->goodsModel->sheepFilter()->with(['score_sku_prices'])
            ->whereIn('id', $scoreGoodsIds)
            ->paginate($this->request->param('list_rows', 10))->each(function($goods) {
                $goods->score_price = $goods->score_price;
                $goods->score_sales = $goods->score_sales;
                $goods->score_stock = $goods->score_stock;
            });

        $this->success('获取成功', null, $scoreGoods);
    }


    /**
     * skuPrices列表
     */
    public function skuPrices($goods_id)
    {
        $skuPrices = $this->model->up()->with(['sku_price'])->where('goods_id', $goods_id)->select();
        $skuPrices = collection($skuPrices)->each(function ($skuPrice) {
            $skuPrice->goods_sku_ids = $skuPrice->goods_sku_ids;
            $skuPrice->goods_sku_text = $skuPrice->goods_sku_text;
            $skuPrice->image = $skuPrice->image;
            $skuPrice->score_price = $skuPrice->score_price;
        });

        $this->success('获取成功', null, $skuPrices);
    }



    /**
     * 添加积分商城商品
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only([
            'goods_id', 'sku_prices'
        ]);
        $this->svalidate($params, ".add");

        // 检查是否已经是积分商品了
        $count = $this->model->where('goods_id', $params['goods_id'])->count();
        if ($count) {
            error_stop('该商品已经是积分商城商品了');
        }

        $statuses = array_column($params['sku_prices'], 'status');
        if (!in_array('up', $statuses)) {
            error_stop('请至少选择一个规格');
        }

        Db::transaction(function () use ($params) {
            $this->editSkuPrices($params['goods_id'], $params);
        });
        $this->success('保存成功');
    }


    public function skus($goods_id)
    {
        $skus = SkuModel::with('children')->where('goods_id', $goods_id)->where('parent_id', 0)->select();
        $skuPrices = SkuPriceModel::with(['score_sku_price'])->where('goods_id', $goods_id)->select();

        //编辑
        $scoreSkuPrices = [];
        foreach ($skuPrices as $k => $skuPrice) {
            $scoreSkuPrices[$k] = $skuPrice->score_sku_price ? : [];
            // 活动规格数据初始化
            if (!$scoreSkuPrices[$k]) {
                $scoreSkuPrices[$k]['id'] = 0;
                $scoreSkuPrices[$k]['status'] = 'down';
                $scoreSkuPrices[$k]['price'] = '';
                $scoreSkuPrices[$k]['score'] = '';
                $scoreSkuPrices[$k]['stock'] = '';
                $scoreSkuPrices[$k]['goods_sku_price_id'] = $skuPrice->id;
            }
        }

        $this->success('获取成功', null, [
            'skus' => $skus,
            'sku_prices' => $skuPrices,
            'score_sku_prices' => $scoreSkuPrices
        ]);
    }


    /**
     * 编辑积分商城商品
     */
    public function edit($goods_id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->only([
            'sku_prices'
        ]);
        $this->svalidate($params, ".edit");

        $statuses = array_column($params['sku_prices'], 'status');
        if (!in_array('up', $statuses)) {
            error_stop('请至少选择一个规格');
        }

        Db::transaction(function () use ($goods_id, $params) {
            $this->editSkuPrices($goods_id, $params);
        });
        $this->success('更新成功');
    }


    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $type = $this->request->param('type', 'page');
        $scoreGoodsIds = $this->model->group('goods_id')->column('goods_id');
        
        $scoreGoods = $this->goodsModel->sheepFilter()->with(['score_sku_prices'])
            ->whereIn('id', $scoreGoodsIds);
        
        if ($type == 'select') {
            // 普通结果
            $scoreGoods = $scoreGoods->select();
            $scoreGoods = collection($scoreGoods);
        } else {
            // 分页结果
            $scoreGoods = $scoreGoods->paginate($this->request->param('list_rows', 10));
        }

        $scoreGoods = $scoreGoods->each(function ($goods) {
            $goods->score_price = $goods->score_price;
            $goods->score_sales = $goods->score_sales;
            $goods->score_stock = $goods->score_stock;
        });

        $this->success('获取成功', null, $scoreGoods);
    }



    /**
     * 删除积分商城商品
     *
     * @param string $id 要删除的积分商城商品 id
     * @return void
     */
    public function delete($goods_id)
    {
        if (empty($goods_id)) {
            $this->error(__('Parameter %s can not be empty', 'goods_id'));
        }

        $goodsIds = explode(',', $goods_id);
        $list = $this->model->whereIn('goods_id', $goodsIds)->select();
        $result = Db::transaction(function () use ($list) {
            $count = 0;
            foreach ($list as $item) {
                $count += $item->delete();
            }

            return $count;
        });

        if ($result) {
            $this->success('删除成功', null, $result);
        } else {
            $this->error(__('No rows were deleted'));
        }
    }



    public function recyclebin()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $scoreGoodsIds = $this->model->onlyTrashed()->group('goods_id')->column('goods_id');
        $scoreGoods = $this->goodsModel->sheepFilter()->with(['del_score_sku_prices'])
            ->whereIn('id', $scoreGoodsIds)
            ->paginate($this->request->param('list_rows', 10))->each(function ($skuPrice) {
                $deleteTimes = collection($skuPrice->del_score_sku_prices)->column('deletetime');
                $skuPrice->deletetime = $deleteTimes ? max($deleteTimes) : null;     // 取用积分规格的删除时间
            });

        $this->success('获取成功', null, $scoreGoods);
    }


    /**
     * 还原(支持批量)
     *
     * @param  $id
     * @return \think\Response
     */
    public function restore($id = null)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'goods_id'));
        }
        
        $goodsIds = explode(',', $id);
        Db::transaction(function () use ($goodsIds) {
            foreach ($goodsIds as $goods_id) {
                $count = $this->model->where('goods_id', $goods_id)->count();
                if ($count) {
                    error_stop('商品 ID 为 ' . $goods_id . ' 的商品已经是积分商品了，不可还原');
                }

                $list = $this->model->onlyTrashed()->whereIn('goods_id', $goods_id)->select();
                foreach ($list as $goods) {
                    $goods->restore();
                }
            }
        });

        $this->success('还原成功');
    }


    /**
     * 销毁(支持批量)
     *
     * @param  $id
     * @return \think\Response
     */
    public function destroy($id = null)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'goods_id'));
        }

        $goodsIds = explode(',', $id);
        Db::transaction(function () use ($goodsIds) {
            if (!in_array('all', $goodsIds)) {
                foreach ($goodsIds as $goods_id) {
                    $list = $this->model->onlyTrashed()->whereIn('goods_id', $goods_id)->select();
                    foreach ($list as $goods) {
                        $goods->delete(true);
                    }
                }
            } else {
                $list = $this->model->onlyTrashed()->select();
                foreach ($list as $goods) {
                    $goods->delete(true);
                }
            }
        });

        $this->success('销毁成功');
    }


    private function editSkuPrices($goods_id, $params) 
    {
        //下架全部规格
        $this->model->where('goods_id', $goods_id)->update(['status' => 'down']);

        foreach ($params['sku_prices'] as $key => $skuPrice) {
            if ($skuPrice['id'] == 0) {
                unset($skuPrice['id']);
            }
            unset($skuPrice['sales']);  //不更新销量
            unset($skuPrice['createtime'], $skuPrice['updatetime'], $skuPrice['deletetime']);  // 不手动更新时间
            $skuPrice['goods_id'] = $goods_id;

            $model = new ScoreSkuPrice;
            if (isset($skuPrice['id'])) {
                $model = $this->model->find($skuPrice['id']);
                $model = $model ? : new ScoreSkuPrice;
            }

            $model->save($skuPrice);
        }
    }
}
