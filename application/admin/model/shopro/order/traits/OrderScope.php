<?php

namespace app\admin\model\shopro\order\traits;

use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\order\OrderItem;

trait OrderScope
{
    // 已关闭
    public function scopeClosed($query)
    {
        return $query->where('status', Order::STATUS_CLOSED);
    }

    // 已取消
    public function scopeCancel($query)
    {
        return $query->where('status', Order::STATUS_CANCEL);
    }

    // 未支付
    public function scopeUnpaid($query)
    {
        return $query->where('status', Order::STATUS_UNPAID);
    }
    

    // 可以取消，未支付&货到付款未发货的订单
    public function scopeCanCancel($query) 
    {
        return $query->where(function ($query) {
            $query->where('status', Order::STATUS_UNPAID)->whereOr(function ($query) {
                $self_name = (new Order())->getQuery()->getTable();
                $item_name = (new OrderItem())->getQuery()->getTable();

                $query->where('pay_mode', 'offline')->where('status', Order::STATUS_PENDING)->whereExists(function ($query) use ($self_name, $item_name) {
                    // 货到付款订单，未发货未退款，可以申请售后
                    $query->table($item_name)->where('order_id=' . $self_name . '.id')
                        ->where('dispatch_status', OrderItem::DISPATCH_STATUS_NOSEND)       // 未发货
                        ->where('refund_status', OrderItem::REFUND_STATUS_NOREFUND);      // 没有退款完成;
                });
            });
        });
    }
    
    // 已支付
    public function scopePaid($query)
    {
        return $query->whereIn('status', [Order::STATUS_PAID, Order::STATUS_COMPLETED]);
    }


    // 线下支付(货到付款) pending 时
    public function scopeOffline($query)
    {
        return $query->where('pay_mode', 'offline')->where('status', Order::STATUS_PENDING);
    }


    /**
     * 是否线下支付(货到付款)，暂未付款
     *
     * @param object|array $order
     * @return boolean
     */
    public function isOffline($order)
    {
        return ($order['status'] == Order::STATUS_PENDING && $order['pay_mode'] == 'offline') ? true : false;
    }


    // 已支付的，或者是线下付款的未支付订单，后续可以，发货，收货，评价
    public function scopePretendPaid($query)
    {
        return $query->where(function ($query) {
            $query->whereIn('status', [Order::STATUS_PAID, Order::STATUS_COMPLETED])->whereOr(function($query) {
                $query->where('pay_mode', 'offline')->where('status', Order::STATUS_PENDING);
            });
        });
    }

    // 已完成
    public function scopeCompleted($query)
    {
        return $query->where('status', Order::STATUS_COMPLETED);
    }

    // 未申请全额退款,或者已拒绝
    public function scopeNoApplyRefund($query)
    {
        return $query->whereIn('apply_refund_status', [ 
            Order::APPLY_REFUND_STATUS_NOAPPLY, 
            Order::APPLY_REFUND_STATUS_REFUSE
        ]);
    }

    // 申请全额退款中
    public function scopeApplyRefundIng($query)
    {
        return $query->where('apply_refund_status',  Order::APPLY_REFUND_STATUS_APPLY);
    }


    // 未发货
    public function scopeNosend($query)
    {
        $self_name = (new Order())->getQuery()->getTable();
        $item_name = (new OrderItem())->getQuery()->getTable();

        $is_express = (request()->action() == 'exportdelivery') ? 1 : 0;       // 是否是 express，将只查快递物流的代发货

        return $query->noApplyRefund()->whereExists(function ($query) use ($self_name, $item_name, $is_express) {
            $query->table($item_name)->where('order_id=' . $self_name . '.id')
                ->where('dispatch_status', OrderItem::DISPATCH_STATUS_NOSEND)       // 未发货
                ->where('refund_status', OrderItem::REFUND_STATUS_NOREFUND);      // 没有退款完成
            if ($is_express) {  // 只查快递物流的代发货
                $query->where('dispatch_type', 'express');
            }
        })->whereNotExists(function ($query) use ($self_name, $item_name, $is_express) {
            // 不是 正在售后的商品
            $query->table($item_name)->where('order_id=' . $self_name . '.id')
                ->where('aftersale_status', OrderItem::AFTERSALE_STATUS_ING)   // 但是售后中
                ->where('dispatch_status', OrderItem::DISPATCH_STATUS_NOSEND)       // 未发货
                ->where('refund_status', OrderItem::REFUND_STATUS_NOREFUND);      // 没有退款完成;
            
            if ($is_express) {  // 只查快递物流的代发货
                $query->where('dispatch_type', 'express');
            }
        });
    }

    // 待收货
    public function scopeNoget($query)
    {
        return $query->whereExists(function ($query) {
            $self_name = (new Order())->getQuery()->getTable();
            $item_name = (new OrderItem())->getQuery()->getTable();

            $query->table($item_name)->where('order_id=' . $self_name . '.id')
                ->where('dispatch_status', OrderItem::DISPATCH_STATUS_SENDED)       // 已发货
                ->where('refund_status', OrderItem::REFUND_STATUS_NOREFUND);      // 没有退款完成
        });
    }


    // 已拒收
    public function scopeRefuse($query)
    {
        return $query->whereExists(function ($query) {
            $self_name = (new Order())->getQuery()->getTable();
            $item_name = (new OrderItem())->getQuery()->getTable();

            $query->table($item_name)->where('order_id=' . $self_name . '.id')
                ->where('dispatch_status', OrderItem::DISPATCH_STATUS_REFUSE)       // 已拒收
                ->where('refund_status', OrderItem::REFUND_STATUS_NOREFUND);      // 没有退款完成
        });
    }


    // 待评价
    public function scopeNocomment($query)
    {
        return $query->whereExists(function ($query) {
            $self_name = (new Order())->getQuery()->getTable();
            $item_name = (new OrderItem())->getQuery()->getTable();

            $query->table($item_name)->where('order_id=' . $self_name . '.id')
                ->where('dispatch_status', OrderItem::DISPATCH_STATUS_GETED)       // 已收货
                ->where('refund_status', OrderItem::REFUND_STATUS_NOREFUND)      // 没有退款完成
                ->where('comment_status', OrderItem::COMMENT_STATUS_NO);        // 未评价
        });
    }

    // 售后 (后台要用，虽然有专门的售后单列表)
    public function scopeAftersale($query)
    {
        return $query->whereExists(function ($query) {
            $self_name = (new Order())->getQuery()->getTable();
            $item_name = (new OrderItem())->getQuery()->getTable();
            $query->table($item_name)->where('order_id=' . $self_name . '.id')
                ->where('aftersale_status', '<>', OrderItem::AFTERSALE_STATUS_NOAFTER);
        });
    }

    // 退款
    public function scopeRefund($query)
    {
        return $query->whereExists(function ($query) {
            $self_name = (new Order())->getQuery()->getTable();
            $item_name = (new OrderItem())->getQuery()->getTable();
            $query->table($item_name)->where('order_id=' . $self_name . '.id')
                ->where('refund_status', '<>', OrderItem::REFUND_STATUS_NOREFUND);
        });
    }


    public function scopeCanAftersale($query)
    {
        return $query->where('status', 'in', [Order::STATUS_PAID, Order::STATUS_COMPLETED]);
    }

    public function scopeCanDelete($query)
    {
        return $query->where('status', 'in', [
            Order::STATUS_CANCEL,
            Order::STATUS_CLOSED,
            Order::STATUS_COMPLETED
        ]);
    }
}
