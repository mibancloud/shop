<?php

namespace addons\shopro\listener;

use addons\shopro\library\notify\Notify;
use addons\shopro\library\Pipeline;
use addons\shopro\service\order\OrderThrough;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\Admin;
use app\admin\model\shopro\Cart;
use app\admin\model\shopro\user\Coupon;
use app\admin\model\shopro\order\Order as OrderModel;
use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\order\Invoice as OrderInvoiceModel;
use app\admin\model\shopro\order\Express as OrderExpressModel;
use app\admin\model\shopro\order\Action;
use app\admin\model\shopro\order\Invoice;
use app\admin\model\shopro\Config;
use addons\shopro\service\StockSale;
use addons\shopro\traits\CouponSend;
use addons\shopro\service\order\OrderRefund;
use addons\shopro\service\pay\PayOper;
use addons\shopro\service\order\OrderOper;
use addons\shopro\service\user\User as UserService;
use addons\shopro\service\order\OrderDispatch;
use addons\shopro\library\activity\traits\GiveGift;
use addons\shopro\facade\Activity as ActivityFacade;
use addons\shopro\facade\Wechat;
use addons\shopro\library\easywechatPlus\WechatMiniProgramShop;

class Order
{
    use CouponSend, GiveGift;

    public function orderCreateBefore($data)
    {
        $params = $data['params'];
        $orderData = $data['order_data'];

        $user = auth_user();

        $score_amount = $params['score_amount'];
        $money = $params['money'];
        $goodsList = $params['goods_list'];
        $activity_id = $params['activity_id'];
        $activity_type = $params['activity_type'];

        // 如果需要支付积分
        if ($score_amount) {
            // 判断个人积分是否充足
            if ($user->score < $score_amount) {
                // 积分不足
                error_stop('积分不足');
            }
        }

        if (floatval($money)) {
            // 判断个人余额是否充足
            if ($user->money < $money) {
                // 余额不足
                error_stop('余额不足');
            }
        }

        // 限购，库存锁定，活动处理
        $through = new OrderThrough();
        foreach ($goodsList as $key => $buyInfo) {
            $buyInfo = (new Pipeline)->send($buyInfo)->through($through->through(['limitBuy', 'checkStock', 'activity']))->then(function ($buyInfo) {
                return $buyInfo;
            });
        }
    }


    public function orderCreateAfter($data)
    {
        // 订单创建之后
        $user = auth_user();
        $order = $data['order'];
        $activity = $data['activity'];

        $order = OrderModel::where('id', $order->id)->find();        // 重新获取订单
        $from = request()->param('from');

        $items = $order->items;

        // 添加订单参与的活动订单
        $this->addActivityOrder($order);

        // 如果是积分商城商品,扣除积分
        $payOper = new PayOper();
        if ($order->type == 'score' && $order->score_amount) {
            // 这里会自动检测订单支付状态
            $order = $payOper->score($order, $order->score_amount);
        } else {
            // 检查订单支付状态, 主要针对 0 元订单
            $order = $payOper->checkAndPaid($order, 'order');
        }

        // 删除购物车
        if ($from == 'cart') {
            foreach ($items as $item) {
                Cart::where('user_id', $user->id)
                    ->where('goods_id', $item->goods_id)
                    ->where('goods_sku_price_id', $item->goods_sku_price_id)
                    ->delete();
            }
        }

        // 更新订单扩展字段
        $order_ext = $order['ext'];
        if ($activity && in_array($activity['type'], ['groupon', 'groupon_ladder'])) {
            // 如果是拼团， ext 中存储拼团相关信息（这里拼团肯定是成功了，否则下单前已被拦截）
            $order_ext['buy_type'] = request()->param('buy_type', 'groupon');     // 购买方式，alone： 单独购买， groupon: 拼团
            $order_ext['groupon_id'] = request()->param('groupon_id', 0);        // 如果是拼团，团 id
        }

        if ($order->status == OrderModel::STATUS_UNPAID) {
            // 添加订单自动关闭队列
            $this->autoCloseQueue($order, $activity, $order_ext);
        }

        // 更新订单扩展字段
        $order->ext = $order_ext;
        $order->save();

        return $order;
    }


    /**
     * 下单成功，添加参与的活动记录
     *
     * @param [type] $order
     * @return void
     */
    private function addActivityOrder($order)
    {
        // 添加订单参与的活动信息
        if ($order->activity_id) {
            $orderOper = new OrderOper();
            $orderOper->addActivityOrder($order);
        }

        // 添加订单参与的促销信息
        if ($order->promo_types) {
            $orderOper = new OrderOper();
            $orderOper->addPromosOrder($order);
        }
    }



    // 订单关闭后行为
    public function orderCloseAfter($params)
    {
        $order = $params['order'];

        $this->invalid($order, 'close');
    }


    // 订单取消后行为
    public function orderCancelAfter($params)
    {
        $order = $params['order'];

        $this->invalid($order, 'cancel');

        return $order;
    }


    // 订单支付后
    public function orderPaidAfter($params)
    {
        // 订单支付成功
        $order = $params['order'];
        $user = $params['user'];

        // 添加消费金额
        UserService::consume($user, $order['pay_fee']);

        // 检测有没有自动发货的商品，有就自动发货
        $orderDispatch = new OrderDispatch(['order_id' => $order->id]);
        $orderDispatch->checkDispatchAndSend();

        $this->newOrderNotify($order, $user);
    }



    /**
     * 选择线下付款后
     */
    public function orderOfflineAfter($params)
    {
        // 订单支付成功
        $order = $params['order'];
        $user = $params['user'];

        $this->newOrderNotify($order, $user);
    }


    // 线下付款后
    public function orderOfflinePaidAfter($params) 
    {
        // 订单线下付款后
        $order = $params['order'];
        $user = $params['user'];

        // 处理发票审核改为等待开具
        if ($order->invoice_status == 1) {
            $invoice = Invoice::where('order_id', $order->id)->find();
            if ($invoice) {
                $invoice->status = 'waiting';
                $invoice->save();
            }
        }

        // 满赠总送礼品
        $this->orderGiveGift($order, 'paid');

        // 将订单参与活动信息改为已支付
        $orderOper = new OrderOper();
        $orderOper->activityOrderPaid($order);

        // 添加消费金额
        UserService::consume($user, $order['pay_fee']);
    } 

    
    /**
     * 新订单通知
     */
    private function newOrderNotify($order, $user)
    {
        // 订单支付成功，通知管理员，有新订单需要处理
        $admins = collection(Admin::select())->filter(function ($admin) {
            return $admin->hasAccess($admin, [      // 订单列表或者订单详情权限
                'shopro/order/order/index',
                'shopro/order/order/detail'
            ]);
        });
        if (!$admins->isEmpty()) {
            Notify::send(
                $admins,
                new \addons\shopro\notification\order\OrderNew([
                    'order' => $order,
                    'user' => $user
                ])
            );
        }
    }


    /**
     * 订单申请退款之后
     *
     * @return void
     */
    public function orderApplyRefundAfter($params)
    {
        $order = $params['order'];
        $user = $params['user'];

        // 是否自动完成退款
        $auto_refund = Config::getConfigField('shop.order.auto_refund');
        if ($auto_refund) {
            // 自动退款
            $orderRefund = new OrderRefund($order);
            $orderRefund->fullRefund($user, [
                'refund_type' => 'back',
                'remark' => '用户申请退款自动全额退款'
            ]);
        }

        $admins = collection(Admin::select())->filter(function ($admin) {
            return $admin->hasAccess($admin, [      // 订单详情权限,订单拒绝全额退款，全额退款
                'shopro/order/order/detail',
                'shopro/order/order/applyrefundrefuse',
                'shopro/order/order/fullrefund'
            ]);
        });
        if (!$admins->isEmpty()) {
            Notify::send(
                $admins,
                new \addons\shopro\notification\order\OrderApplyRefund([
                    'auto_refund' => $auto_refund,
                    'order' => $order,
                    'user' => $user
                ])
            );
        }
    }

    // 发货后
    public function orderDispatchAfter($params)
    {
        $this->dispatchAfter($params, 'send');
    }

    // 修改发货信息
    public function orderDispatchChange($params)
    {
        // 获取包裹中的所有商品
        $orderExpress = $params['express'];
        $params['items'] = OrderItem::where('order_express_id', $orderExpress->id)->where('order_id', $orderExpress->order_id)->select();
        $this->dispatchAfter($params, 'change');
    }

    /**
     * 发货后私有方法
     *
     * @param array $params
     * @return void
     */
    private function dispatchAfter($params, $type)
    {
        $order = $params['order'];
        $items = $params['items'];
        $express = $params['express'];
        $dispatch_type = $params['dispatch_type'];

        // 更新发货时间
        $order->ext = array_merge($order->ext, ['send_time' => time()]);      // 发货时间
        $order->save();

        if (!$order->isOffline($order)) {        // 线下付款，未付款的不添加自动收货队列
            $uploadshoppingInfo = new WechatMiniProgramShop(Wechat::miniProgram());

            // 微信小程序，并且存在微信发货管理权限时，才推送发货消息
            if ($order['platform'] == 'WechatMiniProgram' && $uploadshoppingInfo->isTradeManaged()) {
                $hasNosend = OrderItem::where('order_id', $order['id'])->where('refund_status', OrderItem::REFUND_STATUS_NOREFUND)
                    ->where('dispatch_status', OrderItem::DISPATCH_STATUS_NOSEND)->count();
                if ($type == 'send') {
                    if (!$hasNosend && in_array('wechat', $order->pay_types)) {
                        // 所有 items 都已经发货，将交易信息上传微信
                        $uploadshoppingInfo->uploadShippingInfos($order);
                    }
                } else {
                    // 修改物流单
                    if (!$hasNosend && in_array('wechat', $order->pay_types)) {
                        // 所有 items 都已经发货，将交易信息上传微信
                        $uploadshoppingInfo->uploadShippingInfos($order, $express, 'change');
                    }
                }
            }

            // 添加自动确认收货队列，这个队列只自动确认 本次发货的 items
            $confirm_days = Config::getConfigField('shop.order.auto_confirm');
            $confirm_days = $confirm_days > 0 ? $confirm_days : 0;
            if ($confirm_days) {
                // 小于等于0， 不自动确认收货
                \think\Queue::later(($confirm_days * 86400), '\addons\shopro\job\OrderAutoOper@autoConfirm', $params, 'shopro');
            }
        }
        
        $user = User::where('id', $order['user_id'])->find();
        $user && $user->notify(
            new \addons\shopro\notification\order\OrderDispatched([
                'order' => $order,
                'items' => $items,
                'express' => $express,
            ])
        );
    }


    public function orderConfirmAfter($params)
    {
        $order = $params['order'];

        // 更新收货时间
        $order->ext = array_merge($order->ext, ['confirm_time' => time()]);      // 收货时间
        $order->save();

        // 添加自动好评队列
        $comment_days = Config::getConfigField('shop.order.auto_comment');
        $comment_days = $comment_days > 0 ? $comment_days : 0;
        if ($comment_days) {
            // 小于等于0， 不自动评价
            \think\Queue::later(($comment_days * 86400), '\addons\shopro\job\OrderAutoOper@autoComment', $params, 'shopro');
        }

        // 判断订单(未退款)是否全部确认收货(已经退款的不参与计算)
        $noConfirm = OrderItem::where('order_id', $order['id'])->where('refund_status', OrderItem::REFUND_STATUS_NOREFUND)
            ->where('dispatch_status', '<>', OrderItem::DISPATCH_STATUS_GETED)->count();

        // 订单已全部确认收货
        if ($noConfirm <= 0) {
            $data = ['order' => $order];

            \think\Hook::listen('order_confirm_finish', $data);
        }

        return $order;
    }


    public function orderConfirmFinish($params)
    {
        $order = $params['order'];

        // 满赠总送礼品
        $this->orderGiveGift($order, 'confirm');
    }


    public function orderRefuseAfter($params)
    {
        $order = $params['order'];
    }



    public function orderCommentAfter($params)
    {
        $order = $params['order'];
        $user = $params['user'];

        // 更新评价时间
        $order->ext = array_merge($order->ext, ['comment_time' => time()]);      // 评价时间
        $order->save();

        // 判断订单(未退款)是否全部评价完成(已经退款的不参与计算)
        $noComment = OrderItem::where('order_id', $order['id'])->where('refund_status', OrderItem::REFUND_STATUS_NOREFUND)
            ->where('comment_status', '<>', OrderItem::COMMENT_STATUS_OK)->count();

        // 订单已完成
        if ($noComment <= 0) {
            $order->status = OrderModel::STATUS_COMPLETED;
            $order->ext = array_merge($order->ext, ['completed_time' => time()]);      // 完成时间
            $order->save();

            Action::add($order, null, $user, 'user', '交易完成');

            $params = [
                'order' => $order
            ];
            \think\Hook::listen('order_finish', $params);
        }

        return $order;
    }


    public function orderFinish($params)
    {
        $order = $params['order'];

        // 满赠总送礼品
        $this->orderGiveGift($order, 'finish');
    }


    /**
     * 满赠，赠送礼品，私有方法
     *
     * @param object|array $order
     * @param string $event
     * @return void
     */
    private function orderGiveGift($order, $event)
    {
        if (strpos($order->promo_types, 'full_gift') !== false) {
            $user = User::get($order->user_id);

            $ext = $order->ext;
            $promoInfos = $ext['promo_infos'] ?? [];

            if ($order && $user) {
                // 检测并赠送礼品
                $this->checkAndGift($order, $user, $promoInfos, $event);
            }
        }
    }


    /**
     * 单个商品退款完成， 全额退款时订单商品也会挨个触发此事件
     */
    public function orderItemRefundAfter($params)
    {
        $order = $params['order'];
        $item = $params['item'];

        // 更新退款时间
        $order->ext = array_merge($order->ext, ['refund_time' => time()]);      // 退款时间
        $order->save();
    }


    /**
     * 本次退款操作事件，全额退款和单个退款都会触发此事件（发送消息通知）
     */
    public function orderRefundAfter($params)
    {
        $order = $params['order'];
        $items = $params['items'];
        $refund_type = $params['refund_type'];
        $refund_method = $params['refund_method'];
        $order = OrderModel::withTrashed()->where('id', $order->id)->find();      // 重新查询订单

        if ($refund_method == 'full_refund') {
            // 全额退款，将开票申请取消（只操作未开票的，已开票的不管）
            $this->cancelInvoice($order, $refund_method);
        }

        // 订单退款成功
        $user = User::where('id', $order->user_id)->find();
        $user && $user->notify(
            new \addons\shopro\notification\order\OrderRefund([
                'order' => $order,
                'items' => $items,
                'refund_type' => $refund_type,
                'refund_method' => $refund_method
            ])
        );
    }


    /**
     * 订单自动关闭
     *
     * @param \think\Model $order
     * @param array|object $activity
     * @param array $order_ext
     * @return void
     */
    private function autoCloseQueue($order, $activity, &$order_ext)
    {
        $rules = $activity['rules'] ?? null;
        // 获取活动规则，活动不存在，自动会使用全局自动关闭
        if (isset($rules['order_auto_close']) && $rules['order_auto_close'] > 0) {
            $close_minue = $rules['order_auto_close'];
        } else {
            $close_minue = Config::getConfigField('shop.order.auto_close');
            $close_minue = $close_minue > 0 ? $close_minue : 0;
        }

        if ($close_minue) {
            // 更新订单，将过期时间存入订单，前台展示支付倒计时
            $order_ext['expired_time'] = time() + ($close_minue * 60);
    
            \think\Queue::later(($close_minue * 60), '\addons\shopro\job\OrderAutoOper@autoClose', ['order' => $order], 'shopro');
        } else {
            $order_ext['expired_time'] = 0;
        }
    }


    // 订单取消或关闭返还
    private function invalid($order, $type)
    {
        // 如果有优惠券， 返还优惠券
        if ($order->coupon_id) {
            // 订单退回优惠券
            $this->backUserCoupon($order->coupon_id);
        }

        // 退回所有支付的记录，包括积分，余额
        $refund = new OrderRefund($order);
        $refund->refundAllPays([
            'remark' => '订单失效自动退回'
        ]);

        if ($order->activity_id || $order->promo_types) {
            // 有活动,执行活动失败
            ActivityFacade::buyFail($order, 'invalid');
        }

        $stockSale = new StockSale();
        if ($order->activity_id) {
            if ($order->pay_mode == 'offline') {
                // 线下付款取消,或者拒收（选择线下付款时，已经真是扣库存了，这里相当于全额退款，退回库存）
                $stockSale->backStockSale($order);
            }
        } else {
            if ($order->pay_mode == 'offline') {
                // 线下付款取消,或者拒收（选择线下付款时，已经真是扣库存了，这里相当于全额退款，退回库存）
                $stockSale->backStockSale($order);
            } else {
                // 没有活动, 释放锁定的库存
                $stockSale->stockUnLock($order);
            }
        }

        // 将开票信息取消
        $this->cancelInvoice($order, 'invalid');

        return $order;
    }


    /**
     * 未开票的申请，取消开票
     *
     * @param \think\Model $order
     * @param string $type
     * @return void
     */
    private function cancelInvoice($order, $type = 'invalid') 
    {
        if ($order->invoice_status == 1) {
            $invoice = OrderInvoiceModel::where('order_id', $order->id)->find();
            if ($invoice && in_array($invoice->status, ['unpaid', 'waiting'])) {
                // 未开票的申请，改为已取消    
                $invoice->status = 'cancel';
                $invoice->save();
            }
        }
    }
}
