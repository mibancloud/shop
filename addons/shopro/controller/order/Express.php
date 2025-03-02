<?php

namespace addons\shopro\controller\order;

use addons\shopro\controller\Common;
use app\admin\model\shopro\order\Express as OrderExpressModel;
use addons\shopro\library\express\Express as ExpressLib;

class Express extends Common
{

    protected $noNeedLogin = ['push'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $user = auth_user();
        $order_id = $this->request->param('order_id');

        // 更新包裹信息（5分钟缓存）
        (new ExpressLib)->updateOrderExpress($order_id);

        $expresses = OrderExpressModel::with(['logs', 'items' => function ($query) use ($order_id) {
            return $query->where('order_id', $order_id);
        }])->where('user_id', $user->id)->where('order_id', $order_id)->select();

        $this->success('获取成功', $expresses);
    }


    public function detail()
    {
        $user = auth_user();
        $id = $this->request->param('id');
        $order_id = $this->request->param('order_id');

        // 更新包裹信息（5分钟缓存）
        (new ExpressLib)->updateOrderExpress($order_id);

        $express = OrderExpressModel::with(['logs', 'items' => function ($query) use ($order_id) {
            return $query->where('order_id', $order_id);
        }])->where('user_id', $user->id)->where('order_id', $order_id)->where('id', $id)->find();

        $this->success('获取成功', $express);
    }



    /**
     * 接受物流推送
     *
     * @param Request $request
     * @return void
     */
    public function push()
    {
        $data = $this->request->param();
        $expressLib = new ExpressLib();
        $result = $expressLib->push($data);
        return response($result, 200, [], 'json');
    }
}
