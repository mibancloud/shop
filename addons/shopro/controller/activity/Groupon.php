<?php

namespace addons\shopro\controller\activity;

use addons\shopro\controller\Common;
use app\admin\model\shopro\activity\Groupon as GrouponModel;
use app\admin\model\shopro\activity\GrouponLog as GrouponLogModel;
use addons\shopro\service\goods\GoodsService;
use addons\shopro\facade\Activity as ActivityFacade;

class Groupon extends Common
{

    protected $noNeedLogin = ['index', 'detail'];
    protected $noNeedRight = ['*'];

    // 商品详情，参团列表
    public function index()
    {
        $params = $this->request->param();
        $goods_id = $params['goods_id'] ?? 0;
        $activity_id = $params['activity_id'] ?? 0;

        $groupons = GrouponModel::with('leader')->ing()
            ->where('goods_id', $goods_id)
            ->where('activity_id', $activity_id)
            ->order('id', 'asc')
            ->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', $groupons);
    }




    // 团详情
    public function detail()
    {
        $id = $this->request->param('id');
        $groupon = GrouponModel::with(['my', 'groupon_logs', 'activity' => function ($query) {
            $query->removeOption('soft_delete')->with(['activity_sku_prices']);     // 关联团所属活动，并关联活动规格
        }])->where('id', $id)->find();
        if (!$groupon) {
            $this->error(__('No Results were found'));
        }

        session('goods-activity_id:' . $groupon->goods_id, $groupon->activity_id);
        $service = new GoodsService(function ($goods, $service) use ($groupon) {
            $goods->skus = $goods->skus;
            $goods->activity = $goods->activity;
            return $goods;
        });
        // 查询所有状态的商品，并且包含被删除的商品
        $goods = $service->activity($groupon->activity_id)->withTrashed()->where('id', $groupon->goods_id)->find();
        if (!$goods) {
            $this->error('活动商品不存在');
        }
        // 商品可能关联不出来活动，因为活动可能已经被删除，redis 也会被删除
        if (!$currentActivity = $goods->activity) {
            // 活动已经结束被删除
            if ($currentActivity = $groupon->activity) {        // 尝试获取包含回收站在内的活动信息
                // 获取活动格式化之后的规格
                $skuPrices = ActivityFacade::recoverSkuPrices($goods, $currentActivity);
                $goods['new_sku_prices'] = $skuPrices;
            }
        }
        $goods = $goods->toArray();
        $goods['sku_prices'] = $goods['new_sku_prices'];
        unset($goods['new_sku_prices']);

        $groupon['goods'] = $goods;
        $groupon['activity_status'] = $currentActivity['status'];

        $this->success('获取成功', $groupon);
    }



    // 我的拼团
    public function myGroupons()
    {
        $user = auth_user();
        $params = $this->request->param();
        $type = $params['type'] ?? 'all';
        
        $grouponIds = GrouponLogModel::where('user_id', $user->id)->order('id', 'desc')->column('groupon_id');

        $groupons = GrouponModel::with(['my' => function ($query) {
            $query->with(['order', 'order_item']);
        }, 'groupon_logs', 'activity' => function ($query) {
            $query->removeOption('soft_delete')->with(['activity_sku_prices']);     // 关联团所属活动，并关联活动规格
        }, 'goods' => function ($query) {
            $query->removeOption('soft_delete');
        }])->whereIn('id', $grouponIds);

        if ($type != 'all') {
            $type = $type == 'finish' ? ['finish', 'finish-fictitious'] : [$type];
            $groupons = $groupons->whereIn('status', $type);
        }

        if ($grouponIds) {
            $groupons = $groupons->orderRaw('field(id, ' . join(',', $grouponIds) . ')');
        }
        $groupons = $groupons->paginate(request()->param('list_rows', 10))->toArray();

        foreach ($groupons['data'] as &$groupon) {
            if ($groupon['goods'] && isset($groupon['my']['order_item'])) {
                $groupon['goods']['price'] = [$groupon['my']['order_item']['goods_price']];
            }

            if ($groupon['activity']) {
                $activity = $groupon['activity'];
                if ($activity['rules'] && isset($activity['rules']['sales_show_type']) && $activity['rules']['sales_show_type'] == 'real') {
                    $sales = [];
                    foreach ($activity['activity_sku_prices'] as $activitySkuPrice) {
                        if ($activitySkuPrice['goods_id'] == $groupon['goods_id']) {
                            $sales[] = $activitySkuPrice['sales'];
                        }
                    }

                    if ($groupon['goods']) {
                        $groupon['goods']['sales'] = array_sum($sales);       // 这里计算的销量和 getSalesAttr 不一样，这里的没有排除下架的规格
                    }
                }
            }
        }

        $this->success('获取成功', $groupons);
    }
}
