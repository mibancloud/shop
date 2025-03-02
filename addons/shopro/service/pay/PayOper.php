<?php

namespace addons\shopro\service\pay;

use think\Log;
use app\admin\model\shopro\Pay as PayModel;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\order\Action;
use think\helper\Str;
use addons\shopro\service\Wallet as WalletService;


class PayOper
{
    protected $user = null;

    /**
     * 实例化
     *
     * @param mixed $user
     */
    public function __construct($user = null)
    {
        // 优先使用传入的用户
        $this->user = $user ? (is_numeric($user) ? User::get($user) : $user) : auth_user();
    }



    /**
     * 微信预付款
     *
     * @param think\Model $order
     * @param float $money
     * @param string $order_type
     * @return think\Model
     */
    public function wechat($order, $money, $order_type = 'order')
    {
        $pay = $this->addPay($order, [
            'order_type' => $order_type,
            'pay_type' => 'wechat',
            'pay_fee' => $money,
            'real_fee' => $money,
            'transaction_id' => null,
            'payment_json' => [],
            'status' => PayModel::PAY_STATUS_UNPAID
        ]);

        return $pay;
    }


    /**
     * 支付宝预付款
     *
     * @param think\Model $order
     * @param float $money
     * @param string $order_type
     * @return think\Model
     */
    public function alipay($order, $money, $order_type = 'order')
    {
        $pay = $this->addPay($order, [
            'order_type' => $order_type,
            'pay_type' => 'alipay',
            'pay_fee' => $money,
            'real_fee' => $money,
            'transaction_id' => null,
            'payment_json' => [],
            'status' => PayModel::PAY_STATUS_UNPAID
        ]);

        return $pay;
    }



    /**
     * 余额付款
     *
     * @param think\Model $order
     * @param float $money
     * @param string $order_type
     * @return think\Model
     */
    public function money($order, $money, $order_type = 'order')
    {
        // 余额支付金额，传入金额和剩余支付金额最大值
        $money = $order->remain_pay_fee > $money ? $money : $order->remain_pay_fee;     // 混合支付不能超过订单应支付总金额

        // 扣除用户余额
        WalletService::change($this->user, 'money', -$money, 'order_pay', [
            'order_id' => $order->id,
            'order_sn' => $order->order_sn,
            'order_type' => $order_type,
        ]);

        // 添加支付记录
        $pay = $this->addPay($order, [
            'order_type' => $order_type,
            'pay_type' => 'money',
            'pay_fee' => $money,
            'real_fee' => $money,
            'transaction_id' => null,
            'payment_json' => [],
            'status' => PayModel::PAY_STATUS_PAID
        ]);

        // 余额直接支付成功，更新订单剩余应付款金额，并检测订单状态
        return $this->checkAndPaid($order, $order_type);
    }



    /**
     * 积分支付
     *
     * @param think\Model $order
     * @param float $money
     * @param string $order_type
     * @return think\Model
     */
    public function score($order, $score, $order_type = 'order')
    {
        if ($order_type == 'order') {
            if ($order['type'] == 'score') {
                $log_type = 'score_shop_pay';
                $real_fee = $score;         // 积分商城真实抵扣，就是积分
            } else {
                $log_type = 'order_pay';
                // $real_fee = ;         // 积分商城真实抵扣，就是积分
                error_stop('缺少积分抵扣金额');       // 支持积分抵扣时补全
            }
        }

        WalletService::change($this->user, 'score', -$score, $log_type, [
            'order_id' => $order->id,
            'order_sn' => $order->order_sn,
            'order_type' => $order_type,
        ]);

        // 添加支付记录
        $pay = $this->addPay($order, [
            'order_type' => $order_type,
            'pay_type' => 'score',
            'pay_fee' => $score,
            'real_fee' => $real_fee,
            'transaction_id' => null,
            'payment_json' => [],
            'status' => PayModel::PAY_STATUS_PAID
        ]);

        // 积分直接支付成功，更新订单剩余应付款金额，并检测订单状态
        return $this->checkAndPaid($order, $order_type);
    }


    /**
     * 线下支付(货到付款)
     *
     * @param think\Model $order
     * @param float $money
     * @param string $order_type
     * @return think\Model
     */
    public function offline($order, $money, $order_type = 'order')
    {
        // 添加支付记录
        $pay = $this->addPay($order, [
            'order_type' => $order_type,
            'pay_type' => 'offline',
            'pay_fee' => $money,
            'real_fee' => $money,
            'transaction_id' => null,
            'payment_json' => [],
            'status' => PayModel::PAY_STATUS_PAID
        ]);

        // 更新订单剩余应付款金额，并检测订单状态
        return $this->checkAndPaid($order, $order_type, 'offline');
    }


    /**
     * 微信支付宝支付回调通用方法
     *
     * @param \think\Model $pay
     * @param array $notify
     * @return void
     */
    public function notify($pay, $notify)
    {
        $pay->status = PayModel::PAY_STATUS_PAID;
        $pay->transaction_id = $notify['transaction_id'];
        $pay->buyer_info = $notify['buyer_info'];
        $pay->payment_json = $notify['payment_json'];
        $pay->paid_time = time();
        $pay->save();

        $orderModel = $this->getOrderModel($pay->order_type);
        $order = new $orderModel();
        $order = $order->where('id', $pay->order_id)->find();
        if (!$order) {
            // 订单未找到，非正常情况，这里记录日志
            Log::write('pay-notify-error:order notfound;pay:' . json_encode($pay) . ';notify:' . json_encode($notify));
            return false;
        }

        if ($order->status == $order::STATUS_UNPAID) {      // 未支付，检测支付状态
            $order = $this->checkAndPaid($order, $pay->order_type);
        }

        return $order;
    }



    /**
     * 更新订单剩余应支付金额，并且检测订单状态
     *
     * @param think\Model $order
     * @param string $order_type
     * @return think\Model
     */
    public function checkAndPaid($order, $order_type, $pay_mode = 'online')
    {
        // 获取订单已支付金额
        $payed_fee = $this->getPayedFee($order, $order_type);

        $remain_pay_fee = bcsub($order->pay_fee, (string)$payed_fee, 2);

        $order->remain_pay_fee = $remain_pay_fee;
        if ($remain_pay_fee <= 0) {
            $order->remain_pay_fee = 0;
            $order->paid_time = time();
            $order->status = $order::STATUS_PAID;
        } else {
            if ($pay_mode == 'offline') {
                // 订单未支付成功，并且是线下支付(货到付款)，将订单状态改为 pending
                $order->status = $order::STATUS_PENDING;
                $order->ext = array_merge($order->ext, ['pending_time' => time()]);     // 货到付款下单时间
                $order->pay_mode = 'offline';
            }
        }
        $order->save();

        if ($order->status == $order::STATUS_PAID) {
            // 订单支付完成
            $user = User::where('id', $order->user_id)->find();
            if ($order_type == 'order') {
                if ($pay_mode == 'offline') {
                    Action::add($order, null, auth_admin(), 'admin', '管理员操作自动货到付款支付成功');
                    // 在控制器执行后续内容，这里不再处理
                    return $order;
                } else {
                    Action::add($order, null, $user, 'user', '用户支付成功');
                    // 支付成功后续使用异步队列处理
                    \think\Queue::push('\addons\shopro\job\OrderPaid@paid', ['order' => $order, 'user' => $user], 'shopro-high');
                }
            } else if ($order_type == 'trade_order') {
                // 支付成功后续使用异步队列处理
                \think\Queue::push('\addons\shopro\job\trade\OrderPaid@paid', ['order' => $order, 'user' => $user], 'shopro-high');
            }
        } else if ($order->status == $order::STATUS_PENDING) {
            // 货到付款，添加货到付款队列（后续也需要处理拼团， 减库存等等）
            $user = User::where('id', $order->user_id)->find();
            if ($order_type == 'order') {
                Action::add($order, null, $user, 'user', '用户货到付款下单成功');

                // 支付成功后续使用异步队列处理
                \think\Queue::push('\addons\shopro\job\OrderPaid@offline', ['order' => $order, 'user' => $user], 'shopro-high');
            }
        }

        return $order;
    }



    /**
     * 获取订单已支付金额，商城订单 计算 积分抵扣金额
     *
     * @param \think\Model $order
     * @param string $order_type
     * @return string
     */
    public function getPayedFee($order, $order_type)
    {
        // 锁定读取所有已支付的记录，判断已支付金额
        $pays = PayModel::{'type' . Str::studly($order_type)}()->paid()->where('order_id', $order->id)->lock(true)->select();

        // 商城或者积分商城订单
        $payed_fee = '0';
        foreach ($pays as $key => $pay) {
            if ($pay->pay_type == 'score') {
                if ($order_type == 'order' && $order['type'] == 'goods') {
                    // 商城类型订单，并且不是积分商城订单，加上积分抵扣真实金额
                    $payed_fee = bcadd($payed_fee, $pay->real_fee, 2);
                } else {
                    // 其他类型，需要计算积分抵扣的金额时
                }
            } else {
                $payed_fee = bcadd($payed_fee, $pay->real_fee, 2);
            }
        }

        return $payed_fee;
    }



    /**
     * 获取剩余可退款的pays 记录（不含积分抵扣）
     * 
     * @param integer $order_id
     * @param string $sort  排序：money=优先退回余额支付的钱
     * @return \think\Collection
     */
    public function getCanRefundPays($order_id, $sort = 'money')
    {
        // 商城订单，已支付的 pay 记录, 这里只查 钱的支付记录，不查积分
        $pays = PayModel::typeOrder()->paid()->isMoney()->where('order_id', $order_id)->lock(true)->order('id', 'asc')->select();
        $pays = collection($pays);
        if ($sort == 'money') {
            // 对 pays 进行排序，优先退 money 的钱
            $pays = $pays->sort(function ($a, $b) {
                if ($a['pay_type'] == 'money' && $b['pay_type'] == 'money') {
                    return 0;
                } else if ($a['pay_type'] == 'money' && $b['pay_type'] != 'money') {
                    return -1;
                } else if ($a['pay_type'] != 'money' && $b['pay_type'] == 'money') {
                    return 1;
                } else {
                    return 0;
                }
            });

            $pays = $pays->values();
        }

        return $pays;
    }



    /**
     * 获取剩余可退款金额，不含积分相关支付
     *
     * @param mixed $pays
     * @return string
     */
    public function getRemainRefundMoney($pays)
    {
        // 拿到 所有可退款的支付记录
        $pays = ($pays instanceof \think\Collection) ? $pays : $this->getCanRefundPays($pays);

        // 支付金额，除了已经退完款的金额 （这里不退积分）
        $payed_money = (string)array_sum($pays->column('pay_fee'));
        // 已经退款金额 （这里不退积分）
        $refunded_money = (string)array_sum($pays->column('refund_fee'));
        // 当前剩余的最大可退款金额，支付金额 - 已退款金额
        $remain_max_refund_money = bcsub($payed_money, $refunded_money, 2);

        return $remain_max_refund_money;
    }



    /**
     * 添加 pay 记录
     *
     * @param think\Model $order
     * @param array $params
     * @return think\Model
     */
    public function addPay($order, $params)
    {
        $payModel = new PayModel();

        $payModel->order_type = $params['order_type'];
        $payModel->order_id = $order->id;
        $payModel->pay_sn = get_sn($this->user->id, 'P');
        $payModel->user_id = $this->user->id;
        $payModel->pay_type = $params['pay_type'];
        $payModel->pay_fee = $params['pay_fee'];
        $payModel->real_fee = $params['real_fee'];
        $payModel->transaction_id = $params['transaction_id'];
        $payModel->payment_json = $params['payment_json'];
        $payModel->paid_time = $params['status'] == PayModel::PAY_STATUS_PAID ? time() : null;
        $payModel->status = $params['status'];
        $payModel->refund_fee = 0;
        $payModel->save();

        return $payModel;
    }


    public function getOrderModel($order_type)
    {
        switch ($order_type) {
            case 'trade_order':
                $orderModel = '\\app\\admin\\model\\shopro\\trade\\Order';
                break;
            case 'order':
                $orderModel = '\\app\\admin\\model\\shopro\\order\\Order';
                break;
            default:
                $orderModel = '\\app\\admin\\model\\shopro\\order\\Order';
                break;
        }

        return $orderModel;
    }
}
