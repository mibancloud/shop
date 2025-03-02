<?php

namespace addons\shopro\library\activity\traits;

use addons\shopro\facade\Redis;
use addons\shopro\facade\ActivityRedis;
use app\admin\model\shopro\activity\Activity;
use app\admin\model\shopro\activity\Groupon as ActivityGroupon;
use app\admin\model\shopro\activity\GrouponLog;
use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\data\FakeUser;
use addons\shopro\service\order\OrderRefund;
use addons\shopro\service\order\OrderOper;

/**
 * 拼团 （普通拼团，阶梯拼团，幸运拼团）
 */
trait Groupon
{
    /**
     * *、redis 没有存团完整信息，只存了团当前人数，团成员（当前人数，团成员均没有存虚拟用户）
     * *、redis userList 没有存这个人的购买状态
     * *、团 解散，成团，（因为直接修改了数据库，参团判断，先判断的数据库后判断的 redis）
     * *、虚拟成团时将虚拟人数存入 redis userList 中，因为团中有虚拟人时，redis 实际人数 和 团需要人数 都没有计算虚拟人，导致团可以超员
     */


    /**
     * 判断加入旧拼团
     */
    protected function checkAndGetJoinGroupon($buyInfo, $user, $groupon_id)
    {
        $goods = $buyInfo['goods'];
        $activity = $goods['activity'];

        // 获取团信息
        $activityGroupon = ActivityGroupon::where('id', $groupon_id)->find();
        if (!$activityGroupon) {
            error_stop('要参与的团不存在');
        }
        // 判断团所属活动是否正常
        if ($activityGroupon->activity_id != $activity['id']) {      // 修复，后台手动将活动删除，然后又立即给这个商品创建新的拼团活动，导致参与新活动的旧团错乱问题
            error_stop('要参与的活动已结束');
        }
        if ($activityGroupon['status'] != 'ing') {
            error_stop('要参与的团已成团，请选择其它团或自己开团');
        }

        if ($activityGroupon['current_num'] >= $activityGroupon['num']) {
            error_stop('该团已满，请参与其它团或自己开团');
        }

        if (!has_redis()) {
            // 没有 redis 直接判断数据库团信息，因为 current_num 支付成功才会累加，故无法保证超员，
            $isJoin = GrouponLog::where('user_id', $user['id'])->where('groupon_id', $activityGroupon->id)->where('is_fictitious', 0)->count();
            if ($isJoin) {
                error_stop('您已参与该团，请不要重复参团');
            }
            
            // 该团可加入
            return $activityGroupon;
        }

        $keys = ActivityRedis::keysActivity([
            'groupon_id' => $activityGroupon['id'],
            'goods_id' => $activityGroupon['goods_id'],
        ], [
            'activity_id' => $activity['id'],
            'activity_type' => $activity['type'],
        ]);

        extract($keys);

        $current_num = Redis::HGET($keyActivity, $keyGrouponNum);
        if ($current_num >= $activityGroupon['num']) {
            error_stop('该团已满，请参与其它团或自己开团');
        }

        // 将用户加入拼团缓存，用来判断同一个人在一个团，多次下单，订单失效时删除缓存
        $userList = Redis::HGET($keyActivity, $keyGrouponUserlist);
        $userList = json_decode($userList, true);
        $userIds = array_column($userList, 'user_id');
        if (in_array($user['id'], $userIds)) {
            error_stop('您已参与该团，请不要重复参团');
        }

        return $activityGroupon;
    }



    /**
     * 增加拼团预成员人数
     */
    protected function grouponCacheForwardNum($activityGroupon, $activity, $user, $payed = 'nopay')
    {
        if (!has_redis()) {
            return true;
        }

        $keys = ActivityRedis::keysActivity([
            'groupon_id' => $activityGroupon['id'],
            'goods_id' => $activityGroupon['goods_id'],
        ], [
            'activity_id' => $activity['id'],
            'activity_type' => $activity['type'],
        ]);

        extract($keys);

        // 当前团人数 grouponNumKey 如果不存在，自动创建
        $current_num = Redis::HINCRBY($keyActivity, $keyGrouponNum, 1);

        if ($current_num > $activityGroupon['num']) {
            // 再把刚加上的减回来
            $current_num = Redis::HINCRBY($keyActivity, $keyGrouponNum, -1);

            error_stop('该团已满，请参与其它团或自己开团');
        }

        // 将用户加入拼团缓存，用来判断同一个人在一个团，多次下单，取消失效订单时删除缓存
        $userList = Redis::HGET($keyActivity, $keyGrouponUserlist);
        $userList = json_decode($userList, true);
        $userList = $userList ?: [];
        $userList[] = [
            'user_id' => $user['id'],
            // 'status' => $payed       // 太复杂，先不做
        ];
        Redis::HSET($keyActivity, $keyGrouponUserlist, json_encode($userList));
    }



    // 拼团团成员预成员退回
    protected function grouponCacheBackNum($order, $type)
    {
        if (!has_redis()) {
            return true;
        }

        // 查询拼团商品
        $item = OrderItem::where('order_id', $order['id'])->find();        // 拼团订单只有一个商品

        // 扩展字段
        $order_ext = $order['ext'];
        // 团 id
        $groupon_id = $order_ext['groupon_id'] ?? 0;

        if (!$groupon_id) {
            return true;       // 商品独立购买，未参团,或者开新团
        }
        
        // 查询拼团,必须是拼团中才处理(已结束的(完成或者解散的没意义了)),redis 中没有存 团信息和状态
        $groupon = ActivityGroupon::ing()->lock(true)->find($groupon_id);
        if (!$groupon) {
            return true;
        }
        
        // if ($type == 'refund') {     // 退款这里不删除拼团记录，当成正常团成员处理
        //     // 退款,真实删除拼团记录,并减少参团人数
        //     $this->delGrouponLog($order, $groupon);
        // }

        $keys = ActivityRedis::keysActivity([
            'groupon_id' => $groupon_id,
            'goods_id' => $item['goods_id'],
            'goods_sku_price_id' => $item['goods_sku_price_id'],
        ], [
            'activity_id' => $item['activity_id'],
            'activity_type' => $item['activity_type'],
        ]);

        extract($keys);

        if (!Redis::EXISTS($keyActivity)) {
            // redis 不存在,可能活动已删除,不处理
            return true;
        }

        // 扣除预参团成员
        if (Redis::HEXISTS($keyActivity, $keyGrouponNum)) {
            $groupon_num = Redis::HINCRBY($keyActivity, $keyGrouponNum, -1);
        }

        $userList = Redis::HGET($keyActivity, $keyGrouponUserlist);
        $userList = json_decode($userList, true);
        $userList = $userList ?: [];
        foreach ($userList as $key => $user) {
            if ($user['user_id'] == $item['user_id']) {
                unset($userList[$key]);
            }
        }
        $userList = array_values($userList);
        Redis::HSET($keyActivity, $keyGrouponUserlist, json_encode($userList));
    }





    /**
     * 支付成功真实加入团
     */
    protected function joinGroupon($order, $user, \Closure $grouponCb = null)
    {
        $items = $order->items;
        $item = $items[0];      // 拼团只能单独购买

        // 扩展字段
        $order_ext = $order['ext'];
        // 团 id
        $groupon_id = $order_ext['groupon_id'] ?? 0;
        $buy_type = $order_ext['buy_type'] ?? 'groupon';

        // 单独购买，不加入团
        if ($buy_type == 'alone') {
            return true;
        }

        if ($groupon_id) {
            // 加入旧团，查询团
            $activityGroupon = ActivityGroupon::find($groupon_id);
        } else {
            // 加入新团，创建团
            $activityGroupon = $this->joinNewGroupon($order, $user, $item, $grouponCb);
        }
        // 添加参团记录
        $activityGrouponLog = $this->addGrouponLog($order, $user, $item, $activityGroupon);

        return $this->checkGrouponStatus($activityGroupon);
    }


    /**
     * 支付成功开启新拼团
     */
    protected function joinNewGroupon($order, $user, $item, \Closure $grouponCb = null)
    {
        // 获取活动
        $activity = Activity::where('id', $item['activity_id'])->find();
        $rules = $activity['rules'];

        // 小于 0 不限结束时间单位小时
        $expire_time = 0;
        if (isset($rules['valid_time']) && $rules['valid_time'] > 0) {
            // 转为 秒
            $expire_time = $rules['valid_time'] * 3600;
        }

        // 小于 0 不限结束时间单位小时
        $fictitious_time = 0;
        if (isset($rules['is_fictitious']) && $rules['is_fictitious'] && isset($rules['fictitious_time']) && $rules['fictitious_time'] > 0) {
            // 转为 秒
            $fictitious_time = $rules['fictitious_time'] * 3600;
        }

        if ($grouponCb) {
            // team_num 
            extract($grouponCb($rules, $item['ext']));
        }

        // 开团
        $activityGroupon = new ActivityGroupon();
        $activityGroupon->user_id = $user['id'];
        $activityGroupon->goods_id = $item['goods_id'];
        $activityGroupon->activity_id = $item['activity_id'];
        $activityGroupon->num = $team_num ?? 1;        // 避免活动找不到
        $activityGroupon->current_num = 0;              // 真实团成员等支付完成之后再增加
        $activityGroupon->status = 'ing';
        $activityGroupon->expire_time = $expire_time > 0 ? (time() + $expire_time) : 0;
        $activityGroupon->save();

        // 记录团 id
        $order->ext = array_merge($order->ext, ['groupon_id' => $activityGroupon->id]);
        $order->save();

        // 将团信息存入缓存，增加缓存中当前团人数
        $this->grouponCacheForwardNum($activityGroupon, $activity, $user, 'payed');

        if ($expire_time > 0) {
            // 增加自动关闭拼团队列(如果有虚拟成团，会判断虚拟成团)
            \think\Queue::later($expire_time, '\addons\shopro\job\GrouponAutoOper@expire', [
                'activity' => $activity,
                'activity_groupon_id' => $activityGroupon->id
            ], 'shopro');
        }

        if ($fictitious_time > 0) {
            // 自动虚拟成团时间（提前自动虚拟成团，让虚拟成团更加真实一点，避免在团结束那一刻突然成团了）应小于自动过期时间
            \think\Queue::later($fictitious_time, '\addons\shopro\job\GrouponAutoOper@fictitious', [
                'activity' => $activity,
                'activity_groupon_id' => $activityGroupon->id
            ], 'shopro');
        }

        return $activityGroupon;
    }


    /**
     * 增加团成员记录
     */
    protected function addGrouponLog($order, $user, $item, $activityGroupon)
    {
        if (!$activityGroupon) {
            \think\Log::error('groupon-notfund: order_id: ' . $order['id']);
            return null;
        }

        // 增加团成员数量
        $activityGroupon->setInc('current_num', 1);

        // 增加参团记录
        $activityGrouponLog = new GrouponLog();
        $activityGrouponLog->user_id = $user['id'];
        $activityGrouponLog->nickname = $user['nickname'];
        $activityGrouponLog->avatar = $user['avatar'];
        $activityGrouponLog->groupon_id = $activityGroupon['id'] ?? 0;
        $activityGrouponLog->goods_id = $item['goods_id'];
        $activityGrouponLog->goods_sku_price_id = $item['goods_sku_price_id'];
        $activityGrouponLog->activity_id = $item['activity_id'];
        $activityGrouponLog->is_leader = ($activityGroupon['user_id'] == $user['id']) ? 1 : 0;
        $activityGrouponLog->is_fictitious = 0;
        $activityGrouponLog->order_id = $order['id'];
        $activityGrouponLog->save();

        return $activityGrouponLog;
    }


    /**
     * 【此方法即将废除，加入团之后，不删除参团记录】，删除团成员记录(退款:已经真实加入团了,这里扣除)()
     */
    protected function delGrouponLog($order, $groupon)
    {
        $activityGrouponLog = GrouponLog::where('user_id', $order->user_id)
            ->where('groupon_id', $groupon->id)
            ->where('order_id', $order->id)
            ->find();
        
        if ($activityGrouponLog) {
            $activityGrouponLog->delete();

            // 扣除参团人数
            $groupon->setDec('current_num', 1);
        }
    }


    /**
     * 订单退款时标记拼团记录为已退款（主动退款和拼团失败退款）
     *
     * @param \think\Model $order
     * @return void
     */
    protected function refundGrouponLog($order) 
    {
        $order_ext = $order['ext'];
        $groupon_id = $order_ext['groupon_id'] ?? 0;
        if (!$groupon_id) {
            return true;       // 商品独立购买，未参团,或者开新团
        }

        $activityGrouponLog = GrouponLog::where('user_id', $order->user_id)
            ->where('groupon_id', $groupon_id)
            ->where('order_id', $order->id)
            ->find();

        if ($activityGrouponLog) {
            // 修改 logs 为已退款
            $activityGrouponLog->is_refund = 1;
            $activityGrouponLog->save();
        }
    }


    // 虚拟成团，增加虚拟成员，并判断是否完成，然后将团状态改为，虚拟成团成功
    protected function finishFictitiousGroupon($activity, $activityGroupon, $invalid = true, $num = 0, $users = [])
    {
        // 拼团剩余人数
        $surplus_num = $activityGroupon['num'] - $activityGroupon['current_num'];

        // 团已经满员
        if ($surplus_num <= 0) {
            if ($activityGroupon['status'] == 'ing') {
                // 已满员但还是进行中状态，检测并完成团，起到纠正作用
                return $this->checkGrouponStatus($activityGroupon);
            }
            return true;
        }

        // 本次虚拟人数, 如果传入 num 则使用 num 和 surplus_num 中最小值， 如果没有传入，默认剩余人数全部虚拟
        $fictitious_num = $num ? ($num > $surplus_num ? $surplus_num : $num) : $surplus_num;

        $fakeUsers = FakeUser::orderRaw('rand()')->limit($fictitious_num)->select();

        if (count($fakeUsers) < $fictitious_num && $num == 0) {
            if ($invalid) {
                // 虚拟用户不足，并且是自动虚拟成团进程，自动解散团
                return $this->invalidRefundGroupon($activityGroupon);
            }
            return false;
        }

        // 增加团人数
        $activityGroupon->setInc('current_num', $fictitious_num);

        if (has_redis()) {
            // redis 参数
            $keys = ActivityRedis::keysActivity([
                'groupon_id' => $activityGroupon['id'],
                'goods_id' => $activityGroupon['goods_id'],
            ], [
                'activity_id' => $activity['id'],
                'activity_type' => $activity['type'],
            ]);

            extract($keys);

            Redis::HINCRBY($keyActivity, $keyGrouponNum, $fictitious_num);      // 增加 redis 参团人数

            // 将用户加入拼团缓存，用来判断同一个人在一个团，多次下单，取消失效订单时删除缓存
            $userList = Redis::HGET($keyActivity, $keyGrouponUserlist);
            $userList = json_decode($userList, true);
            $userList = $userList ?: [];
            for ($i =0; $i < $fictitious_num; $i++) {
                $userList[] = [
                    'user_id' => 'fictitiou_' . time() . mt_rand(1000, 9999),
                ];
            }
            Redis::HSET($keyActivity, $keyGrouponUserlist, json_encode($userList));
        }

        for ($i = 0; $i < $fictitious_num; $i++) {
            // 先用传过来的
            $avatar = isset($users[$i]['avatar']) ? $users[$i]['avatar'] : '';
            $nickname = isset($users[$i]['nickname']) ? $users[$i]['nickname'] : '';

            // 如果没有，用查的虚拟的
            $avatar = $avatar ?: $fakeUsers[$i]['avatar'];
            $nickname = $nickname ?: $fakeUsers[$i]['nickname'];

            // 增加参团记录
            $activityGrouponLog = new GrouponLog();
            $activityGrouponLog->user_id = 0;
            $activityGrouponLog->nickname = $nickname;
            $activityGrouponLog->avatar = $avatar;
            $activityGrouponLog->groupon_id = $activityGroupon['id'] ?? 0;
            $activityGrouponLog->goods_id = $activityGroupon['goods_id'];
            $activityGrouponLog->goods_sku_price_id = 0;        // 没有订单，所以也就没有 goods_sku_price_id
            $activityGrouponLog->activity_id = $activityGroupon['activity_id'];
            $activityGrouponLog->is_leader = 0;     // 不是团长
            $activityGrouponLog->is_fictitious = 1; // 虚拟用户
            $activityGrouponLog->order_id = 0;      // 虚拟成员没有订单
            $activityGrouponLog->save();
        }

        return $this->checkGrouponStatus($activityGroupon);
    }


    /**
     * 团过期退款，或者后台手动解散退款
     */
    protected function invalidRefundGroupon($activityGroupon, $user = null)
    {
        $activityGroupon->status = 'invalid';       // 拼团失败
        $activityGroupon->save();

        // 查询参团真人
        $logs = GrouponLog::with(['order'])->where('groupon_id', $activityGroupon['id'])->where('is_fictitious', 0)->select();

        foreach ($logs as $key => $log) {
            $order = $log->order;
            if ($order && in_array($order->status, [Order::STATUS_PAID, Order::STATUS_COMPLETED])) {
                $refundNum = OrderItem::where('order_id', $order->id)->where('refund_status', '<>', OrderItem::REFUND_STATUS_NOREFUND)->count();
                if (!$refundNum) {
                    // 无条件全额退款
                    $refund = new OrderRefund($order);
                    $refund->fullRefund($user, [
                        'remark' => '拼团失败退款'
                    ]);
                }
            } else if ($order && $order->isOffline($order)) {
                $orderOper = new OrderOper();
                $orderOper->cancel($order, null, 'system', '拼团失败，系统自动取消订单');
            }
        }

        // 触发拼团失败行为
        $data = ['groupon' => $activityGroupon];
        \think\Hook::listen('activity_groupon_fail', $data);

        return true;
    }



    /**
     * 检查团状态
     */
    protected function checkGrouponStatus($activityGroupon)
    {
        if (!$activityGroupon) {
            return true;
        }

        // 重新获取团信息
        $activityGroupon = ActivityGroupon::where('id', $activityGroupon['id'])->find();
        if ($activityGroupon['current_num'] >= $activityGroupon['num'] && !in_array($activityGroupon['status'], ['finish', 'finish_fictitious'])) {
            // 查询是否有虚拟团成员
            $fictitiousCount = GrouponLog::where('groupon_id', $activityGroupon['id'])->where('is_fictitious', 1)->count();

            // 将团设置为已完成
            $activityGroupon->status = $fictitiousCount ? 'finish_fictitious' : 'finish';
            $activityGroupon->finish_time = time();
            $activityGroupon->save();

            // 触发成团行为
            $data = ['groupon' => $activityGroupon];
            \think\Hook::listen('activity_groupon_finish', $data);
        }

        return true;
    }
}
