<?php

namespace app\admin\validate\shopro\goods;

use think\Validate;

class Service extends Validate
{
    protected $rule = [
        'name' => 'require',
    ];

    protected $message  =   [
        'name.require'     => '请填写服务保障名称',
    ];


    protected $scene = [
        'add'  =>  ['name']
    ];
}
