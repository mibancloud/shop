<?php

namespace addons\shopro\controller;

use Psr\Http\Message\ResponseInterface;
use think\exception\HttpResponseException;
use app\admin\model\shopro\Pay as PayModel;
use addons\shopro\service\pay\PayOper;
use addons\shopro\library\pay\PayService;
use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\trade\Order as TradeOrderModel;
use think\Log;
use think\Db;
use addons\shopro\service\pay\PayRefund;
use Yansongda\Pay\Pay as YansongdaPay;

class Pay extends Common
{

    protected $noNeedLogin = ['alipay', 'notify', 'notifyRefund'];
    protected $noNeedRight = ['*'];

    public function prepay()
    {
        $this->repeatFilter();          // 防止连点

        check_env(['yansongda']);

        $user = auth_user();

        $order_sn = $this->request->post('order_sn');
        $payment = $this->request->post('payment');
        $openid = $this->request->post('openid', '');
        $money = $this->request->post('money', 0);
        $money = $money > 0 ? $money : 0;
        $platform = $this->request->header('platform');

        list($order, $order_type) = $this->getOrderInstance($order_sn);
        $order = $order->where('user_id', $user->id)->where('order_sn', $order_sn)->find();
        if (!$order) {
            $this->error(__('No Results were found'));
        }

        if (in_array($order->status, [$order::STATUS_CLOSED, $order::STATUS_CANCEL])) {
            $this->error('订单已失效');
        }

        if (in_array($order->status, [$order::STATUS_PAID, $order::STATUS_COMPLETED])) {
            $this->error('订单已支付');
        }

        if ($order_type == 'order' && $order->isOffline($order)) {
            // 已经货到付款
            $this->error('已下单成功');
        }

        if (!$payment || !in_array($payment, ['wechat', 'alipay', 'money', 'offline'])) {
            $this->error('支付类型不能为空');
        }

        // pay 实例
        $payOper = new PayOper();

        if ($money && $order_type == 'order') {
            // 余额混合支付
            $order = Db::transaction(function () use ($payOper, $order, $order_type, $money) {
                // 加锁读订单
                $order = $order->lock(true)->find($order->id);

                // 余额支付
                $order = $payOper->money($order, $money, $order_type);

                return $order;
            });

            if (in_array($order->status, [$order::STATUS_PAID, $order::STATUS_COMPLETED])) {
                $this->success('订单支付成功', $order);
            }
        }

        if ($payment == 'money' && $order_type == 'order') {
            // 余额支付
            $order = Db::transaction(function () use ($payOper, $order, $order_type) {
                // 加锁读订单
                $order = $order->lock(true)->find($order->id);

                $order = $payOper->money($order, $order->remain_pay_fee, $order_type);

                return $order;
            });

            if ($order->status != $order::STATUS_PAID) {
                $this->error('订单支付失败');
            }
            $this->success('订单支付成功', $order);
        }

        if ($payment == 'offline' && $order_type == 'order') {
            if (!isset($order->ext['offline_status']) || $order->ext['offline_status'] != 'enable') {
                $this->error('订单不支持货到付款');
            }
            // 货到付款
            $order = Db::transaction(function () use ($payOper, $order, $order_type) {
                // 加锁读订单
                $order = $order->lock(true)->find($order->id);

                $order = $payOper->offline($order, 0, $order_type);     // 增加 0 记录

                return $order;
            });

            if ($order->status != $order::STATUS_PAID) {
                $this->success('下单成功', $order);     // 货到付款
            }
            $this->success('订单支付成功', $order);
        }

        // 微信支付宝（第三方）付款
        $payModel = $payOper->{$payment}($order, $order->remain_pay_fee, $order_type);

        $order_data = [
            'order_id' => $order->id,
            'out_trade_no' => $payModel->pay_sn,
            'total_amount' => $payModel->pay_fee,      // 剩余支付金额
        ];

        // 微信公众号，小程序支付，必须有 openid
        if ($payment == 'wechat') {
            if (in_array($platform, ['WechatOfficialAccount', 'WechatMiniProgram'])) {
                if (isset($openid) && $openid) {
                    // 如果传的有 openid
                    $order_data['payer']['openid'] = $openid;
                } else {
                    // 没有 openid 默认拿下单人的 openid
                    $oauth = \app\admin\model\shopro\ThirdOauth::where([
                        'user_id' => $order->user_id,
                        'provider' => 'Wechat',
                        'platform' => lcfirst(str_replace('Wechat', '', $platform))
                    ])->find();

                    $order_data['payer']['openid'] = $oauth ? $oauth->openid : '';
                }

                if (empty($order_data['payer']['openid'])) {
                    // 缺少 openid
                    $this->error('miss_openid', -1);
                }
            }

            $order_data['description'] = '商城订单支付';
        } else {
            $order_data['subject'] = '商城订单支付';
        }

        $payService = new PayService($payment);

        try {
            $result = $payService->pay($order_data);
        } catch (\Yansongda\Pay\Exception\Exception $e) {
            $this->error('支付失败' . (config('app_debug') ? "：" . $e->getMessage() : '，请重试'));
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();

            $this->error('支付失败' . (config('app_debug') ? "：" . $message : '，请重试'));
        }

        if ($platform == 'App') {
            if ($payment == 'wechat') {
                // Yansongda\Supports\Collection，可当数组，可当字符串，这里不用处理
            } else {
                // Guzzle
                $result = $result->getBody()->getContents();
            }
        }

        $this->success('', [
            'pay_data' => $result,
        ]);
    }



    /**
     * 支付宝网页支付
     */
    public function alipay()
    {
        $pay_sn = $this->request->get('pay_sn');
        $platform = $this->request->get('platform');

        $payModel = PayModel::where('pay_sn', $pay_sn)->find();
        if (!$payModel || $payModel->status != PayModel::PAY_STATUS_UNPAID) {
            $this->error("支付单不存在或已支付");
        }

        try {
            $order_data = [
                'order_id' => $payModel->order_id,
                'out_trade_no' => $payModel->pay_sn,
                'total_amount' => $payModel->pay_fee,
                'subject' => '商城订单支付',
            ];

            $payService = new PayService('alipay', $platform);
            $result = $payService->pay($order_data, [], 'url');

            $result = $result->getBody()->getContents();

            echo $result;
        } catch (\Exception $e) {
            echo $e->getMessage();
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();

            echo '支付失败' . (config('app_debug') ? "：" . $message : '，请重试');
        }
    }



    /**
     * 支付成功回调
     */
    public function notify()
    {
        Log::write('pay-notify-comein:');

        $payment = $this->request->param('payment');
        $platform = $this->request->param('platform');

        $payService = new PayService($payment, $platform);

        $result = $payService->notify(function ($data, $originData = []) use ($payment) {
            Log::write('pay-notify-data:' . json_encode($data));

            $out_trade_no = $data['out_trade_no'];

            // 查询 pay 交易记录
            $payModel = PayModel::where('pay_sn', $out_trade_no)->find();
            if (!$payModel || $payModel->status != PayModel::PAY_STATUS_UNPAID) {
                // 订单不存在，或者订单已支付
                return YansongdaPay::$payment()->success();
            }

            Db::transaction(function () use ($payModel, $data, $originData, $payment) {
                $notify = [
                    'pay_sn' => $data['out_trade_no'],
                    'transaction_id' => $data['transaction_id'],
                    'notify_time' => $data['notify_time'],
                    'buyer_info' => $data['buyer_info'],
                    'payment_json' => $originData ? json_encode($originData) : json_encode($data),
                    'pay_fee' => $data['pay_fee'],          // 微信的已经*100处理过了
                    'pay_type' => $payment              // 支付方式
                ];

                // pay 实例
                $payOper = new PayOper($payModel->user_id);
                $payOper->notify($payModel, $notify);
            });

            return YansongdaPay::$payment()->success();
        });

        return $this->payResponse($result, $payment);
    }



    /**
     * 微信退款回调 (仅微信用，支付宝走支付回调 notify 方法)
     *
     * @return void
     */
    public function notifyRefund() 
    {
        Log::write('pay-notify-refund-comein:');

        $payment = $this->request->param('payment');
        $platform = $this->request->param('platform');

        $payService = new PayService($payment, $platform);

        $result = $payService->notifyRefund(function ($data, $originData) use ($payment, $platform) {
            Log::write('pay-notify-refund-result:' . json_encode($data));

            Db::transaction(function () use ($data, $originData, $payment) {
                $out_refund_no = $data['out_refund_no'];
                $out_trade_no = $data['out_trade_no'];

                // 交给退款实例处理
                $refund = new PayRefund();
                $refund->notify([
                    'out_trade_no' => $out_trade_no,
                    'out_refund_no' => $out_refund_no,
                    'payment_json' => json_encode($originData),
                ]);
            });

            return YansongdaPay::$payment()->success();
        });

        return $this->payResponse($result, $payment);
    }


    /**
     * 处理返回结果 tp5 不能直接 return YansongdaPay::$payment()->success()
     *
     * @param object|string $result
     * @param string|null $payment
     * @return void
     */
    private function payResponse($result = null, $payment = null)
    {
        if ($result instanceof ResponseInterface) {
            $content = $result->getBody()->getContents();
            $content = $payment == 'wechat' ? json_decode($content, true) : $content;
            return response($content, 200, [], ($payment == 'wechat' ? 'json' : ''));
        }

        return $result;
    }



    /**
     * 根据订单号获取订单实例
     *
     * @param [type] $order_sn
     * @return void
     */
    private function getOrderInstance($order_sn)
    {
        if (strpos($order_sn, 'TO') === 0) {
            // 交易订单
            $order_type = 'trade_order';
            $order = new TradeOrderModel();
        } else {
            // 订单
            $order_type = 'order';
            $order = new Order();
        }

        return [$order, $order_type];
    }
}
