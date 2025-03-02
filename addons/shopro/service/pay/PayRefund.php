<?php

namespace addons\shopro\service\pay;

use think\Log;
use think\Db;
use addons\shopro\library\pay\PayService;
use app\admin\model\shopro\Pay as PayModel;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\Refund as RefundModel;
use addons\shopro\service\Wallet as WalletService;


class PayRefund
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
        $this->user = is_numeric($user) ? User::get($user) : $user;
    }


    /**
     * 之前没有经历过任何的退款（刚支付的订单要退款|拼团失败订单等）
     *
     * @param \think\Model $pay
     * @param string $refund_type
     * @return void
     */
    public function fullRefund($pay, $data = [])
    {
        $pay->refund_fee = $pay->pay_fee;
        $pay->status = PayModel::PAY_STATUS_REFUND;     // 直接退款完成
        $pay->save();

        // 添加退款单
        $refund = $this->add($pay, $pay->pay_fee, $data);

        // 退款
        $refund = $this->return($pay, $refund);

        return $refund;
    }



    /**
     * 部分退款，指定退款金额，并且检测 pay 是否已经退款完成
     *
     * @param \think\Model $pay
     * @param float $refund_money
     * @param array $data
     * @return \think\Model
     */
    public function refund($pay, $refund_money, $data = [])
    {
        $pay->refund_fee = Db::raw('refund_fee + ' . $refund_money);
        $pay->save();

        // 添加退款单
        $refund = $this->add($pay, $refund_money, $data);

        // 退款
        $refund = $this->return($pay, $refund);

        // 检查 pay 是否退款完成， 
        $this->checkPayAndRefunded($pay);

        return $refund;
    }



    /**
     * 微信支付宝退款回调方法
     *
     * @param array $data
     * @return void
     */
    public function notify($data)
    {
        $out_trade_no = $data['out_trade_no'];
        $out_refund_no = $data['out_refund_no'];
        $payment_json = $data['payment_json'];

        $pay = PayModel::where('pay_sn', $out_trade_no)->find();
        if (!$pay) {
            Log::write('refund-notify-error:paymodel notfound;pay:' . json_encode($data));
            return false;
        }

        if ($pay->order_type == 'order') {
            $refund = RefundModel::where('refund_sn', $out_refund_no)->find();

            if (!$refund || $refund->status != RefundModel::STATUS_ING) {
                // 退款单不存在，或者已退款
                return false;
            }

            $refund = $this->completed($refund, $payment_json);
            return true;
        } else {
            // 如有其他订单类型如果支持退款，逻辑这里补充
        }
    }



    /**
     * 退回
     *
     * @param \think\Model $pay
     * @param \think\Model $refund
     * @return \think\Model
     */
    protected function return($pay, $refund)
    {
        $method = $refund->refund_method;
        if (method_exists($this, $method)) {
            $refund = $this->{$method}($pay, $refund);
        } else {
            error_stop('退款方式不支持');
        }

        return $refund;
    }



    /**
     * 退余额
     *
     * @param \think\Model $pay
     * @param \think\Model $refund
     * @return \think\Model
     */
    private function money($pay, $refund)
    {
        // 退回用户余额
        WalletService::change($pay->user_id, 'money', $refund->refund_fee, 'order_refund', [
            'refund_id' => $refund->id,
            'refund_sn' => $refund->refund_sn,
            'pay_id' => $pay->id,
            'pay_sn' => $pay->pay_sn,
            'order_id' => $pay->order_id,
            'order_type' => $pay->order_type,
        ]);

        $refund = $this->completed($refund);

        return $refund;
    }


    /**
     * 退积分
     *
     * @param \think\Model $pay
     * @param \think\Model $refund
     * @return \think\Model
     */
    private function score($pay, $refund)
    {
        // 退回用户积分
        WalletService::change($pay->user_id, 'score', $refund->refund_fee, 'order_refund', [
            'refund_id' => $refund->id,
            'refund_sn' => $refund->refund_sn,
            'pay_id' => $pay->id,
            'pay_sn' => $pay->pay_sn,
            'order_id' => $pay->order_id,
            'order_type' => $pay->order_type,
        ]);

        $refund = $this->completed($refund);

        return $refund;
    }


    /**
     * 退 offline
     *
     * @param \think\Model $pay
     * @param \think\Model $refund
     * @return \think\Model
     */
    private function offline($pay, $refund)
    {
        // offline 退款啥也不干，钱还是线下退回，线上不处理
        
        $refund = $this->completed($refund);

        return $refund;
    }



    /**
     * 退微信
     *
     * @param \think\Model $pay
     * @param \think\Model $refund
     * @return \think\Model
     */
    private function wechat($pay, $refund)
    {
        $order_data = [
            'out_trade_no' => $pay->pay_sn,
            'out_refund_no' => $refund->refund_sn,
            'reason' => $refund->remark,
            'amount' => [
                'refund' => $refund->refund_fee,
                'total' => $pay->pay_fee,
                'currency' => 'CNY'
            ]
        ];

        $pay = new PayService('wechat', $refund->platform);
        $result = $pay->refund($order_data);

        // 微信通知回调 pay->notifyRefund
        if (isset($result['status']) && in_array($result['status'], ['SUCCESS', 'PROCESSING'])) {
            // 微信返回的状态会是 PROCESSING
            return true;
        } else {
            error_stop('退款失败:' . (isset($result['message']) ? $result['message'] : json_encode($result, JSON_UNESCAPED_UNICODE)));
        }

        return $refund;
    }


    /**
     * 退支付宝
     *
     * @param \think\Model $pay
     * @param \think\Model $refund
     * @return \think\Model
     */
    private function alipay($pay, $refund)
    {
        $order_data = [
            'out_trade_no' => $pay->pay_sn,
            'out_request_no' => $refund->refund_sn,
            'refund_amount' => $refund->refund_fee,
            'refund_reason' => $refund->remark
        ];

        $pay = new PayService('alipay', $refund->platform);
        $result = $pay->refund($order_data);

        // 支付宝通知回调 pay->notify       // 是和支付通知一个地址
        if ($result['code'] == "10000") {
            return true;
        } else {
            error_stop('退款失败:' . $result['msg'] . (isset($result["sub_msg"]) && $result['sub_msg'] ? '-' . $result['sub_msg'] : ''));
        }

        return $refund;
    }



    /**
     * 添加 pay 记录
     *
     * @param think\Model $pay
     * @param float $refund_money 
     * @param array $data
     * @return think\Model
     */
    private function add($pay, $refund_money, $data = [])
    {
        $refund_type = $data['refund_type'] ?? 'back';

        // 判断退款方式
        if ($refund_type == 'back') {
            // 原路退回
            $refund_method = $pay->pay_type;
        } else {
            if ($pay->pay_type == 'score') {
                // 退积分
                $refund_method = 'score';
            } else if ($pay->pay_type == 'offline') {
                // 退积分
                $refund_method = 'offline';
            } else {
                // 退回到余额
                $refund_method = 'money';
            }
        }

        $refund = new RefundModel();

        $refund->refund_sn = get_sn($this->user->id, 'R');
        $refund->order_id = $pay->order_id;
        $refund->pay_id = $pay->id;
        $refund->pay_type = $pay->pay_type;
        $refund->refund_fee = $refund_money;
        $refund->refund_type = $refund_type;
        $refund->refund_method = $refund_method;
        $refund->status = RefundModel::STATUS_ING;
        $refund->platform = $data['platform'] ?? null;
        $refund->remark = $data['remark'] ?? null;
        $refund->save();

        return $refund;
    }



    /**
     * 完成退款单
     *
     * @param \think\Model $refund
     * @return \think\Model
     */
    private function completed($refund, $payment_json = '')
    {
        $refund->status = RefundModel::STATUS_COMPLETED;
        $refund->payment_json = $payment_json;
        $refund->save();

        return $refund;
    }


    /**
     * 检查 pay 并且完成退款
     *
     * @param \think\Model $pay
     * @return void
     */
    private function checkPayAndRefunded($pay)
    {
        $pay = PayModel::where('id', $pay->id)->find();

        if ($pay->refund_fee >= $pay->pay_fee) {
            // 退款完成了
            $pay->status = PayModel::PAY_STATUS_REFUND;
            $pay->save();
        }
    }
}
