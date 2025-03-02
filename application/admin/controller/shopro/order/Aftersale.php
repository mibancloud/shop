<?php

namespace app\admin\controller\shopro\order;

use think\Db;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\order\Aftersale as OrderAftersaleModel;
use app\admin\model\shopro\order\AftersaleLog as OrderAftersaleLogModel;
use app\admin\model\shopro\order\Order as OrderModel;
use app\admin\model\shopro\order\OrderItem as OrderItemModel;
use app\admin\model\shopro\order\Action as OrderActionModel;
use addons\shopro\service\order\OrderRefund;
use addons\shopro\library\Operator;

class Aftersale extends Common
{

    protected $noNeedRight = ['getType'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new OrderAftersaleModel;
        $this->orderModel = new OrderModel;
    }

    /**
     * 售后单列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        // 查询主表是订单表
        $orders = $this->orderModel->withTrashed()->sheepFilter()->with(['user', 'aftersales' => function ($query) {
                $query->removeOption('soft_delete');
            }])->paginate(request()->param('list_rows', 10));

        $this->success('获取成功', null, $orders);
    }


    // 获取数据类型
    public function getType()
    {
        $type = $this->model->typeList();
        $dispatchStatus = $this->model->dispatchStatusList();
        $aftersaleStatus = $this->model->aftersaleStatusList();
        $refundStatus = $this->model->refundStatusList();

        $result = [
            'type' => $type,
            'dispatch_status' => $dispatchStatus,
            'aftersale_status' => $aftersaleStatus,
            'refund_status' => $refundStatus,
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
     * 售后单详情
     *
     * @param  $id
     * @return \think\Response
     */
    public function detail($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $aftersale = $this->model->withTrashed()->with(['user', 'order' => function ($query) {
            $query->removeOption('soft_delete');
        }, 'logs'])->where('id', $id)->find();
        if (!$aftersale) {
            $this->error(__('No Results were found'));
        }

        // 建议退款金额
        $aftersale->suggest_refund_fee = $aftersale->suggest_refund_fee;

        // 多态关联 oper
        $morphs = [
            'user' => \app\admin\model\shopro\user\User::class,
            'admin' => \app\admin\model\Admin::class,
            'system' => \app\admin\model\Admin::class,
        ];
        $aftersale['logs'] = morph_to($aftersale['logs'], $morphs, ['oper_type', 'oper_id']);

        $aftersale = $aftersale->toArray();
        foreach ($aftersale['logs'] as &$log) {
            $log['oper'] = Operator::info($log['oper_type'], $log['oper'] ?? null);
        }
        $this->success('获取成功', null, $aftersale);
    }



    /**
     * 完成售后
     */
    public function completed($id)
    {
        $admin = $this->auth->getUserInfo();

        $aftersale = $this->model->withTrashed()->canOper()->where('id', $id)->find();
        if (!$aftersale) {
            $this->error('售后单不存在或不可完成');
        }

        $order = $this->orderModel->withTrashed()->find($aftersale->order_id);
        $orderItem = OrderItemModel::find($aftersale->order_item_id);
        if (!$order || !$orderItem) {
            $this->error('订单或订单商品不存在');
        }

        $aftersale = Db::transaction(function () use ($aftersale, $order, $orderItem, $admin) {
            $aftersale->aftersale_status = OrderAftersaleModel::AFTERSALE_STATUS_COMPLETED;    // 售后完成
            $aftersale->save();
            // 增加售后单变动记录、
            OrderAftersaleLogModel::add($order, $aftersale, $admin, 'admin', [
                'log_type' => 'completed',
                'content' => '售后订单已完成',
                'images' => []
            ]);

            $orderItem->aftersale_status = OrderItemModel::AFTERSALE_STATUS_COMPLETED;
            $orderItem->save();
            OrderActionModel::add($order, $orderItem, $admin, 'admin', '管理员完成售后');

            // 售后单完成之后
            $data = ['aftersale' => $aftersale, 'order' => $order, 'item' => $orderItem];
            \think\Hook::listen('order_aftersale_completed', $data);

            return $aftersale;
        });

        $this->success('操作成功', null, $aftersale);
    }


    /**
     * 拒绝售后
     */
    public function refuse($id = 0)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $admin = $this->auth->getUserInfo();

        $params = $this->request->param();
        $this->svalidate($params, '.refuse');

        $aftersale = $this->model->withTrashed()->canOper()->where('id', $id)->find();
        if (!$aftersale) {
            $this->error('售后单不存在或不可拒绝');
        }

        $order = $this->orderModel->withTrashed()->find($aftersale->order_id);
        $orderItem = OrderItemModel::find($aftersale->order_item_id);
        if (!$order || !$orderItem) {
            $this->error('订单或订单商品不存在');
        }

        $aftersale = Db::transaction(function () use ($aftersale, $order, $orderItem, $params, $admin) {
            $aftersale->aftersale_status = OrderAftersaleModel::AFTERSALE_STATUS_REFUSE;    // 售后拒绝
            $aftersale->save();
            // 增加售后单变动记录
            OrderAftersaleLogModel::add($order, $aftersale, $admin, 'admin', [
                'log_type' => 'refuse',
                'content' => $params['refuse_msg'],
                'images' => []
            ]);

            $orderItem->aftersale_status = OrderItemModel::AFTERSALE_STATUS_REFUSE;    // 拒绝售后
            $orderItem->save();

            OrderActionModel::add($order, $orderItem, $admin, 'admin', '管理员拒绝订单售后：' . $params['refuse_msg']);

            // 售后单拒绝后
            $data = ['aftersale' => $aftersale, 'order' => $order, 'item' => $orderItem];
            \think\Hook::listen('order_aftersale_refuse', $data);

            return $aftersale;
        });

        $this->success('操作成功', null, $aftersale);
    }


    /**
     * 同意退款
     */
    public function refund($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $admin = $this->auth->getUserInfo();

        $params = $this->request->param();
        $this->svalidate($params, '.refund');

        $refund_money = round(floatval($params['refund_money']), 2);
        $refund_type = $params['refund_type'] ?? 'back';
        if ($refund_money <= 0) {
            $this->error('请输入正确的退款金额');
        }

        $aftersale = $this->model->withTrashed()->canOper()->where('id', $id)->find();
        if (!$aftersale) {
            $this->error('售后单不存在或不可退款');
        }

        $order = $this->orderModel->withTrashed()->with('items')->find($aftersale->order_id);
        if (!$order) {
            $this->error('订单不存在');
        }
        $items = $order->items;
        $items = array_column($items, null, 'id');

        // 当前订单已退款总金额
        $refunded_money = array_sum(array_column($items, 'refund_fee'));
        // 剩余可退款金额
        $refund_surplus_money = bcsub($order->pay_fee, (string)$refunded_money, 2);
        // 如果退款金额大于订单支付总金额
        if (bccomp((string)$refund_money, $refund_surplus_money, 2) === 1) {
            $this->error('退款总金额不能大于实际支付金额');
        }

        $orderItem = $items[$aftersale['order_item_id']];

        if (!$orderItem || in_array($orderItem['refund_status'], [
            OrderItemModel::REFUND_STATUS_AGREE,
            OrderItemModel::REFUND_STATUS_COMPLETED,
        ])) {
            $this->error('订单商品已退款，不能重复退款');
        }

        $aftersale = Db::transaction(function () use ($aftersale, $order, $orderItem, $refund_money, $refund_type, $refund_surplus_money, $admin) {
            $aftersale->aftersale_status = OrderAftersaleModel::AFTERSALE_STATUS_COMPLETED;    // 售后同意
            $aftersale->refund_status = OrderAftersaleModel::REFUND_STATUS_AGREE;    // 同意退款
            $aftersale->refund_fee = $refund_money;     // 退款金额
            $aftersale->save();

            // 增加售后单变动记录
            OrderAftersaleLogModel::add($order, $aftersale, $admin, 'admin', [
                'log_type' => 'refund',
                'content' => '售后订单已退款',
                'images' => []
            ]);

            $orderItem->aftersale_status = OrderItemModel::AFTERSALE_STATUS_COMPLETED;
            $orderItem->save();
            OrderActionModel::add($order, $orderItem, $admin, 'admin', '管理员同意售后退款');

            // 开始退款
            $orderRefund = new OrderRefund($order);
            $orderRefund->refund($orderItem, $refund_money, $admin, [
                'refund_type' => $refund_type,
                'remark' => '管理员同意售后退款'
            ]);

            $data = ['aftersale' => $aftersale, 'order' => $order, 'item' => $orderItem];
            \think\Hook::listen('order_aftersale_completed', $data);

            return $aftersale;
        });

        $this->success('操作成功', null, $aftersale);
    }


    /**
     * 留言
     */
    public function addLog($id = 0)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $admin = $this->auth->getUserInfo();

        $params = $this->request->param();
        $this->svalidate($params, '.add_log');

        $aftersale = $this->model->withTrashed()->where('id', $id)->find();
        if (!$aftersale) {
            $this->error('售后单不存在');
        }

        $order = $this->orderModel->withTrashed()->with('items')->find($aftersale->order_id);
        if (!$order) {
            $this->error('订单不存在');
        }

        $aftersale = Db::transaction(function () use ($order, $aftersale, $params, $admin) {
            if ($aftersale['aftersale_status'] == 0) {
                $aftersale->aftersale_status = OrderAftersaleModel::AFTERSALE_STATUS_ING;    // 售后处理中
                $aftersale->save();
            }

            // 增加售后单变动记录
            OrderAftersaleLogModel::add($order, $aftersale, $admin, 'admin', [
                'log_type' => 'add_log',
                'content' => $params['content'],
                'images' => $params['images'] ?? []
            ]);

            return $aftersale;
        });

        $this->success('操作成功', null, $aftersale);
    }
}
