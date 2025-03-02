<?php

namespace addons\shopro\job\trade;

use addons\shopro\job\BaseJob;
use think\queue\Job;
use think\Db;
use think\exception\HttpResponseException;
use app\admin\model\shopro\trade\Order;
use app\admin\model\shopro\user\User;
use addons\shopro\service\Wallet as WalletService;
use addons\shopro\facade\Wechat;
use addons\shopro\library\easywechatPlus\WechatMiniProgramShop;

/**
 * 订单自动操作
 */
class OrderPaid extends BaseJob
{

    /**
     * 交易订单支付完成
     */
    public function paid(Job $job, $data)
    {
        try {
            $order = $data['order'];
            $user = $data['user'];

            $order = Order::where('id', $order['id'])->find();
            $user = User::get($user['id']);

            // 数据库删订单的问题常见，这里被删的订单直接把队列移除
            if ($order) {
                Db::transaction(function () use ($order, $user, $data) {
                    if ($order->type == 'recharge') {
                        // 充值
                        $ext = $order->ext;
                        $rule = $ext['rule'] ?? [];
                        $money = (isset($rule['money']) && $rule['money'] > 0) ? $rule['money'] : 0;
                        $gift_type = $rule['gift_type'] ?? 'money';
                        $gift = (isset($rule['gift']) && $rule['gift'] > 0) ? $rule['gift'] : 0;
                        if ($money > 0) {
                            // 增加余额
                            WalletService::change($user, 'money', $money, 'order_recharge', [
                                'order_id' => $order->id,
                                'order_sn' => $order->order_sn,
                                'order_type' => 'trade_order',
                            ]);
                        }

                        if ($gift > 0) {
                            // 充值赠送
                            WalletService::change($user, $gift_type, $gift, 'recharge_gift', [
                                'order_id' => $order->id,
                                'order_sn' => $order->order_sn,
                                'order_type' => 'trade_order',
                            ]);
                        }
                    }


                    $uploadshoppingInfo = new WechatMiniProgramShop(Wechat::miniProgram());

                    // 微信小程序，使用 微信支付, 并且存在微信发货管理权限时，才推送发货消息
                    if ($order->platform == 'WechatMiniProgram' && $order->pay_type == 'wechat' && $uploadshoppingInfo->isTradeManaged()) {
                        $uploadshoppingInfo->tradeUploadShippingInfos($order);
                    }
                });
            }

            // 删除 job
            $job->delete();
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'OrderPaid.paid.HttpResponseException', $message);
        } catch (\Exception $e) {
            format_log_error($e, 'OrderPaid.paid');
        }
    }
}
