<?php

namespace addons\shopro\service\order;

use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\order\Action;
use app\admin\model\shopro\Pay as PayModel;
use app\admin\model\shopro\user\User;
use app\common\model\User as CommonUser;
use addons\shopro\service\pay\PayRefund;
use addons\shopro\service\pay\PayOper;
use addons\shopro\traits\CouponSend;
use addons\shopro\service\StockSale;
use addons\shopro\facade\Activity as ActivityFacade;

class OrderRefund
{

    use CouponSend;

    protected $order = null;

    protected $default_refund_type = 'back';

    public function __construct($order)
    {
        $this->order = $order;

        $this->default_refund_type = $order->ext['refund_type'] ?? 'back';
    }

    /**
     * 全额退款(无条件退款， 优惠券积分全退)
     *
     * @param \think\Model $user
     * @param string $remark
     * @return void
     */
    public function fullRefund($user = null, $data = [])
    {
        $items = OrderItem::where('order_id', $this->order->id)->lock(true)->select();

        foreach ($items as $key => $item) {
            if (in_array($item['refund_status'], [
                OrderItem::REFUND_STATUS_AGREE,
                OrderItem::REFUND_STATUS_COMPLETED,
            ])) {
                error_stop('订单有退款，不能全额退款');
            }
        }

        // 返还库存，减少销量
        $stockSale = new StockSale();
        $stockSale->backStockSale($this->order, $items);

        if ($this->order->apply_refund_status == Order::APPLY_REFUND_STATUS_APPLY) {
            // 如果订单申请了全额退款，这里将全额退款状态改为 已完成
            $this->order->apply_refund_status = Order::APPLY_REFUND_STATUS_FINISH;
            $this->order->save();
        }

        if ($this->order->coupon_id) {
            // 订单使用了优惠券，退回用户优惠券
            $this->backUserCoupon($this->order->coupon_id);
        }

        if ($this->order->activity_id || $this->order->promo_types) {
            // 有活动,执行活动失败
            ActivityFacade::buyFail($this->order, 'refund');
        }

        $total_refund_fee = '0';            // 已退金额
        foreach ($items as $key => $item) {
            $is_last = (count($items) - 1) == $key ? true : false;        // 是否最后一个商品

            // 计算 refund_fee
            $refund_fee = $this->calcRefundFee($item, $total_refund_fee, $is_last);
            
            $item->refund_status = OrderItem::REFUND_STATUS_AGREE;    // 同意退款
            $item->refund_fee = $refund_fee;        // 实时计算，总 退款金额 等于 商品实际支付金额
            $item->ext = array_merge($item->ext, ['refund_time' => time()]);      // 退款时间
            $item->save();

            // 累加已退金额
            if ($refund_fee > 0) {
                $total_refund_fee = bcadd($total_refund_fee, $refund_fee, 2);
            }

            Action::add($this->order, $item, $user, ($user ? (($user instanceof User || $user instanceof CommonUser) ? 'user' : 'admin') : 'system'), (isset($data['remark']) && $data['remark'] ? $data['remark'] . ',' : '') . '退款金额：￥' . $item->refund_fee);

            // 订单商品退款后
            $eventData = ['order' => $this->order, 'item' => $item];
            \think\Hook::listen('order_item_refund_after', $eventData);
        }
        
        // 退回已支付的所有金额（积分，余额等）
        $this->refundAllPays($data);

        // 订单商品退款后
        $eventData = [
            'order' => $this->order, 
            'items' => $items, 
            'refund_type' => $data['refund_type'] ?? $this->default_refund_type,
            'refund_method' => 'full_refund'
        ];
        \think\Hook::listen('order_refund_after', $eventData);
    }



    /**
     * 部分退款 (通过订单商品退款)
     *
     * @param \think\Model $item
     * @param string $refund_money
     * @param \think\Model $user
     * @param string $remark
     * @return void
     */
    public function refund($item, $refund_money, $user = null, $data = [])
    {
        $item->refund_status = OrderItem::REFUND_STATUS_AGREE;    // 同意退款
        $item->refund_fee = $refund_money;
        $item->ext = array_merge($item->ext, ['refund_time' => time()]);      // 退款时间
        $item->save();

        Action::add($this->order, $item, $user, ($user ? (($user instanceof User || $user instanceof CommonUser) ? 'user' : 'admin') : 'system'), (isset($data['remark']) && $data['remark'] ? $data['remark'] . ',' : '') . '退款金额：￥' . $refund_money);

        // 订单商品退款后
        $eventData = ['order' => $this->order, 'item' => $item];
        \think\Hook::listen('order_item_refund_after', $eventData);

        // 查找符合条件的 pays 并从中退指定金额
        $this->refundPaysByMoney((string)$refund_money, $data);

        // 订单商品退款后
        $eventData = [
            'order' => $this->order, 
            'items' => [$item],
            'refund_type' => $data['refund_type'] ?? $this->default_refund_type,
            'refund_method' => 'item_refund'
        ];
        \think\Hook::listen('order_refund_after', $eventData);
    }


    /**
     * 退回已支付的所有 pays 记录
     *
     * @param string $remark
     * @return void
     */
    public function refundAllPays($data = [])
    {
        // 商城订单，已支付的 pay 记录
        $pays = PayModel::typeOrder()->paid()->where('order_id', $this->order->id)->lock(true)->select();

        $refund = new PayRefund($this->order->user_id);
        foreach ($pays as $key => $pay) {
            $refund->fullRefund($pay, [
                'refund_type' => $data['refund_type'] ?? $this->default_refund_type,
                'platform' => $this->order->platform,
                'remark' => $data['remark'] ?? ''
            ]);
        }
    }




    /**
     * 查找符合条件的 pays 并从中退指定金额 （不退积分，包括积分抵扣的积分）
     *
     * @param string $refund_money
     * @param string $remark
     * @return void
     */
    protected function refundPaysByMoney(string $refund_money, $data = [])
    {
        $payOper = new PayOper($this->order->user_id);
        $pays = $payOper->getCanRefundPays($this->order->id);
        $remain_max_refund_money = $payOper->getRemainRefundMoney($pays);

        if (bccomp($refund_money, $remain_max_refund_money, 2) === 1) {
            // 退款金额超出最大支付金额
            error_stop('退款金额超出最大可退款金额');
        }

        $current_refunded_money = '0';      // 本次退款，已退金额累计
        $refund = new PayRefund($this->order->user_id);
        foreach ($pays as $key => $pay) {
            $current_remain_money = bcsub($refund_money, $current_refunded_money, 2);       // 剩余应退款金额
            if ($current_remain_money <= 0) {
                // 退款完成
                break;
            }

            $current_pay_remain_money = bcsub($pay->pay_fee, $pay->refund_fee, 2);  // 当前 pay 记录剩余可退金额
            if ($current_pay_remain_money <= 0) {
                // 当前 pay 支付的金额已经退完了，循环下一个
                continue;
            }

            $current_refund_money = min($current_remain_money, $current_pay_remain_money);  // 取最小值

            $refund->refund($pay, $current_refund_money, [
                'refund_type' => $data['refund_type'] ?? $this->default_refund_type,
                'platform' => $this->order->platform,
                'remark' => $data['remark'] ?? ''
            ]);

            $current_refunded_money = bcadd($current_refunded_money, $current_refund_money, 2);
        }
        
        if ($refund_money > $current_refunded_money) {
            // 退款金额超出最大支付金额
            error_stop('退款金额超出最大可退款金额');
        }
    }


    /**
     * 计算 item 应退金额
     *
     * @param \think\Model $item
     * @param string $total_refund_fee
     * @param boolean $is_last
     * @return string
     */
    private function calcRefundFee($item, $total_refund_fee, $is_last = false) 
    {
        $pay_fee = $this->order->pay_fee;   // 支付总金额

        $current_goods_amount = bcmul($item->goods_price, (string)$item->goods_num, 2);
        $total_amount = bcadd($current_goods_amount, $item->dispatch_fee, 2);
        $refund_fee = bcsub($total_amount, $item->discount_fee, 2);        // (商品金额 + 运费金额) - 总优惠(活动，优惠券，包邮优惠)
        if ($total_refund_fee >= $pay_fee) {
            $refund_fee = 0;
        } else {
            $remain_fee = bcsub($pay_fee, $total_refund_fee, 2);
            $refund_fee = $remain_fee > $refund_fee ? ($is_last ? $remain_fee : $refund_fee) : $remain_fee;
        }

        return $refund_fee;
    }
}
