<?php

namespace app\admin\model\shopro\order;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\user\Invoice as UserInvoiceModel;

class Invoice extends Common
{
    protected $name = 'shopro_order_invoice';

    protected $type = [
        'download_urls' => 'array',
        'finish_time' => 'timestamp'
    ];

    protected $hidden = [
        'order_items'
    ];

    // 追加属性
    protected $append = [
        'type_text',
        'status_text'
    ];


    public function typeList() 
    {
        return (new UserInvoiceModel)->typeList();
    }

    /**
     * 状态
     *
     * @return array
     */
    public function statusList()
    {
        return [
            'cancel' => '已取消',
            'unpaid' => '未支付',
            'waiting' => '等待开票',
            'finish' => '已开具',
        ];
    }


    public function getOrderStatusAttr($value, $data)
    {
        $order = $this->order;
        $order_status = 'normal';

        if (!$order) {
            $order_status = 'order_deleted';
        } else if (in_array($order->status, [Order::STATUS_PAID, Order::STATUS_COMPLETED])) {
            $items = $this->order_items;
            $refund_num = 0;
            $aftersale_num = 0;
            foreach ($items as $item) {
                if (in_array($item->refund_status, [
                    OrderItem::REFUND_STATUS_AGREE,
                    OrderItem::REFUND_STATUS_COMPLETED
                ])) {
                    $refund_num += 1;
                }

                if (in_array($item->aftersale_status, [
                    OrderItem::AFTERSALE_STATUS_REFUSE, 
                    OrderItem::AFTERSALE_STATUS_ING, 
                    OrderItem::AFTERSALE_STATUS_COMPLETED
                ])) {
                    $aftersale_num += 1;
                } 
            }

            if ($refund_num) {
                $order_status = 'refund';
                if ($refund_num == count($items)) {
                    $order_status = 'refund_all';
                }
            } else if ($aftersale_num) {
                $order_status = 'aftersale';
            }
        } else if ($order->isOffline($order)) {
            $order_status = 'offline_unpaid';
        }

        return $order_status;
    }


    public function getOrderStatusTextAttr($value, $data)
    {
        $order_status = $this->order_status;

        switch($order_status) {
            case 'order_deleted':
                $order_status_text = '该订单已被删除';
                break;
            case 'refund_all':
                $order_status_text = '该订单已全部退款';
                break;
            case 'refund':
                $order_status_text = '该订单已部分退款';
                break;
            case 'aftersale':
                $order_status_text = '该订单已申请售后';
                break;
            case 'offline_unpaid':
                $order_status_text = '该订单货到付款-未付款';
                break;
            default :
                $order_status_text = '';
        }

        return $order_status_text;
    }



    public function getOrderFeeAttr($value, $data)
    {
        $order = $this->order;

        if ($order && $order->pay_fee != $order->original_pay_fee) {
            return [
                'pay_fee' => $order->pay_fee,
                'original_pay_fee' => $order->original_pay_fee,
            ];
        }
        return null;
    }

    
    public function scopeCancel($query)
    {
        return $query->where('status', 'cancel');
    }


    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }


    public function scopeFinish($query)
    {
        return $query->where('status', 'finish');
    }


    /**
     * 不展示 未支付的
     */
    public function scopeShow($query)
    {
        return $query->whereIn('status', ['cancel', 'waiting', 'finish']);
    }



    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    


    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }


    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }


}
