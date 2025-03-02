<?php

namespace app\admin\controller\shopro\goods;

use app\admin\controller\shopro\Common;
use app\admin\model\shopro\goods\Goods as GoodsModel;
use app\admin\model\shopro\goods\Sku as SkuModel;
use app\admin\model\shopro\goods\SkuPrice as SkuPriceModel;
use app\admin\model\shopro\goods\StockWarning as StockWarningModel;
use app\admin\model\shopro\activity\Activity as ActivityModel;
use app\admin\controller\shopro\traits\SkuPrice as SkuPriceTrait;
use addons\shopro\traits\StockWarning as StockWarningTrait;
use addons\shopro\service\goods\GoodsService;
use think\Db;

/**
 * 商品管理
 */
class Goods extends Common
{

    use SkuPriceTrait, StockWarningTrait;

    protected $noNeedRight = ['getType', 'select', 'activitySelect'];

    /**
     * 商品模型对象
     * @var \app\admin\model\shopro\goods\Goods
     */
    protected $model = null;
    protected $activityModel = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new GoodsModel;
        $this->activityModel = new ActivityModel;
    }


    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $goodsTableName = $this->model->getQuery()->getTable();

        $goods = $this->model->sheepFilter()->with(['max_sku_price']);

        // 聚合库存 (包含下架的规格)
        $skuSql = SkuPriceModel::field('sum(stock) as stock, goods_id as sku_goods_id')->group('goods_id')->buildSql();
        $goods = $goods->join([$skuSql => 'sp'], $goodsTableName . '.id = sp.sku_goods_id', 'left')
            ->field("$goodsTableName.*, sp.stock")       // ,score.*
            ->paginate($this->request->param('list_rows', 10))->each(function ($goods) {
                // 获取活动信息
                $goods->activities = $goods->activities;
                $goods->promos = $goods->promos;

                $data_type = request()->param('data_type', '');       // 特殊 type 需要处理的数据
                if ($data_type == 'score_shop') {
                    $goods->is_score_shop = $goods->is_score_shop;
                }
            });

        $this->success('获取成功', null, $goods);
    }


    // 获取数据类型
    public function getType()
    {
        $activityTypes = $this->activityModel->typeList();
        $statusList = $this->model->statusList();

        $result = [
            'activity_type' => $activityTypes,
            'status' => $statusList
        ];

        $data = [];
        foreach ($result as $key => $list) {
            $data[$key][] = ['name' => '全部', 'type' => 'all'];

            foreach ($list as $k => $v) {
                $data[$key][] = [
                    'name' => $v,
                    'type' => $k
                ];
            }
        }

        $this->success('获取成功', null, $data);
    }




    /**
     * 添加
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only([
            'type', 'title', 'subtitle', 'category_ids', 'image', 'images', 'image_wh', 'params',
            'original_price', 'price', 'is_sku', 'limit_type', 'limit_num', 'sales_show_type',
            'stock_show_type', 'show_sales', 'service_ids', 'dispatch_type', 'dispatch_id', 'is_offline', 'status', 'weigh',
        ]);         // likes, views, sales,
        $params['content'] = $this->request->param('content', '', null);      // content 不经过全局过滤
        $this->svalidate($params, ".add");
        if (!$params['is_sku']) {
            // 校验单规格属性
            $sku_params = $this->request->only(['stock', 'stock_warning', 'sn', 'weight', 'cost_price', 'original_price', 'price']);
            $this->svalidate($sku_params, '.sku_params');
        }

        $data = Db::transaction(function () use ($params) {
            $this->model->save($params);

            $this->editSku($this->model, 'add');
        });
        $this->success('保存成功', null, $data);
    }




    /**
     * 详情
     *
     * @param  $id
     * @return \think\Response
     */
    public function detail($id)
    {
        $goods = $this->model->where('id', $id)->find();
        if (!$goods) {
            $this->error(__('No Results were found'));
        }
        $goods->category_ids_arr = $goods->category_ids_arr;

        if ($goods->is_sku) {
            $goods->skus = $goods->skus;
            $goods->sku_prices = $goods->sku_prices;
        } else {
            // 将单规格的部分数据直接放到 row 上
            $goodsSkuPrice = SkuPriceModel::where('goods_id', $id)->order('id', 'asc')->find();

            $goods->stock = $goodsSkuPrice->stock;
            $goods->sn = $goodsSkuPrice->sn;
            $goods->weight = $goodsSkuPrice->weight;
            $goods->stock_warning = $goodsSkuPrice->stock_warning;
            $goods->cost_price = $goodsSkuPrice->cost_price;
        }

        $content = $goods['content'];
        $goods = $goods->toArray();
        $goods['content'] = $content;
        $this->success('保存成功', null, $goods);
    }


    /**
     * 编辑(支持批量)
     */
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->only([
            'type', 'title', 'subtitle', 'image', 'images', 'image_wh', 'params',
            'original_price', 'price', 'is_sku', 'limit_type', 'limit_num', 'sales_show_type',
            'stock_show_type', 'show_sales', 'service_ids', 'dispatch_type', 'dispatch_id', 'is_offline', 'status', 'weigh',
        ]);         // likes, views, sales,
        $this->request->has('content') && $params['content'] = $this->request->param('content', '', null);      // content 不经过全局过滤
        $this->svalidate($params);
        isset($params['is_sku']) && $params['category_ids'] = $this->request->param('category_ids', '');        // 分类不判空
        if (isset($params['is_sku']) && !$params['is_sku']) {
            // 校验单规格属性
            $sku_params = $this->request->only(['stock_warning', 'sn', 'weight', 'cost_price', 'original_price', 'price']);
            $this->svalidate($sku_params, 'sku_params');
        }

        $id = explode(',', $id);

        $items = $this->model->whereIn('id', $id)->select();
        Db::transaction(function () use ($items, $params) {
            foreach ($items as $goods) {
                $goods->save($params);

                if (isset($params['is_sku'])) {
                    // 编辑商品（如果没有 is_sku 就是批量编辑上下架等）
                    $this->editSku($goods, 'edit');
                }
            }
        });

        $this->success('更新成功', null);
    }


    public function addStock($id) 
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $goods = $this->model->where('id', $id)->find();
        if (!$goods) {
            $this->error(__('No Results were found'));
        }
        if ($goods->is_sku) {
            // 多规格
            $skuPrices = $this->request->post('sku_prices/a', []);
            foreach ($skuPrices as $skuPrice) {
                if (isset($skuPrice['add_stock']) && $skuPrice['add_stock'] != 0 && $skuPrice['id']) {
                    $skuPriceModel = SkuPriceModel::where('goods_id', $id)->order('id', 'asc')->find($skuPrice['id']);
                    if ($skuPriceModel) {
                        Db::transaction(function () use ($skuPriceModel, $skuPrice) {
                            $this->addStockToSkuPrice($skuPriceModel, $skuPrice['add_stock'], 'goods');
                        });
                    }
                }
            }
        } else {
            $add_stock = $this->request->param('add_stock', 0);
            $skuPriceModel = SkuPriceModel::where('goods_id', $id)->order('id', 'asc')->find();

            if ($skuPriceModel) {
                Db::transaction(function () use ($skuPriceModel, $add_stock) {
                    $this->addStockToSkuPrice($skuPriceModel, $add_stock, 'goods');
                });
            }
        }

        $this->success('补货成功');
    }



    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $type = $this->request->param('type', 'page');
        $goodsTableName = $this->model->getQuery()->getTable();

        $goods = $this->model->sheepFilter()->with(['max_sku_price']);

        // 聚合库存 (包含下架的规格)
        $skuSql = SkuPriceModel::field('sum(stock) as stock, goods_id as sku_goods_id')->group('goods_id')->buildSql();
        $goods = $goods->join([$skuSql => 'sp'], $goodsTableName . '.id = sp.sku_goods_id', 'left')
            ->field("$goodsTableName.*, sp.stock");       // ,score.*

        if ($type == 'select') {
            // 普通结果
            $goods = collection($goods->select());
        } else {
            // 分页结果
            $goods = $goods->paginate($this->request->param('list_rows', 10));
        }

        $goods = $goods->each(function ($goods) {
            // 获取活动信息
            $goods->activities = $goods->activities;
            $goods->promos = $goods->promos;

            $data_type = $this->request->param('data_type', '');       // 特殊 type 需要处理的数据
            if ($data_type == 'score_shop') {
                $goods->is_score_shop = $goods->is_score_shop;
            }
        });

        $this->success('获取成功', null, $goods);
    }



    /**
     * 获取指定活动相关商品
     *
     * @param Request $request
     * @return void
     */
    public function activitySelect()
    {
        $activity_id = $this->request->param('activity_id');
        $need_buyers = $this->request->param('need_buyers', 0);      // 需要查询哪些人在参与活动
        $activity = $this->activityModel->where('id', $activity_id)->find();
        if (!$activity) {
            $this->error(__('No Results were found'));
        }
        $goodsIds = $activity->goods_ids ? explode(',', $activity->goods_ids) : [];

        // 存一下，获取器获取指定活动的时候会用到
        foreach ($goodsIds as $id) {
            session('goods-activity_id:' . $id, $activity_id);
        }
        $service = new GoodsService(function ($goods) use ($need_buyers) {
            if ($need_buyers) {
                $goods->buyers = $goods->buyers;
            }
            $goods->activity = $goods->activity;
            return $goods;
        });

        $goods = $service->activity($activity_id)->whereIds($goodsIds)->show()->select();
        $goods = collection($goods)->toArray();     // 可以将里面的单个 model也转为数组
        foreach ($goods as &$gd) {
            unset($gd['new_sku_prices'], $gd['activity']);
        }
        $this->success('获取成功', null, $goods);
    }




    /**
     * 删除(支持批量)
     *
     * @param  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

        $list = $this->model->where('id', 'in', $id)->select();
        $result = Db::transaction(function () use ($list) {
            $count = 0;
            foreach ($list as $item) {
                // 删除相关库存预警记录
                StockWarningModel::destroy(function ($query) use ($item) {
                    $query->where('goods_id', $item->id);
                });
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

        $goods = $this->model->onlyTrashed()->sheepFilter()->paginate($this->request->param('list_rows', 10));
        $this->success('获取成功', null, $goods);
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
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

        $items = $this->model->onlyTrashed()->where('id', 'in', $id)->select();
        $result = Db::transaction(function () use ($items) {
            $count = 0;
            foreach ($items as $item) {
                $count += $item->restore();
            }

            return $count;
        });

        if ($result) {
            $this->success('还原成功', null, $result);
        } else {
            $this->error(__('No rows were updated'));
        }
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
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

        if ($id !== 'all') {
            $items = $this->model->onlyTrashed()->whereIn('id', $id)->select();
        } else {
            $items = $this->model->onlyTrashed()->select();
        }
        $result = Db::transaction(function () use ($items) {
            $count = 0;
            foreach ($items as $goods) {
                // 删除商品相关的规格，规格记录
                SkuModel::where('goods_id', $goods->id)->delete();
                SkuPriceModel::where('goods_id', $goods->id)->delete();

                // 删除商品
                $count += $goods->delete(true);
            }
            return $count;
        });

        if ($result) {
            $this->success('销毁成功', null, $result);
        }
        $this->error('销毁失败');
    }
}
