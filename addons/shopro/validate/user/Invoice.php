<?php

namespace addons\shopro\validate\user;

use think\Validate;

class Invoice extends Validate
{
    protected $rule = [
        'type' => 'require',
        'name' => 'require',
    ];

    protected $message  =   [
        'type.require'     => '请选择发票类型',
        'name.require'     => '请填写发票名称',
    ];


    protected $scene = [
        'add' => ['type', 'name'],
        'edit' => ['name']
    ];
}
