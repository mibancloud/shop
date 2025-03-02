<?php

namespace addons\shopro\library\easywechatPlus;

use addons\shopro\exception\ShoproException;
use addons\shopro\service\order\shippingInfo\OrderShippingInfo;
use addons\shopro\service\order\shippingInfo\TradeOrderShippingInfo;
use app\admin\model\shopro\data\WechatExpress;

/**
 * 补充 小程序购物订单
 */
class WechatMiniProgramShop extends EasywechatPlus
{


    /**
     * 上送订单信息
     *
     * @param object|array $order
     * @return void
     */
    public function uploadShippingInfos($order, $express = null, $type = 'send')
    {
        try {
            $orderShippingInfo = new OrderShippingInfo($order);

            if ($type == 'change') {
                $uploadParams = $orderShippingInfo->getChangeShippingParams($express);
            } else {
                $uploadParams = $orderShippingInfo->getShippingParams();
            }

            // 设置消息跳转地址
            $this->setMessageJumpPath();

            $length = count($uploadParams);
            foreach ($uploadParams as $key => $params) {
                $params['delivery_mode'] = 1;
                $params['is_all_delivered'] = true;

                if ($params['logistics_type'] == 1 && count($params['shipping_list']) > 1) {
                    // 快递物流，并且
                    $params['delivery_mode'] = 2;
                }

                if ($length > 1) {
                    if ($key == ($length - 1)) {
                        // 最后一条
                        $params['is_all_delivered'] = true;         // 发货完成
                    } else {
                        $params['is_all_delivered'] = false;        // 发货未完成
                    }
                }

                $params['upload_time'] = date(DATE_RFC3339);

                \think\Log::error('发货信息录入' . json_encode($params));
                $result = $this->uploadShippingInfo($params);

                if ($result['errcode'] != 0) {
                    throw new ShoproException('获取失败: errcode:' . $result['errcode'] . '; errmsg:' . $result['errmsg']);
                }
            }
        } catch (\Exception $e) {
            format_log_error($e, 'upload_shipping_info', '发货信息录入错误');
        }
    }



    /**
     * trade 订单上送订单信息
     *
     * @param object|array $order
     * @return void
     */
    public function tradeUploadShippingInfos($order)
    {
        try {
            $orderShippingInfo = new TradeOrderShippingInfo($order);

            $uploadParams = $orderShippingInfo->getShippingParams();

            // 将确认收货跳转地址设置为空，trade 商城系统不需要确认收货
            $this->setMessageJumpPath(true, '');

            $length = count($uploadParams);
            foreach ($uploadParams as $key => $params) {
                $params['delivery_mode'] = 1;
                $params['is_all_delivered'] = true;
                $params['upload_time'] = date(DATE_RFC3339);

                \think\Log::error('发货信息录入' . json_encode($params));
                $result = $this->uploadShippingInfo($params);

                if ($result['errcode'] != 0) {
                    throw new ShoproException('获取失败: errcode:' . $result['errcode'] . '; errmsg:' . $result['errmsg']);
                }
            }
        } catch (\Exception $e) {
            format_log_error($e, 'upload_shipping_info', '发货信息录入错误');
        }
    }


    /**
     * 将发货信息提交给微信
     *
     * @param array $params 上送参数
     * @return void
     */
    private function uploadShippingInfo($params)
    {
        $access_token = $this->getAccessToken();

        $add_template_url = "https://api.weixin.qq.com/wxa/sec/order/upload_shipping_info";
        $result = \addons\shopro\facade\HttpClient::request('post', $add_template_url, [
            'body' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'query' => ["access_token" => $access_token['access_token']],
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $result = $result->getBody()->getContents();
        return json_decode($result, true);
    }



    /**
     * 检测并且设置微信消息跳转地址
     *
     * @param boolean $exception
     * @param boolean $is_force
     * @return boolean
     */
    public function checkAndSetMessageJumpPath($exception = false, $is_force = false)
    {
        try {
            // 查询是否又微信发货管理权限
            if ($this->isTradeManaged($is_force)) {
                // 有权限，设置消息跳转地址
                $this->setMessageJumpPath($is_force);
            }

            return true;
        } catch (\Exception $e) {
            format_log_error($e, 'checkAndSetMessageJumpPath', '自动设置微信小程序发货信息管理消息跳转路径失败');
            if ($exception) {
                // 抛出异常
                throw new ShoproException($e->getMessage());
            }
        }

        return false;
    }


    /**
     * 查询是否有微信发货信息管理权限 (48001 时当没有权限处理，不抛出异常)
     *
     * @param boolean $is_force
     * @return boolean
     */
    public function isTradeManaged($is_force = false)
    {
        $key = 'wechat:is_trade_managed';
        if (!$is_force && redis_cache('?' . $key)) {
            return redis_cache($key);     // 直接返回是否有权限
        }

        $access_token = $this->getAccessToken();
        $add_template_url = "https://api.weixin.qq.com/wxa/sec/order/is_trade_managed";

        $mini_appid = sheep_config('shop.platform.WechatMiniProgram.app_id');
        if (!$mini_appid) {
            // 没有配置微信小程序参数
            throw new ShoproException('微信小程序发货管理查询失败，没有配置微信小程序');
        }

        $params = [
            'appid' => $mini_appid
        ];
        $result = \addons\shopro\facade\HttpClient::request('post', $add_template_url, [
            'body' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'query' => ["access_token" => $access_token['access_token']],
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $result = $result->getBody()->getContents();
        $result = json_decode($result, true);

        if ($result['errcode'] != 0 && $result['errcode'] != '48001') {
            // 48001 时不抛出异常，当没有权限处理
            throw new ShoproException('查询是否有微信发货信息管理权限失败: errcode:' . $result['errcode'] . '; errmsg:' . $result['errmsg']);
        }

        $is_trade_managed = isset($result['is_trade_managed']) ? intval($result['is_trade_managed']) : 0;

        redis_cache($key, $is_trade_managed, 7200);       // 缓存结果,两小时

        return $is_trade_managed;
    }



    /**
     * 设置微信消息跳转路径
     *
     * @param boolean $is_force
     * @return void
     */
    public function setMessageJumpPath($is_force = false, $path = 'pages/order/detail?comein_type=wechat')
    {
        if (!$is_force && redis_cache('?wechat:set_message_jump_path')) {
            // 已经设置过了，无需再次设置
            return true;
        }

        $access_token = $this->getAccessToken();
        $add_template_url = "https://api.weixin.qq.com/wxa/sec/order/set_msg_jump_path";

        $params = [
            'path' => $path
        ];
        $result = \addons\shopro\facade\HttpClient::request('post', $add_template_url, [
            'body' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'query' => ["access_token" => $access_token['access_token']],
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $result = $result->getBody()->getContents();
        $result = json_decode($result, true);

        if ($result['errcode'] != 0) {
            throw new ShoproException('设置微信发货消息跳转地址失败: errcode:' . $result['errcode'] . '; errmsg:' . $result['errmsg']);
        }

        if ($is_force) {
            // 充值订单发货时，清掉缓存
            redis_cache('wechat:set_message_jump_path', null);      // 清除缓存
        } else {
            redis_cache('wechat:set_message_jump_path', time());      // 永久有效
        }

        return $result;
    }



    /**
     * 获取微信delivery数据
     *
     * @param boolean $is_force
     * @return void
     */
    public function getDelivery($is_force = false)
    {
        if (!$is_force && redis_cache('?wechat:get_delivery_list')) {
            // 已经设置过了，无需再次设置
            return true;
        }

        $access_token = $this->getAccessToken();
        $get_delivery_url = "https://api.weixin.qq.com/cgi-bin/express/delivery/open_msg/get_delivery_list";

        $result = \addons\shopro\facade\HttpClient::request('post', $get_delivery_url, [
            'body' => '{}',
            'query' => ["access_token" => $access_token['access_token']],
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $result = $result->getBody()->getContents();
        $result = json_decode($result, true);

        if ($result['errcode'] != 0) {
            throw new ShoproException('获取微信 delivery 列表失败: errcode:' . $result['errcode'] . '; errmsg:' . $result['errmsg']);
        }

        // 存库
        $datas = $result['delivery_list'];
        foreach ($datas as $data) {
            $wechatExpress = WechatExpress::where('code', $data['delivery_id'])->find();
            $current = [
                'name' => $data['delivery_name'] ?? '',
                'code' => $data['delivery_id'] ?? '',
            ];

            if (!$wechatExpress) {
                $wechatExpress = new WechatExpress();
            }

            $wechatExpress->save($current);
        }

        redis_cache('wechat:get_delivery_list', time());      // 永久有效

        return $result;
    }


    /**
     * 方法转发到 easywechat
     *
     * @param string $funcname
     * @param array $arguments
     * @return void
     */
    public function __call($funcname, $arguments)
    {
        // if ($funcname == 'deletePrivateTemplate') {
        //     return $this->app->template_message->{$funcname}(...$arguments);
        // }

        return $this->app->{$funcname}(...$arguments);
    }
}
