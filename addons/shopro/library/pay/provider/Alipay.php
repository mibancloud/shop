<?php

namespace addons\shopro\library\pay\provider;

use think\Log;
use think\exception\HttpResponseException;
use addons\shopro\service\pay\PayRefund;
use Yansongda\Pay\Pay;

class Alipay extends Base
{
    protected $payService = null;
    protected $pay = null;
    protected $platform = null;

    public function __construct($payService, $platform = null) 
    {
        $this->payService = $payService;

        $this->platform = $platform;
    }



    public function pay($order, $config = [], $from = null) 
    {
        $this->init('alipay', $config);

        if (in_array($this->platform, ['WechatOfficialAccount', 'WechatMiniProgram', 'H5'])) {
            // 返回支付宝支付链接
            if (!$from == 'url') {
                // return request()->domain() . '/shop/api/pay/alipay?pay_sn=' . $order['out_trade_no'] . '&platform=' . $this->platform . '&return_url=' . urlencode($this->config['alipay']['default']['return_url']);
                return request()->domain() . '/addons/shopro/pay/alipay?pay_sn=' . $order['out_trade_no'] . '&platform=' . $this->platform . '&return_url=' . urlencode($this->config['alipay']['default']['return_url']);
            }
        } 

        $method = $this->getMethod('alipay');
        $result = Pay::alipay()->$method($order);

        return $result;
    }



    public function transfer($payload, $config = [])
    {
        $this->init('alipay', $config);

        $code = 0;
        $response = Pay::alipay()->transfer($payload);
        if ($response['code'] === '10000' && $response['status'] === 'SUCCESS') {
            $code = 1;
        }

        return [$code, $response];
    }


    public function notify($callback, $config = [])
    {
        $this->init('alipay', $config);
        try {
            $data = Pay::alipay()->callback(); // 是的，验签就这么简单！

            // {    // 支付宝支付成功回调参数
            //     "gmt_create": "2022-06-21 14:54:39",
            //     "charset": "utf-8",
            //     "seller_email": "xptech@qq.com",
            //     "subject": "\u5546\u57ce\u8ba2\u5355\u652f\u4ed8",
            //     "buyer_id": "2088902485164146",
            //     "invoice_amount": "0.01",
            //     "notify_id": "2022062100222145440064141420932810",
            //     "fund_bill_list": "[{\"amount\":\"0.01\",\"fundChannel\":\"ALIPAYACCOUNT\"}]",
            //     "notify_type": "trade_status_sync",
            //     "trade_status": "TRADE_SUCCESS",
            //     "receipt_amount": "0.01",
            //     "buyer_pay_amount": "0.01",
            //     "app_id": "2021001114666742",
            //     "seller_id": "2088721922277739",
            //     "gmt_payment": "2022-06-21 14:54:40",
            //     "notify_time": "2022-06-21 14:54:41",
            //     "version": "1.0",
            //     "out_trade_no": "P202202383569762189002100",
            //     "total_amount": "0.01",
            //     "trade_no": "2022062122001464141435375324",
            //     "auth_app_id": "2021001114666742",
            //     "buyer_logon_id": "157***@163.com",
            //     "point_amount": "0.00"
            // }

            // {    // 支付宝退款成功（交易关闭）回调参数
            //     "gmt_create": "2022-06-21 15:31:34",
            //     "charset": "utf-8",
            //     "seller_email": "xptech@qq.com",
            //     "gmt_payment": "2022-06-21 15:31:34",
            //     "notify_time": "2022-06-21 15:53:32",
            //     "subject": "商城订单支付",
            //     "gmt_refund": "2022-06-21 15:53:32.158",
            //     "out_biz_no": "R202203533190902732002100",
            //     "buyer_id": "2088902485164146",
            //     "version": "1.0",
            //     "notify_id": "2022062100222155332064141421692866",
            //     "notify_type": "trade_status_sync",
            //     "out_trade_no": "P202203305611515511002100",
            //     "total_amount": "0.01",
            //     "trade_status": "TRADE_CLOSED",
            //     "refund_fee": "0.01",
            //     "trade_no": "2022062122001464141435383344",
            //     "auth_app_id": "2021001114666742",
            //     "buyer_logon_id": "157***@163.com",
            //     "gmt_close": "2022-06-21 15:53:32",
            //     "app_id": "2021001114666742",
            //     "seller_id": "2088721922277739"
            // }

            Log::write('pay-notify-origin-data：' . json_encode($data));
            // 判断是否是支付宝退款（支付宝退款成功会通知该接口）
            
            $out_trade_no = $data['out_trade_no'];          // 商户单号
            $out_refund_no = $data['out_biz_no'] ?? '';     // 退款单号
            if (
                $data['notify_type'] == 'trade_status_sync'      // 同步交易状态
                && $data['trade_status'] == 'TRADE_CLOSED'          // 交易关闭
                && $out_refund_no                                   // 退款单号
            ) {
                // 交给退款实例处理
                $refund = new PayRefund();
                $refund->notify([
                    'out_trade_no' => $out_trade_no,
                    'out_refund_no' => $out_refund_no,
                    'payment_json' => json_encode($data)
                ]);

                return Pay::alipay()->success();
            }

            // 判断支付宝是否是支付成功状态，如果不是，直接返回响应
            if ($data['trade_status'] != 'TRADE_SUCCESS') {
                // 不是交易成功的通知，直接返回成功
                return Pay::alipay()->success();
            }

            $data['pay_fee'] = $data['total_amount'];
            $data['transaction_id'] = $data['trade_no'];
            $data['buyer_info'] = $data['buyer_logon_id'];

            return $callback($data);
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'alipayNotify.HttpResponseException', $message);
            return 'fail';
        } catch (\Exception $e) {
            format_log_error($e, 'alipayNotify');
            return 'fail';
        }
    }


    /**
     * 退款
     *
     * @param array $order_data
     * @param array $config
     * @return array
     */
    public function refund($order_data, $config = [])
    {
        $this->init('alipay', $config);

        $result = Pay::alipay()->refund($order_data);
        Log::write('pay-refund-origin-data：' . json_encode($result, JSON_UNESCAPED_UNICODE));

        // {
        //     "code": "10000",
        //     "msg": "Success",
        //     "buyer_logon_id": "157***@163.com",
        //     "buyer_user_id": "2088902485164146",
        //     "fund_change": "Y",
        //     "gmt_refund_pay": "2022-06-21 15:53:32",
        //     "out_trade_no": "P202203305611515511002100",
        //     "refund_fee": "0.01",
        //     "send_back_fee": "0.00",
        //     "trade_no": "2022062122001464141435383344"
        // }

        return $result;
    }


    /**
     * 格式化支付参数
     *
     * @param [type] $params
     * @return void
     */
    protected function formatConfig($config, $data = [])
    {
        $config['notify_url'] = request()->domain() . '/addons/shopro/pay/notify/payment/alipay/platform/' . $this->platform;

        if (in_array($this->platform, ['WechatOfficialAccount', 'WechatMiniProgram', 'H5'])) {
            // app 支付不能带着个参数
            $config['return_url'] = str_replace('&amp;', '&', request()->param('return_url', ''));
        }

        $config = $this->formatCert($config);

        return $config;
    }


    /**
     * 拼接支付证书绝对地址
     *
     * @param array $config
     * @return array
     */
    protected function formatCert($config)
    {
        $end = substr($config['app_secret_cert'], -4);
        if ($end == '.crt') {
            $config['app_secret_cert'] = ROOT_PATH . 'public' . $config['app_secret_cert'];    
        }
        $config['alipay_public_cert_path'] = ROOT_PATH . 'public' . $config['alipay_public_cert_path'];
        $config['app_public_cert_path'] = ROOT_PATH . 'public' . $config['app_public_cert_path'];
        $config['alipay_root_cert_path'] = ROOT_PATH . 'public' . $config['alipay_root_cert_path'];

        return $config;
    }
}