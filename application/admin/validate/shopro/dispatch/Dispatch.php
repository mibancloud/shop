<?php

namespace app\admin\validate\shopro\dispatch;

use think\Validate;

class Dispatch extends Validate
{
    protected $rule = [
        'name' => 'require',
        'express' => 'array',
        'autosend' => 'array',

        // express 表数据
        'type' => 'require',
        'first_num' => 'require',
        'first_price' => 'require',
        'additional_num' => 'require',
        'additional_price' => 'require',
    ];

    protected $message  =   [
        'name.require'     => '请填写自定义分类名称',
        'express.array'     => '请填写正确的模板规则',
        'autosend.array'     => '请填写正确的自动发货规则',

        'type.require' => '请选择计价方式',
        'first_num.require' => '请填写初始计价数量',
        'first_price.require' => '请填写初始配送价格',
        'additional_num.require' => '请填写追加计价数量',
        'additional_price.require' => '请填写追加计价价格',
    ];


    protected $scene = [
        'add'  =>  ['name', 'express'],
        'express' => ['type', 'first_num', 'first_price', 'additional_num', 'additional_price'],

        'autosend' => ['type', 'content']
    ];
}
