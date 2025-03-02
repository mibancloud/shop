<?php

namespace addons\shopro\controller\app;

use addons\shopro\controller\Common;
use addons\shopro\service\goods\GoodsService;
use app\admin\model\shopro\user\GoodsLog;

class ScoreShop extends Common
{
    protected $noNeedLogin = ['index', 'ids', 'detail'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $params = $this->request->param();

        $keyword = $params['keyword'] ?? '';
        $sort = $params['sort'] ?? 'weigh';
        $order = $params['order'] ?? 'desc';

        $service = new GoodsService(function ($goods) {
            $goods->score = $goods->score;
            return $goods;
        });

        $service->show()->score()->with(['all_score_sku_prices']);          // 包含下架的积分规格

        if ($keyword) {
            $service->search($keyword);
        }

        if ($sort) {
            $service->order($sort, $order);
        }

        $goods = $service->paginate();

        $this->success('获取成功', $goods);
    }


    public function ids()
    {
        $params = $this->request->param();
        $ids = $params['ids'] ?? '';

        $service = new GoodsService(function ($goods) {
            $goods->score = $goods->score;
            return $goods;
        });

        $service->show()->score()->with(['all_score_sku_prices']);

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

        $service = new GoodsService(function ($goods, $service) {
            $goods->score = $goods->score;
            $goods->service = $goods->service;
            $goods->skus = $goods->skus;
            return $goods;
        });

        $goods = $service->show()->score()->where('id', $id)->find();
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
}
