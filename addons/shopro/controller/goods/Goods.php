<?php

namespace addons\shopro\controller\goods;

use addons\shopro\controller\Common;
use addons\shopro\service\goods\GoodsService;
use app\admin\model\shopro\user\GoodsLog;
use app\admin\model\shopro\activity\Activity;

class Goods extends Common
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $params = $this->request->param();

        $keyword = $params['keyword'] ?? '';
        $ids = $params['ids'] ?? '';
        $category_id = $params['category_id'] ?? '';
        $is_category_deep = $params['is_category_deep'] ?? true;
        $sort = $params['sort'] ?? 'weigh';
        $order = $params['order'] ?? 'desc';

        $service = new GoodsService(function ($goods) {
            $goods->activities = $goods->activities;
            $goods->promos = $goods->promos;
            return $goods;
        });

        $service->up()->with(['max_sku_price' => function ($query) {      // 计算价格区间用(不知道为啥 with 必须再 show 后面)
            $query->where('status', 'up');
        }]);

        if ($keyword) {
            $service->search($keyword);
        }
        if ($ids) {
            $service->whereIds($ids);
        }
        if ($category_id) {
            $service->category($category_id, $is_category_deep);
        }

        if ($sort) {
            $service->order($sort, $order);
        }


        $goods = $service->paginate();

        $this->success('获取成功', $goods);
    }


    /**
     * 通过 ids 获取商品（不分页）
     *
     * @return void
     */
    public function ids() 
    {
        $params = $this->request->param();

        $ids = $params['ids'] ?? '';

        $service = new GoodsService(function ($goods) {
            $goods->activities = $goods->activities;
            $goods->promos = $goods->promos;
            return $goods;
        });

        $service->show()->with(['max_sku_price' => function ($query) {
            $query->where('status', 'up');
        }]);

        if ($ids) {
            $service->whereIds($ids);
        }
        $goods = $service->select();

        $this->success('获取成功', $goods);
    }


    public function detail()
    {
        $user = auth_user();
        $id = $this->request->param('id');
        $activity_id = $this->request->param('activity_id');

        // 存一下，获取器获取指定活动的时候会用到
        session('goods-activity_id:' . $id, $activity_id);
        $service = new GoodsService(function ($goods, $service) use ($activity_id) {
            $goods->service = $goods->service;
            $goods->skus = $goods->skus;
            
            if (!$activity_id) {
                $goods->activities = $goods->activities;
                $goods->promos = $goods->promos;
            } else {
                $goods->activity = $goods->activity;
                $goods->original_goods_price = $goods->original_goods_price;
            }
            return $goods;
        });

        $goods = $service->show()->activity($activity_id)->with(['max_sku_price' => function ($query) {      // 计算价格区间用(不知道为啥 with 必须再 show 后面)
            $query->where('status', 'up');
        }, 'favorite'])->where('id', $id)->find();
        if (!$goods) {
            $this->error(__('No Results were found'));
        }

        // 添加浏览记录
        GoodsLog::addView($user, $goods);

        // 处理商品规格
        $skuPrices = $goods['new_sku_prices'];
        $content = $goods['content'];
        $goods = $goods->toArray();
        $goods['sku_prices'] = $skuPrices;
        $goods['content'] = $content;
        unset($goods['new_sku_prices']);

        $this->success('获取成功', $goods);
    }



    /**
     * 获取指定活动相关商品
     *
     * @return void
     */
    public function activity()
    {
        $activity_id = $this->request->param('activity_id');
        $need_buyers = $this->request->param('need_buyers', 0);      // 需要查询哪些人在参与活动
        $activity = Activity::where('id', $activity_id)->find();
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

        $goods = $service->activity($activity_id)->whereIds($goodsIds)->show()->order('weigh', 'desc')->select();
        $goods = collection($goods)->toArray();
        foreach ($goods as &$gd) {
            unset($gd['new_sku_prices'], $gd['activity']);
        }
        $this->success('获取成功', $goods);
    }



    /**
     * 获取指定活动相关商品,带分页
     *
     * @param Request $request
     * @return void
     */
    public function activityList()
    {
        $activity_id = $this->request->param('activity_id');
        $activity = Activity::where('id', $activity_id)->find();
        if (!$activity) {
            $this->error(__('No Results were found'));
        }
        $goodsIds = $activity->goods_ids ? explode(',', $activity->goods_ids) : [];

        // 存一下，获取器获取指定活动的时候会用到
        foreach ($goodsIds as $id) {
            session('goods-activity_id:' . $id, $activity_id);
        }
        $service = new GoodsService(function ($goods) {
            $goods->promos = $goods->promos;
            return $goods;
        });
        $goods = $service->activity($activity_id)->whereIds($goodsIds)->show()->order('weigh', 'desc')->paginate();
        $this->success('获取成功', $goods);
    }
}
