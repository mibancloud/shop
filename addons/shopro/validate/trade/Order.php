<?php

namespace addons\shopro\validate\trade;

use think\Validate;

class Order extends Validate
{
    protected $rule = [
        'recharge_money' => 'require',
    ];

    protected $message  =   [
        'recharge_money.require' => '请输入充值金额',
    ];


    protected $scene = [
        'recharge'  =>  ['recharge_money'],
    ];
}
