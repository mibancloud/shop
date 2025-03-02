<?php

namespace addons\shopro\job\trade;

use addons\shopro\job\BaseJob;
use think\queue\Job;
use think\Db;
use think\exception\HttpResponseException;
use addons\shopro\exception\ShoproException;
use app\admin\model\shopro\trade\Order;

/**
 * 订单自动操作
 */
class OrderAutoOper extends BaseJob
{

    /**
     * 订单自动关闭
     */
    public function autoClose(Job $job, $data)
    {
        try {
            $order = $data['order'];

            // 重新查询订单
            $order = Order::unpaid()->where('id', $order['id'])->find();

            if ($order) {
                Db::transaction(function () use ($order, $data) {
                    // 执行关闭
                    $order->status = Order::STATUS_CLOSED;
                    $order->ext = array_merge($order->ext, ['closed_time' => time()]);      // 取消时间
                    $order->save();
                });
            }

            // 删除 job
            $job->delete();
        } catch (ShoproException $e) {
            // 自定义异常时删除 队列
            $job->delete();
            format_log_error($e, 'TradeOrderAutoOper.autoClose.ShoproException');
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'TradeOrderAutoOper.autoClose.HttpResponseException', $message);
        } catch (\Exception $e) {
            // 队列执行失败
            format_log_error($e, 'TradeOrderAutoOper.autoClose');
        }
    }
}
