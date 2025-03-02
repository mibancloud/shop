<?php

namespace addons\shopro\controller\trade;

use addons\shopro\controller\Common;
use app\admin\model\shopro\trade\Order as TradeOrderModel;
use app\admin\model\shopro\Config;

class Order extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $user = auth_user();
        $params = $this->request->param();
        $type = $params['type'] ?? 'recharge';

        if (!in_array($type, ['recharge'])) {
            $this->error('参数错误');
        }
        $orders = TradeOrderModel::{$type}()->where('user_id', $user->id)->paid()
            ->order('id', 'desc')->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', $orders);
    }


    public function detail()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $order = TradeOrderModel::where('user_id', $user->id);

        $order = $order->where(function ($query) use($id) {
            return $query->where('id', $id)->whereOr('order_sn', $id);
        });

        $order = $order->find();
        if (!$order) {
            $this->error(__('No Results were found'));
        }
        $order->pay_type_text = $order->pay_type_text;

        $this->success('获取成功', $order);
    }


    public function rechargeRules()
    {
        $config = sheep_config('shop.recharge_withdraw.recharge');
        $config['status'] = $config['status'] ?? 0;
        $config['quick_amounts'] = $config['quick_amounts'] ?? [];
        $config['gift_type'] = $config['gift_type'] ?? 'money';

        $this->success('获取成功', $config);
    }


    public function recharge()
    {
        $user = auth_user();
        $params = $this->request->param();

        // 表单验证
        $this->svalidate($params, 'recharge');

        $recharge_money = floatval($params['recharge_money']);

        $config = Config::getConfigs('shop.recharge_withdraw.recharge');
        $recharge_status = $config['status'] ?? 0;
        $quick_amounts = $config['quick_amounts'] ?? [];
        $gift_type = $config['gift_type'] ?? 'money';

        if (!$recharge_status) {
            $this->error('充值入口已关闭');
        }

        if ($recharge_money < 0.01) {
            $this->error('请输入正确的充值金额');
        }

        $rule = ['money' => (string)$recharge_money];
        foreach ($quick_amounts as $quick_amount) {
            if (bccomp((string)$recharge_money, (string)$quick_amount['money'], 2) === 0) {
                $rule = $quick_amount;
                $rule['gift_type'] = $gift_type;
            }
        }

        $close_time = Config::getConfigs('shop.order.auto_close');
        $close_time = $close_time && $close_time > 0 ? $close_time : 0;

        $orderData = [];
        $orderData['type'] = 'recharge';
        $orderData['order_sn'] = get_sn($user->id, 'TO');
        $orderData['user_id'] = $user->id;
        $orderData['status'] = TradeOrderModel::STATUS_UNPAID;
        $orderData['order_amount'] = $recharge_money;
        $orderData['pay_fee'] = $recharge_money;
        $orderData['remain_pay_fee'] = $recharge_money;
        $orderData['platform'] = request()->header('platform');
        $orderData['remark'] = $params['remark'] ?? null;

        $ext = [
            'expired_time' => time() + ($close_time * 60),
            'rule' => $rule
        ];

        $orderData['ext'] = $ext;

        $order = new TradeOrderModel();
        $order->save($orderData);

        if ($close_time) {
            // 小于等于0， 不自动关闭订单
            \think\Queue::later(($close_time * 60), '\addons\shopro\job\trade\OrderAutoOper@autoClose', ['order' => $order], 'shopro');
        }

        $this->success('订单添加成功', $order);
    }
}
