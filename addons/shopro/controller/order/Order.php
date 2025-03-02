<?php

namespace addons\shopro\controller\order;

use think\Db;
use addons\shopro\controller\Common;
use addons\shopro\service\order\OrderCreate;
use addons\shopro\service\order\OrderOper;
use app\admin\model\shopro\order\Order as OrderModel;
use app\admin\model\shopro\Pay as PayModel;
use addons\shopro\library\express\Express as ExpressLib;

class Order extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $user = auth_user();
        $params = $this->request->param();
        $type = $params['type'] ?? 'all';

        $orders = OrderModel::where('user_id', $user->id)->with(['items', 'invoice']);

        switch ($type) {
            case 'unpaid':
                $orders = $orders->unpaid();
                break;
            case 'nosend':
                $orders = $orders->pretendPaid()->nosend();
                break;
            case 'noget':
                $orders = $orders->pretendPaid()->noget();
                break;
            case 'nocomment':
                $orders = $orders->paid()->nocomment();
                break;
        }

        $orders = $orders->order('id', 'desc')->paginate(request()->param('list_rows', 10))->toArray();

        $orderModel = new OrderModel();
        foreach ($orders['data'] as &$order) {
            $order = $orderModel->setOrderItemStatusByOrder($order);
        }

        $this->success('获取成功', $orders);
    }


    public function detail()
    {
        $user = auth_user();
        $id = $this->request->param('id');
        $merchant_trade_no = $this->request->param('merchant_trade_no');
        $transaction_id = $this->request->param('transaction_id');

        $order = OrderModel::where('user_id', $user->id)->with(['items', 'address', 'invoice']);

        if ($id) {
            $order = $order->where(function ($query) use ($id) {
                return $query->where('id', $id)->whereOr('order_sn', $id);
            });
        } else if ($merchant_trade_no) {
            $pay = PayModel::where('pay_sn', $merchant_trade_no)->findOrFail();
            $order = $order->where('id', $pay->order_id);
        } else {
            $this->error('参数错误');
        }

        $order = $order->find();
        if (!$order) {
            $this->error(__('No Results were found'));
        }

        $order->pay_types_text = $order->pay_types_text;
        // 处理未支付订单 item status_code
        $order = $order->setOrderItemStatusByOrder($order);     // 这里订单转 数组了

        // 更新包裹信息（5分钟缓存）
        (new ExpressLib)->updateOrderExpress($order['id']);
        
        $this->success('获取成功', $order);
    }


    public function itemDetail()
    {
        $user = auth_user();

        $id = $this->request->param('id');
        $item_id = $this->request->param('item_id');

        if (!$id || !$item_id) {
            $this->error('参数错误');
        }

        $order = OrderModel::with(['items' => function ($query) use ($item_id) {
            $query->where('id', $item_id);
        }])->where('user_id', $user->id)->where('id', $id)->find();
        if (!$order || !$order->items) {
            $this->error(__('No Results were found'));
        }

        $order = $order->setOrderItemStatusByOrder($order);     // 这里订单转 数组了
        $item = $order['items'][0];

        $this->success('获取成功', $item);
    }


    public function calc()
    {
        $params = $this->request->param();
        $this->svalidate($params, ".calc");

        $orderCreate = new OrderCreate($params);
        $result = $orderCreate->calc();

        if (isset($result['msg']) && $result['msg']) {
            $this->error($result['msg'], 1, $result);
        } else {
            $this->success('计算成功', $result);
        }
    }


    public function create()
    {
        $params = $this->request->param();
        $this->svalidate($params, ".create");

        $orderCreate = new OrderCreate($params);
        $result = $orderCreate->calc('create');

        $order = $orderCreate->create($result);

        $this->success('订单添加成功', $order);
    }



    /**
     * 获取用户可用和不可用优惠券
     *
     * @param Request $request
     * @return void
     */
    public function coupons() 
    {
        $params = $this->request->param();
        $this->svalidate($params, ".create");

        $orderCreate = new OrderCreate($params);
        $result = $orderCreate->getCoupons();

        $this->success('获取成功', $result);
    }



    // 取消订单
    public function cancel()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $order = Db::transaction(function () use ($id, $user) {
            $order = OrderModel::canCancel()->where('user_id', $user->id)->with(['items', 'invoice'])->lock(true)->where('id', $id)->find();
            if (!$order) {
                $this->error(__('No Results were found'));
            }

            $orderOper = new OrderOper();
            $order = $orderOper->cancel($order, $user, 'user');

            return $order;
        });
        // 订单未支付，处理 item 状态
        $order = $order->setOrderItemStatusByOrder($order);     // 这里订单转 数组了

        $this->success('取消成功', $order);
    }


    // 订单申请全额退款
    public function applyRefund() 
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $order = OrderModel::paid()->where('user_id', $user->id)->where('id', $id)->find();
        if (!$order) {
            $this->error(__('No Results were found'));
        }

        $order = Db::transaction(function () use ($order, $user) {
            $orderOper = new OrderOper();
            $order = $orderOper->applyRefund($order, $user, 'user');

            return $order;
        });

        $order = OrderModel::with(['items', 'invoice'])->find($id);
        $order = $order->setOrderItemStatusByOrder($order);     // 这里订单转 数组了
        $this->success('申请成功', $order);
    }

    // 确认收货(货到付款的确认收货在后台)
    public function confirm()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $order = OrderModel::paid()->where('user_id', $user->id)->where('id', $id)->find();
        if (!$order) {
            $this->error(__('No Results were found'));
        }

        $order = Db::transaction(function () use ($order, $user) {
            $orderOper = new OrderOper();
            $order = $orderOper->confirm($order, [], $user, 'user');

            return $order;
        });

        $order = OrderModel::with(['items', 'invoice'])->find($id);
        $this->success('收货成功', $order);
    }


    // 评价
    public function comment()
    {
        $user = auth_user();
        $params = $this->request->param();
        $id = $params['id'] ?? 0;
        $this->svalidate($params, ".comment");

        $comments = $params['comments'] ?? [];
        foreach ($comments as $comment) {
            $this->svalidate($comment, ".comment_item");
        }

        $order = OrderModel::paid()->where('user_id', $user->id)->where('id', $id)->find();
        if (!$order) {
            $this->error(__('No Results were found'));
        }

        $order = Db::transaction(function () use ($order, $params, $user) {
            $orderOper = new OrderOper();
            $order = $orderOper->comment($order, $params['comments'], $user, 'user');

            return $order;
        });

        $this->success('评价成功', $order);
    }



    /**
     * 用户是否存在未支付的，当前团的订单
     *
     * @param Request $request
     * @return void
     */
    public function unpaidGrouponOrder()
    {
        $user = auth_user();

        $params = $this->request->param();
        $activity_id = $params['activity_id'] ?? 0;

        if ($user && $activity_id) {
            $order = OrderModel::unpaid()->where('user_id', $user->id)
                ->where('activity_id', $activity_id)
                ->whereIn('activity_type', ['groupon', 'groupon_ladder'])->find();
        }

        $this->success('获取成功', $order ?? null);
    }


    // 删除订单
    public function delete()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $order = OrderModel::canDelete()->where('user_id', $user->id)->where('id', $id)->find();
        if (!$order) {
            $this->error('订单不存在或不可删除');
        }

        Db::transaction(function () use ($order, $user) {
            $orderOper = new OrderOper();
            $orderOper->delete($order, $user, 'user');
        });

        $this->success('删除成功');
    }
}
