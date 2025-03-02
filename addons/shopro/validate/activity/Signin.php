<?php

namespace addons\shopro\validate\activity;

use think\Validate;

class Signin extends Validate
{
    protected $rule = [
        'date' => 'require',
    ];

    protected $message  =   [
        'date.require'     => '请选择补签日期',
    ];


    protected $scene = [
        'replenish'  =>  ['date'],
    ];
}
