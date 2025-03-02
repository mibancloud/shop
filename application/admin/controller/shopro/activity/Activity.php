<?php

namespace app\admin\controller\shopro\activity;

use think\Db;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\activity\Activity as ActivityModel;
use app\admin\model\shopro\goods\Goods as GoodsModel;
use app\admin\model\shopro\goods\Sku as SkuModel;
use app\admin\model\shopro\goods\SkuPrice as SkuPriceModel;
use addons\shopro\library\activity\Activity as ActivityManager;
use addons\shopro\library\activity\traits\CheckActivity;
use addons\shopro\facade\Activity as ActivityFacade;

/**
 * 活动管理
 */
class Activity extends Common
{

    use CheckActivity;

    protected $noNeedRight = ['getType', 'select', 'skus'];
    
    protected $manager = null;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new ActivityModel;
        $this->manager = ActivityFacade::instance();
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
        $type = $this->request->param('type', null);

        if (!$this->request->isAjax()) {
            if ($type) {
                return $this->view->fetch('shopro/activity/activity/index');
            }

            return $this->view->fetch('shopro/activity/activity/activity');
        }

        $activities = $this->model->sheepFilter()->where('type', $type)->paginate(request()->param('list_rows', 10))->toArray();

        $items = $activities['data'];

        // 关联活动的商品
        $goodsIds = array_values(array_filter(array_column($items, 'goods_ids')));
        $goodsIdsArr = [];
        foreach ($goodsIds as $ids) {
            $idsArr = explode(',', $ids);
            $goodsIdsArr = array_merge($goodsIdsArr, $idsArr);
        }
        $goodsIdsArr = array_values(array_filter(array_unique($goodsIdsArr)));
        if ($goodsIdsArr) {
            // 查询商品
            $goods = GoodsModel::where('id', 'in', $goodsIdsArr)->select();
            $goods = array_column($goods, null, 'id');
        }
        foreach ($items as $key => $activity) {
            $items[$key]['goods'] = [];
            if ($activity['goods_ids']) {
                $idsArr = explode(',', $activity['goods_ids']);
                foreach ($idsArr as $id) {
                    if (isset($goods[$id])) {
                        $items[$key]['goods'][] = $goods[$id];
                    }
                }
            }
        }

        $activities['data'] = $items;

        $this->success('获取成功', null, $activities);
    }


    // 获取数据类型
    public function getType()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $typeList = $this->model->typeList();

        $result = [
            'type_list' => $typeList,
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
            'title', 'goods_ids', 'type', 'prehead_time', 'start_time', 'end_time',
            'rules', 'richtext_id', 'richtext_title', 'goods_list'
        ]);
        if (isset($params['goods_list'])) {
            $params['goods_list'] = json_decode($params['goods_list'], true);
        }
        $this->svalidate($params, ".add");

        Db::transaction(function () use ($params) {
            $this->manager->save($params);
        });

        $this->success('保存成功');
    }




    /**
     * 详情
     *
     * @param  $id
     * @return \think\Response
     */
    public function detail($id)
    {
        $activity = $this->model->where('id', $id)->find();
        if (!$activity) {
            $this->error(__('No Results were found'));
        }
        $activity->goods_list = $activity->goods_list;
        $activity->rules = $activity->rules;

        $this->success('获取成功', null, $activity);
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
            'title', 'goods_ids', 'prehead_time', 'start_time', 'end_time',
            'rules', 'richtext_id', 'richtext_title', 'goods_list'
        ]);
        if (isset($params['goods_list'])) {
            $params['goods_list'] = json_decode($params['goods_list'], true);
        }
        $this->svalidate($params);

        $id = explode(',', $id);
        $items = $this->model->whereIn('id', $id)->select();

        Db::transaction(function () use ($items, $params) {
            foreach ($items as $activity) {
                $this->manager->update($activity, $params);
            }
        });

        $this->success('更新成功');
    }


    /**
     * 获取活动商品规格并且初始化
     */
    public function skus()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->param();
        $id = $params['id'] ?? 0;
        $goods_id = $params['goods_id'] ?? 0;
        $activity_type = $params['activity_type'] ?? '';
        $start_time = $params['start_time'] ?? '';
        $end_time = $params['end_time'] ?? '';
        $prehead_time = $params['prehead_time'] ?? '';

        if ($start_time && $end_time && $activity_type) {
            // 如果存在开始结束时间，并且是要修改
            $goodsList = [$goods_id => ['id' => $goods_id]];

            $this->checkActivityConflict([
                'type' => $activity_type,
                'classify' => $this->model->getClassify($activity_type),
                'start_time' => $start_time,
                'end_time' => $end_time,
                'prehead_time' => $prehead_time
            ], $goodsList, $id);
        }

        // 商品规格
        $skus = SkuModel::with('children')->where('goods_id', $goods_id)->where('parent_id', 0)->select();

        // 获取规格
        $skuPrices = SkuPriceModel::with(['activity_sku_price' => function ($query) use ($id) {
            $query->where('activity_id', $id);
        }])->where('goods_id', $goods_id)->select();


        //编辑
        $activitySkuPrices = [];
        foreach ($skuPrices as $k => $skuPrice) {
            $activitySkuPrices[$k] = $skuPrice->activity_sku_price ? $skuPrice->activity_sku_price->toArray() : [];
            // 活动规格数据初始化
            if (!$activitySkuPrices[$k]) {
                $activitySkuPrices[$k]['id'] = 0;
                $activitySkuPrices[$k]['status'] = 'down';
                $activitySkuPrices[$k]['price'] = '';
                $activitySkuPrices[$k]['stock'] = '';
                $activitySkuPrices[$k]['sales'] = '0';
                $activitySkuPrices[$k]['goods_sku_price_id'] = $skuPrice->id;
            }

            // 个性化初始化每个活动的 规格 字段
            $activitySkuPrices[$k] = $this->manager->showSkuPrice($activity_type, $activitySkuPrices[$k]);
        }

        $this->success('获取成功', null, [
            'skus' => $skus,
            'sku_prices' => $skuPrices,
            'activity_sku_prices' => $activitySkuPrices,
        ]);
    }



    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $type = $this->request->param('type');
        $activities = $this->model->sheepFilter()->whereIn('type', $type)->paginate(request()->param('list_rows', 10))->toArray();

        $this->success('获取成功', null, $activities);
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
                $count += $this->manager->delete($item);
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

        $type = $this->request->param('type');
        $activities = $this->model->onlyTrashed()->sheepFilter()->where('type', $type)->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $activities);
    }
}
