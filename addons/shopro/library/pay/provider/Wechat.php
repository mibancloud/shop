<?php

namespace addons\shopro\library\pay\provider;

use think\Log;
use think\exception\HttpResponseException;
use Yansongda\Pay\Pay;

class Wechat extends Base
{
    protected $payService = null;
    protected $platform = null;

    public function __construct($payService, $platform = null) 
    {
        $this->payService = $payService;

        $this->platform = $platform;
    }



    public function pay($order, $config = []) 
    {
        $this->init('wechat', $config);

        if (isset($this->config['wechat']['default']['mode']) && $this->config['wechat']['default']['mode'] === 2) {
            if (in_array($this->platform, ['WechatOfficialAccount', 'WechatMiniProgram'])) {
                $order['payer']['sub_openid'] = $order['payer']['openid'] ?? '';
                unset($order['payer']['openid']);
            }
        }

        $order['amount']['total'] = intval(bcmul($order['total_amount'], '100'));        // 按分 为单位

        if ($this->platform == 'H5') {
            $order['_type'] = 'app';        // 使用 配置中的 app_id 字段
            $order['scene_info'] = [
                'payer_client_ip' => request()->ip(),
                'h5_info' => [
                    'type' => 'Wap',
                ]
            ];
        }

        unset($order['order_id'], $order['total_amount']);
        $method = $this->getMethod('wechat');
        $result = Pay::wechat()->$method($order);

        return $result;
    }



    public function transfer($payload, $config = [])
    {
        $this->init('wechat', $config, 'sub_mch');

        $code = 0;
        $payload['total_amount'] = intval(bcmul($payload['total_amount'], '100'));

        foreach ($payload['transfer_detail_list'] as $key => &$detail) {
            $detail['transfer_amount'] = intval(bcmul($detail['transfer_amount'], '100'));
        }
        if (isset($this->config['wechat']['default']['_type'])) {
            // 为了能正常获取 appid
            $payload['_type'] = $this->config['wechat']['default']['_type'];
        }

        // $payload['authorization_type'] = 'INFORMATION_AUTHORIZATION_TYPE';
        $payload['authorization_type'] = 'FUND_AUTHORIZATION_TYPE';
        // $payload['authorization_type'] = 'INFORMATION_AND_FUND_AUTHORIZATION_TYPE';

        $response = Pay::wechat()->transfer($payload);
        if (isset($response['batch_id']) && $response['batch_id']) {
            $code = 1;
        }

        return [$code, $response];
    }


    public function notify($callback, $config = [])
    {
        $this->init('wechat', $config);
        try {
            $originData = Pay::wechat()->callback(); // 是的，验签就这么简单！
            // {
            //     "id": "a5c68a7c-5474-5151-825d-88b4143f8642",
            //     "create_time": "2022-06-20T16:16:12+08:00",
            //     "resource_type": "encrypt-resource",
            //     "event_type": "TRANSACTION.SUCCESS",
            //     "summary": "支付成功",
            //     "resource": {
            //         "original_type": "transaction",
            //         "algorithm": "AEAD_AES_256_GCM",
            //         "ciphertext": {
            //             "mchid": "1623831039",
            //             "appid": "wx43051b2afa4ed3d0",
            //             "out_trade_no": "P202204155176122100021000",
            //             "transaction_id": "4200001433202206201698588194",
            //             "trade_type": "JSAPI",
            //             "trade_state": "SUCCESS",
            //             "trade_state_desc": "支付成功",
            //             "bank_type": "OTHERS",
            //             "attach": "",
            //             "success_time": "2022-06-20T16:16:12+08:00",
            //             "payer": {
            //                 "openid": "oRj5A44G6lgCVENzVMxZtoMfNeww"
            //             },
            //             "amount": {
            //                 "total": 1,
            //                 "payer_total": 1,
            //                 "currency": "CNY",
            //                 "payer_currency": "CNY"
            //             }
            //         },
            //         "associated_data": "transaction",
            //         "nonce": "qoJzoS9MCNgu"
            //     }
            // }
            Log::write('pay-notify-origin-data：' . json_encode($originData));
            if ($originData['event_type'] == 'TRANSACTION.SUCCESS') {
                // 支付成功回调
                $data = $originData['resource']['ciphertext'] ?? [];
                if (isset($data['trade_state']) && $data['trade_state'] == 'SUCCESS') {
                    // 交易成功
                    $data['pay_fee'] = ($data['amount']['total'] / 100);
                    $data['notify_time'] = date('Y-m-d H:i:s', strtotime((string)$data['success_time']));
                    $data['buyer_info'] = $data['payer']['openid'] ?? ($data['payer']['sub_openid'] ?? '');

                    $result = $callback($data, $originData);
                    return $result;
                }

                return 'fail';
            } else {
                // 微信交易未成功，返回 false，让微信再次通知
                Log::error('notify-error:交易未成功:' . $originData['event_type']);
                return 'fail';
            }
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'wechatNotify.HttpResponseException', $message);
            return 'fail';
        } catch (\Exception $e) {
            format_log_error($e, 'wechatNotify');
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
        $config['notify_url'] = $config['notify_url'] ?? request()->domain() . '/addons/shopro/pay/notifyRefund/payment/wechat/platform/' . $this->platform;
        $order_data['notify_url'] = $config['notify_url'];

        $this->init('wechat', $config);

        $order_data['amount']['total'] = intval(bcmul($order_data['amount']['total'], '100'));
        $order_data['amount']['refund'] = intval(bcmul($order_data['amount']['refund'], '100'));

        $result = Pay::wechat()->refund($order_data);
        Log::write('pay-refund-origin-data：' . json_encode($result, JSON_UNESCAPED_UNICODE));
        // {   返回数据字段
        //     "amount": {
        //         "currency": "CNY",
        //         "discount_refund": 0,
        //         "from": [],
        //         "payer_refund": 1,
        //         "payer_total": 1,
        //         "refund": 1,
        //         "settlement_refund": 1,
        //         "settlement_total": 1,
        //         "total": 1
        //     },
        //     "channel": "ORIGINAL",
        //     "create_time": "2022-06-20T19:06:36+08:00",
        //     "funds_account": "AVAILABLE",
        //     "out_refund_no": "R202207063668479227002100",
        //     "out_trade_no": "P202205155977315528002100",
        //     "promotion_detail": [],
        //     "refund_id": "50301802252022062021833667769",
        //     "status": "PROCESSING",
        //     "transaction_id": "4200001521202206207964248014",
        //     "user_received_account": "\u652f\u4ed8\u7528\u6237\u96f6\u94b1"
        // }

        return $result;
    }



    /**
     * 微信退款回调
     *
     * @param array $callback
     * @param array $config
     * @return array
     */
    public function notifyRefund($callback, $config = [])
    {
        $this->init('wechat', $config);
        try {
            $originData = Pay::wechat()->callback(); // 是的，验签就这么简单！
            Log::write('pay-notifyrefund-callback-data:' . json_encode($originData));
            // {
            //     "id": "4a553265-1f28-53a3-9395-8d902b902462",
            //     "create_time": "2022-06-21T11:25:33+08:00",
            //     "resource_type": "encrypt-resource",
            //     "event_type": "REFUND.SUCCESS",
            //     "summary": "\u9000\u6b3e\u6210\u529f",
            //     "resource": {
            //         "original_type": "refund",
            //         "algorithm": "AEAD_AES_256_GCM",
            //         "ciphertext": {
            //             "mchid": "1623831039",
            //             "out_trade_no": "P202211233042122753002100",
            //             "transaction_id": "4200001417202206214219765470",
            //             "out_refund_no": "R202211252676008994002100",
            //             "refund_id": "50300002272022062121864292533",
            //             "refund_status": "SUCCESS",
            //             "success_time": "2022-06-21T11:25:33+08:00",
            //             "amount": {
            //                 "total": 1,
            //                 "refund": 1,
            //                 "payer_total": 1,
            //                 "payer_refund": 1
            //             },
            //             "user_received_account": "\u652f\u4ed8\u7528\u6237\u96f6\u94b1"
            //         },
            //         "associated_data": "refund",
            //         "nonce": "8xfQknYyLVop"
            //     }
            // }

            if ($originData['event_type'] == 'REFUND.SUCCESS') {
                // 支付成功回调
                $data = $originData['resource']['ciphertext'] ?? [];
                if (isset($data['refund_status']) && $data['refund_status'] == 'SUCCESS') {
                    // 退款成功
                    $result = $callback($data, $originData);
                    return $result;
                }

                return 'fail';
            } else {
                // 微信交易未成功，返回 false，让微信再次通知
                Log::error('notify-error:退款未成功:' . $originData['event_type']);
                return 'fail';
            }
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'wechatNotifyRefund.HttpResponseException', $message);
            return 'fail';
        } catch (\Exception $e) {
            format_log_error($e, 'wechatNotifyRefund');
            return 'fail';
        }
    }




    /**
     * 格式化支付参数
     *
     * @param [type] $params
     * @return void
     */
    protected function formatConfig($config, $data = [], $type = 'normal')
    {
        if ($config['mode'] == 2 && $type == 'sub_mch') {
            // 服务商模式，但需要子商户直连 ，重新定义 config(商家转账到零钱)
            $config = [
                'mch_id' => $config['sub_mch_id'],
                'mch_secret_key' => $config['sub_mch_secret_key'],
                'mch_secret_cert' => $config['sub_mch_secret_cert'],
                'mch_public_cert_path' => $config['sub_mch_public_cert_path'],
            ];
            $config['mode'] = 0;        // 临时改为普通商户
        }

        if ($config['mode'] === 2) {
            // 首先将平台配置的 app_id 初始化到配置中
            $config['mp_app_id'] = $config['app_id'];       // 服务商关联的公众号的 appid
            $config['sub_app_id'] = $data['app_id'];        // 服务商特约子商户
        } else {
            $config['app_id'] = $data['app_id'];
        }
        
        switch ($this->platform) {
            case 'WechatMiniProgram':
                $config['_type'] = 'mini';          // 小程序提现，需要传 _type = mini 才能正确获取到 appid
                if ($config['mode'] === 2) {
                    $config['sub_mini_app_id'] = $config['sub_app_id'];
                    unset($config['sub_app_id']);
                } else {
                    $config['mini_app_id'] = $config['app_id'];
                    unset($config['app_id']);
                }
                break;
            case 'WechatOfficialAccount':
                $config['_type'] = 'mp';          // 小程序提现，需要传 _type = mp 才能正确获取到 appid
                if ($config['mode'] === 2) {
                    $config['sub_mp_app_id'] = $config['sub_app_id'];
                    unset($config['sub_app_id']);
                } else {
                    $config['mp_app_id'] = $config['app_id'];
                    unset($config['app_id']);
                }
                break;
            case 'App':
            case 'H5':
            default:
                break;
        }

        $config['notify_url'] = request()->domain() . '/addons/shopro/pay/notify/payment/wechat/platform/' . $this->platform;
        $config['mch_secret_cert'] = ROOT_PATH . 'public' . $config['mch_secret_cert'];
        $config['mch_public_cert_path'] = ROOT_PATH . 'public' . $config['mch_public_cert_path'];

        return $config;
    }
}