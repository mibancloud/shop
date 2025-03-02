<?php

namespace addons\shopro\service\order;

use app\admin\model\shopro\user\User;
use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\order\Action;
use app\admin\model\shopro\goods\Comment;
use app\admin\model\shopro\activity\GiftLog;
use app\admin\model\shopro\activity\Order as ActivityOrderModel;

class OrderOper
{

    public function __construct()
    {
        
        
    }


    /**
     * 订单自动关闭`
     *
     * @param \think\Model $order
     * @param \think\Model|null $user
     * @param string $type
     * @return void
     */
    public function close($order, $user = null, $type = 'user', $msg = '', $ext = [])
    {
        $order->status = Order::STATUS_CLOSED;
        $order->ext = array_merge($order->ext, $ext, ['closed_time' => time()]);      // 关闭时间
        $order->allowField(true)->save();

        Action::add($order, null, $user, $type, ($msg ? : $this->getOperText($type) . '关闭订单'));

        // 订单自动关闭之后 行为 返还用户优惠券，积分
        $data = ['order' => $order];
        \think\Hook::listen('order_close_after', $data);

        return $order;
    }



    /**
     * 订单取消
     *
     * @param object $order
     * @param object $user
     * @param string $type
     * @return object
     */
    public function cancel($order, $user = null, $type = 'user', $msg = '') 
    {
        $order->status = Order::STATUS_CANCEL;        // 取消订单
        $order->ext = array_merge($order->ext, ['cancel_time' => time()]);      // 取消时间
        $order->allowField(true)->save();

        Action::add($order, null, $user, $type, ($msg ?: $this->getOperText($type) . '取消订单'));

        // 订单取消，退回库存，退回优惠券积分，等
        $data = ['order' => $order];
        \think\Hook::listen('order_cancel_after', $data);

        return $order;
    }



    /**
     * 申请全额退款
     *
     * @param object $order
     * @param object $user
     * @param string $type
     * @return object
     */
    public function applyRefund($order, $user, $type = 'user')
    {
        $items = OrderItem::where('order_id', $order->id)->lock(true)->select();

        foreach ($items as $key => $item) {
            if (in_array($item['refund_status'], [
                OrderItem::REFUND_STATUS_AGREE,
                OrderItem::REFUND_STATUS_COMPLETED,
            ])) {
                error_stop('订单有退款，不可申请');
            }

            if ($item['dispatch_status'] != OrderItem::DISPATCH_STATUS_NOSEND) {
                error_stop('订单已发货，不可申请');
            }
        }

        $order->apply_refund_status = Order::APPLY_REFUND_STATUS_APPLY;        // 申请退款
        $order->ext = array_merge($order->ext, ['apply_refund_time' => time()]);      // 申请时间
        $order->save();

        Action::add($order, null, $user, $type, $this->getOperText($type) . '申请全额退款');

        // 订单申请全额退款
        $data = ['order' => $order, 'user' => $user];
        \think\Hook::listen('order_apply_refund_after', $data);

        return $order;
    }


    /**
     * 确认收货
     *
     * @param object $order
     * @param array $itemIds
     * @param object|null $user
     * @param string $type
     * @return object
     */
    public function confirm($order, $itemIds = [], $user = null, $type = 'user') 
    {
        $items = OrderItem::canConfirm()->where('order_id', $order->id)->where(function ($query) use ($itemIds) {
            if ($itemIds) {
                // 只确认收货传入的 ids
                $query->whereIn('id', $itemIds);
            }
        })->lock(true)->select();
        
        if (!$items) {
            error_stop('订单已确认收货，请不要重复确认收货');
        }

        foreach ($items as $item) {
            $item->ext = array_merge($item->ext, ['confirm_time' => time()]);
            $item->dispatch_status = OrderItem::DISPATCH_STATUS_GETED;        // 确认收货
            $item->save();

            Action::add($order, $item, $user, $type, $this->getOperText($type) . '确认收货');

            // 订单确认收货后
            $data = ['order' => $order, 'item' => $item];
            \think\Hook::listen('order_confirm_after', $data);
        }

        return $order;
    }



    /**
     * 拒收收货
     *
     * @param object $order
     * @param array $itemIds
     * @param object|null $user
     * @param string $type
     * @return object
     */
    public function refuse($order, $user = null, $type = 'user')
    {
        $items = OrderItem::canConfirm()->where('order_id', $order->id)->lock(true)->select();

        if (!$items) {
            error_stop('没有可拒收的商品');
        }

        foreach ($items as $item) {
            $item->ext = array_merge($item->ext, ['refuse_time' => time()]);
            $item->dispatch_status = OrderItem::DISPATCH_STATUS_REFUSE;        // 拒收
            $item->save();

            Action::add($order, $item, $user, $type, $this->getOperText($type) . '操作，用户拒绝收货');
        }

        // 订单拒收后事件
        $data = ['order' => $order];
        \think\Hook::listen('order_refuse_after', $data);

        return $order;
    }



    /**
     * 评价 (根据 comments 中的数据进行评价， 可以之评价一个（系统自动评价时候）)
     *
     * @param object $order
     * @param object|null $user
     * @param string $type
     * @return object
     */
    public function comment($order, $comments, $user = null, $type = 'user')
    {
        // 评价的orderItem id
        $comments = array_column($comments, null, 'item_id');
        $itemIds = array_keys($comments);

        $items = OrderItem::canComment()->where('order_id', $order->id)->lock(true)->select();

        if (!$items) {
            if ($type == 'system') {
                return $order;      // 系统自动评价时检测到用户已经自己评价，这里直接返回，不抛出异常
            }
            error_stop('订单已评价，请不要重复评价');
        }

        $orderConfig = sheep_config('shop.order');
        $orderUser = User::get($order->user_id);

        foreach ($items as $item) {
            if (!in_array($item['id'], $itemIds)) {
                // 不在本次评价列表
                continue;
            }
            $comment = $comments[$item['id']] ?? [];

            $status = 'normal';
            if (isset($orderConfig['comment_check']) && $orderConfig['comment_check'] && $type != 'system') {
                // 需要检查，并且不是系统自动评价
                $status = 'hidden';
            }

            Comment::create([
                'goods_id' => $item->goods_id,
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'user_id' => $order->user_id,
                'user_nickname' => $orderUser ? $orderUser->nickname : null,
                'user_avatar' => $orderUser ? $orderUser->avatar : null,
                'level' => $comment['level'] ?? 5,
                'content' => $comment['content'] ?? ($orderConfig['auto_comment_content'] ?? '用户默认好评'),
                'images' => $comment['images'] ?? [],
                'status' => $status
            ]);

            $item->ext = array_merge($item->ext, ['comment_time' => time()]);
            $item->comment_status = OrderItem::COMMENT_STATUS_OK;        // 评价
            $item->save();

            Action::add($order, $item, $user, $type, $this->getOperText($type) . '评价完成');

            // 订单评价后
            $data = ['order' => $order, 'item' => $item, 'user' => $user];
            \think\Hook::listen('order_comment_after', $data);
        }

        return $order;
    }


    /**
     * 删除订单
     *
     * @param object $order
     * @param object|null $user
     * @param string $type
     * @return void
     */
    public function delete($order, $user = null, $type = 'user')
    {
        $order->delete();        // 删除订单

        Action::add($order, null, $user, 'user', '用户删除订单');
    }



    /**
     * 存储活动信息
     *
     * @param \think\Model $order
     * @param array $result
     * @return void
     */
    public function addActivityOrder($order)
    {
        $ext = $order->ext;
        $items = $order->items;
        $goodsIds = array_column($items, 'goods_id');

        $model = new ActivityOrderModel();
        $model->order_id = $order->id;
        $model->user_id = $order->user_id;
        $model->activity_id = $order->activity_id;
        $model->activity_title = $ext['activity_title'] ?? null;
        $model->activity_type = $ext['activity_type'] ?? null;
        $model->pay_fee = $order->pay_fee;
        $model->goods_amount = $order->goods_amount;
        $model->discount_fee = $ext['activity_discount_amount'] ?? 0;       // 普通商品总额和活动时商品总额的差
        $model->goods_ids = join(',', $goodsIds);

        $model->save();
    }



    /**
     * 存储促销信息
     *
     * @param \think\Model $order
     * @param array $result
     * @return void
     */
    public function addPromosOrder($order)
    {
        $ext = $order->ext;
        $promoInfos = $ext['promo_infos'] ?? [];

        foreach ($promoInfos as $key => $info) {
            $model = new ActivityOrderModel();
            $model->order_id = $order->id;
            $model->user_id = $order->user_id;
            $model->activity_id = $info['activity_id'];
            $model->activity_title = $info['activity_title'];
            $model->activity_type = $info['activity_type'];
            $model->pay_fee = $order->pay_fee;
            $model->goods_amount = $info['promo_goods_amount'];

            $model->discount_fee = 0;
            if (in_array($info['activity_type'], ['full_reduce', 'full_discount', 'free_shipping'])) {
                $model->discount_fee = $info['promo_discount_money'];
            } else if ($info['activity_type'] == 'full_gift') {
                // 这里设置为 0，等支付成功之后补充
                $model->discount_fee = 0;
            }

            $model->goods_ids = join(',', $info['goods_ids']);
            $rules = [
                'rule_type' => $info['rule_type'],
                'discount_rule' => $info['discount_rule'],
            ];
            if ($info['activity_type'] == 'full_gift') {
                $rules['limit_num'] = $info['limit_num'] ?? 0;
                $rules['event'] = $info['event'] ?? 0;
            }
            $currentExt['rules'] = $rules;
            $model->ext = $currentExt;
            $model->save();
        }
    }


    /**
     * 将活动订单标记为已支付
     *
     * @param [type] $order
     * @return void
     */
    public function activityOrderPaid($order)
    {
        $activityOrders = ActivityOrderModel::where('order_id', $order->id)->select();
        
        foreach ($activityOrders as $activityOrder) {
            if ($activityOrder->activity_type == 'full_gift') {
                $value_money = GiftLog::where('activity_id', $activityOrder->activity_id)
                    ->where('order_id', $activityOrder->order_id)
                    ->where('user_id', $activityOrder->user_id)
                    ->whereIn('type', ['money', 'coupon'])        // 这里只算 赠送的余额，和优惠券（不算积分，和赠送商品的价值）
                    ->sum('value');

                $activityOrder->discount_fee = $value_money;      // 补充赠送的价值
            } else if (in_array($activityOrder->activity_type, ['groupon', 'groupon_ladder'])) {
                $ext = $order->ext;
                $currentExt['buy_type'] = $ext['buy_type'] ?? '';
                $currentExt['groupon_id'] = $ext['groupon_id'] ?? 0;        // 开团时候，支付之后才会有 groupon_id
                $activityOrder->ext = $currentExt;
            }

            $activityOrder->status = ActivityOrderModel::STATUS_PAID;
            $activityOrder->save();
        }
    }


    /**
     * 根据 oper_type 获取对应的用户
     */
    private function getOperText($oper_type)
    {
        switch($oper_type) {
            case 'user':
                $oper_text = '用户';
                break;
            case 'admin': 
                $oper_text = '管理员';
                break;
            case 'system':
                $oper_text = '系统自动';
                break;
            default :
                $oper_text = '系统自动';
                break;
        }

        return $oper_text;
    }
}
