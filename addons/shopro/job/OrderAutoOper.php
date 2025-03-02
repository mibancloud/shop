<?php

namespace addons\shopro\job;

use think\queue\Job;
use think\Log;
use think\Db;
use think\Collection;
use think\exception\HttpResponseException;
use addons\shopro\exception\ShoproException;
use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\order\OrderItem;
use addons\shopro\service\order\OrderOper;
use app\admin\model\shopro\order\Action;

/**
 * 订单自动操作
 */
class OrderAutoOper extends BaseJob
{

    /**
     * 订单自动关闭
     */
    public function autoClose(Job $job, $data){
        if (check_env('yansongda', false)) {
            set_addon_config('epay', ['version' => 'v3'], false);
            \think\Hook::listen('epay_config_init');
        }
        
        try {
            $order = $data['order'];

            // 重新查询订单
            $order = Order::unpaid()->where('id', $order['id'])->find();

            if ($order) {
                Db::transaction(function () use ($order, $data) {
                    $orderOper = new OrderOper();
                    $order = $orderOper->close($order, null, 'system');

                    return $order;
                });
            }

            // 删除 job
            $job->delete();
        } catch (ShoproException $e) {
            // 自定义异常时删除 队列
            $job->delete();

            format_log_error($e, 'OrderAutoOper.autoClose.sheepException');
        } catch (HttpResponseException $e) {
            // error_stop 异常时删除 队列
            $job->delete();

            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'OrderAutoOper.autoClose.HttpResponseException', $message);
        } catch (\Exception $e) {
            // 队列执行失败
            format_log_error($e, 'OrderAutoOper.autoClose');
        }
    }


    /**
     * 订单自动确认收货
     */
    public function autoConfirm(Job $job, $data) {
        try {
            $order = $data['order'];
            $items = $data['items'];
            $itemIds = $items instanceof Collection ? $items->column('id') : array_column($items, 'id');

            // 重新查询订单
            $order = Order::paid()->where('id', $order['id'])->find();      // 货到付款的 还未付款的订单被排除在外，不会自动收货

            if ($order) {
                $order = Db::transaction(function () use ($order, $itemIds) {
                    $orderOper = new OrderOper();
                    $order = $orderOper->confirm($order, $itemIds, null, 'system');
    
                    return $order;
                });
            }

            // 删除 job
            $job->delete();
        } catch (ShoproException $e) {
            // 自定义异常时删除 队列
            $job->delete();

            format_log_error($e, 'OrderAutoOper.autoConfirm.sheepException');
        } catch (HttpResponseException $e) {
            // error_stop 异常时删除 队列
            $job->delete();

            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'OrderAutoOper.autoConfirm.HttpResponseException', $message);
        } catch (\Exception $e) {
            // 队列执行失败
            format_log_error($e, 'OrderAutoOper.autoConfirm');
        }
    }



    public function autoComment(Job $job, $data) {
        try {
            $order = $data['order'];
            $item = $data['item'];

            // 重新查询订单
            $order = Order::paid()->where('id', $order['id'])->find();

            if ($order && $item) {
                $order = Db::transaction(function () use ($order, $item) {
                    $orderOper = new OrderOper();

                    $comments[] = [
                        'item_id' => $item['id'],
                        'level' => 5,
                    ];
                    
                    // 评价一个订单商品
                    $order = $orderOper->comment($order, $comments, null, 'system');

                    return $order;
                });
            }

            // 删除 job
            $job->delete();
        } catch (ShoproException $e) {
            // 自定义异常时删除 队列
            $job->delete();

            format_log_error($e, 'OrderAutoOper.autoComment.sheepException');
        } catch (HttpResponseException $e) {
            // error_stop 异常时删除 队列
            $job->delete();

            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'OrderAutoOper.autoComment.HttpResponseException', $message);
        } catch (\Exception $e) {
            // 队列执行失败
            format_log_error($e, 'OrderAutoOper.autoComment');

            $job->delete();
        }
    }
}