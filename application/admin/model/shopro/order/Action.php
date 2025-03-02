<?php

namespace app\admin\model\shopro\order;

use app\admin\model\shopro\Common;
use app\admin\model\Admin;
use app\admin\model\shopro\user\User;

class Action extends Common
{
    protected $name = 'shopro_order_action';

    protected $type = [
    ];

    // 追加属性
    protected $append = [
        'oper'
    ];


    public static function add($order, $item = null, $oper = null, $type = 'user', $remark = '')
    {
        $oper_id = $oper ? $oper['id'] : 0;
        $self = new self();
        $self->order_id = $order->id;
        $self->order_item_id = is_null($item) ? 0 : $item->id;
        $self->oper_type = $type;
        $self->oper_id = $oper_id;
        $self->order_status = $order->status;
        $self->dispatch_status = is_null($item) ? 0 : $item->dispatch_status;
        $self->comment_status = is_null($item) ? 0 : $item->comment_status;
        $self->aftersale_status = is_null($item) ? 0 : $item->aftersale_status;
        $self->refund_status = is_null($item) ? 0 : $item->refund_status;
        $self->remark = $remark;
        $self->save();

        return $self;
    }
}
