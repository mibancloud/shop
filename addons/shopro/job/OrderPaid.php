<?php

namespace addons\shopro\job;

use think\queue\Job;
use think\Db;
use think\exception\HttpResponseException;
use app\admin\model\shopro\order\Order;
use addons\shopro\service\StockSale;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\order\Invoice as OrderInvoice;
use addons\shopro\service\order\OrderOper;
use addons\shopro\facade\Activity as ActivityFacade;

/**
 * 订单自动操作
 */
class OrderPaid extends BaseJob
{

    /**
     * 订单支付完成
     */
    public function paid(Job $job, $data)
    {
        try {
            $order = $data['order'];
            $user = $data['user'];

            $order = Order::with('items')->where('id', $order['id'])->find();
            $user = User::get($user['id']);

            // 数据库删订单的问题常见，这里被删的订单直接把队列移除
            if ($order) {
                Db::transaction(function () use ($order, $user, $data) {
                    // 订单减库存
                    $stockSale = new StockSale();
                    $stockSale->forwardStockSale($order);

                    // 处理发票审核改为等待开具
                    if ($order->invoice_status == 1) {
                        $invoice = OrderInvoice::where('order_id', $order->id)->find();
                        if ($invoice) {
                            $invoice->status = 'waiting';
                            $invoice->save();
                        }
                    }

                    // 处理活动，加入拼团，完成拼团，添加赠品记录等
                    ActivityFacade::buyOk($order, $user);

                    // 将订单参与活动信息改为已支付
                    $orderOper = new OrderOper();
                    $orderOper->activityOrderPaid($order);
                    
                    // 触发订单支付完成事件
                    $data = ['order' => $order, 'user' => $user];
                    \think\Hook::listen('order_paid_after', $data);
                });
            }

            // 删除 job
            $job->delete();
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'OrderPaid.paid.HttpResponseException', $message);
        } catch (\Exception $e) {
            // 队列执行失败
            format_log_error($e, 'OrderPaid.paid');
        }
    }

    /**
     * 订单选择线下支付(货到付款)完成
     */
    public function offline(Job $job, $data)
    {
        try {
            $order = $data['order'];
            $user = $data['user'];

            $order = Order::with('items')->where('id', $order['id'])->find();
            $user = User::get($user['id']);

            // 数据库删订单的问题常见，这里被删的订单直接把队列移除
            if ($order) {
                Db::transaction(function () use ($order, $user, $data) {
                    // 订单减库存
                    $stockSale = new StockSale();
                    $stockSale->forwardStockSale($order);

                    // 处理活动，加入拼团，完成拼团，添加赠品记录等
                    ActivityFacade::buyOk($order, $user);

                    // 触发订单选择线下支付(货到付款)完成事件
                    $data = ['order' => $order, 'user' => $user];
                    \think\Hook::listen('order_offline_after', $data);
                });
            }

            // 删除 job
            $job->delete();
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'OrderPaid.offline.HttpResponseException', $message);
        } catch (\Exception $e) {
            // 队列执行失败
            format_log_error($e, 'OrderPaid.offline');
        }
    }
}