<?php

namespace addons\shopro\validate\order;

use think\Validate;

class Aftersale extends Validate
{
    protected $rule = [
        'type' => 'require',
        'order_id' => 'require',
        'order_item_id' => 'require',
        'mobile' => 'require',
        'reason' => 'require',
    ];

    protected $message  =   [
        'type.require'     => '请选择售后类型',
        'order_id.require'     => '参数错误',
        'order_item_id.require'     => '参数错误',
        'mobile.require'     => '请填写手机号',
        'reason.require'     => '请选择售后原因',
    ];


    protected $scene = [
        'add'  =>  ['type', 'order_id', 'order_item_id', 'mobile', 'reason'],
    ];
}
