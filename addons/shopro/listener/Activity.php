<?php

namespace addons\shopro\listener;

use addons\shopro\library\notify\Notify;
use app\admin\model\shopro\goods\Goods;
use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\activity\GrouponLog as ActivityGrouponLogModel;
use app\admin\model\shopro\user\User;
use addons\shopro\service\order\OrderOper;
use addons\shopro\service\order\OrderDispatch;

class Activity
{
    public function activityGrouponFinish($params)
    {
        $groupon = $params['groupon'];
        $goods = Goods::get($groupon['goods_id']);

        // 检测该团是否还有其他未支付的订单， 如果有，将订单改为交易关闭（后台虚拟成团情况，有人下单但是未支付）
        $this->invalidOrder($groupon, 'groupon_success');

        // 查询所有参与该团的真实用户 users & grouponLogs & grouponLeader
        extract($this->getActivityGrouponUsers($groupon));

        // 当拼团完成时，检测当前团所有订单是否需要自动发货
        $orderIds = array_column($grouponLogs, 'order_id');
        foreach ($orderIds as $order_id) {
            $orderDispatch = new OrderDispatch(['order_id' => $order_id]);
            $orderDispatch->grouponCheckDispatchAndSend();
        }

        if ($users) {
            Notify::send(
                $users,
                new \addons\shopro\notification\activity\GrouponFinish([
                    'groupon' => $groupon,
                    'groupon_logs' => $grouponLogs,
                    'groupon_leader' => $grouponLeader,
                    'goods' => $goods,
                ])
            );
        }
    }


    public function activityGrouponFail($params)
    {
        $groupon = $params['groupon'];
        $goods = Goods::get($groupon['goods_id']);

        // 检测该团是否还有其他未支付的订单， 如果有，将订单改为交易关闭（拼团到期自动解散，或者后台手动解散 之前，有人下单但是未支付）
        $this->invalidOrder($groupon, 'groupon_fail');

        // 查询所有参与该团的真实用户 users & grouponLogs & grouponLeader
        extract($this->getActivityGrouponUsers($groupon));

        if ($users) {
            Notify::send(
                $users,
                new \addons\shopro\notification\activity\GrouponFail([
                    'groupon' => $groupon,
                    'groupon_logs' => $grouponLogs,
                    'groupon_leader' => $grouponLeader,
                    'goods' => $goods,
                ])
            );
        }
    }


    // 查询所有参与该团的真实用户
    private function getActivityGrouponUsers($groupon)
    {
        $grouponLogs = ActivityGrouponLogModel::where('groupon_id', $groupon['id'])->where('is_fictitious', 0)->select();
        $user_ids = array_column($grouponLogs, 'user_id');

        // 所有用户
        $users = User::whereIn('id', $user_ids)->select();

        // 团长
        $grouponLeader = null;
        foreach ($users as $key => $user) {
            if ($user['id'] == $groupon['user_id']) {
                $grouponLeader = $user;
                break;
            }
        }

        return compact("users", "grouponLogs", "grouponLeader");
    }


    /**
     * 将该团的所有未支付订单关闭
     *
     * @return void
     */
    private function invalidOrder($groupon, $type)
    {
        // 获取订单
        $orders = Order::unpaid()->whereIn('activity_type', ['groupon', 'groupon_ladder'])->where('activity_id', $groupon->activity_id)->where(function ($query) use ($groupon) {
            $query->whereExists(function ($query) use ($groupon) {
                $order_table_name = (new Order())->getQuery()->getTable();
                $table_name = (new OrderItem())->getQuery()->getTable();
                $query->table($table_name)->where('order_id=' . $order_table_name . '.id')->where('goods_id', $groupon['goods_id'])->where('activity_id', $groupon['activity_id']);
            });
        })->select();

        foreach ($orders as $key => $order) {
            $orderExt = $order['ext'];
            if (isset($orderExt['buy_type']) && $orderExt['buy_type'] == 'groupon' && isset($orderExt['groupon_id']) && $orderExt['groupon_id'] == $groupon['id']) {
                // 拼团，并且是当前团,执行关闭
                // \think\facade\Db::transaction(function () use ($order, $type) {      // 不能加事务，会嵌套，触发事件时都有事务
                    $orderOper = new OrderOper();
                    $msg = $type == 'groupon_success' ? '已成团，未支付订单系统自动失效' : '团已解散，未支付订单系统自动失效';
                    $order = $orderOper->close($order, null, 'system', $msg);

                    return $order;
                // });
            }
        }
    }

}
