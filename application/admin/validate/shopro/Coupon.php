<?php

namespace app\admin\validate\shopro;

use think\Validate;
use app\admin\validate\shopro\traits\CustomRule;

class Coupon extends Validate
{
    use CustomRule;

    protected $rule = [
        'name' => 'require',
        'type' => 'require',
        'use_scope' => 'require',
        'items' => 'requireIfAll:use_scope,goods,disabled_goods,category',       // 当 use_scope 在 goods,disabled_goods,category 时，items 必填
        'amount' => 'require',
        'enough' => 'require',
        'stock' => 'require',
        'get_time' => 'require',
        'use_time_type' => 'require',
        'use_time' => 'requireIf:use_time_type,range',        // 固定区间时必填
        'days' => 'requireIf:use_time_type,days',                                  // 相对天数时必填            
    ];

    protected $message  =   [
        'name.require'     => '请填写优惠券名称',
        'type.require'     => '请选择优惠券类型',
        'use_scope.require'     => '请选择可用范围',
        'items.requireIfAll'     => '请选择可用范围值',
        'amount.require'     => '请填写优惠券面额',
        'enough.require'     => '请填写优惠券消费门槛',
        'stock.require'     => '请填写优惠券发放数量',
        'get_time.require'     => '请选择优惠券发放时间',
        'use_time_type.require'     => '请选择优惠券使用时间类型',
        'use_time.requireIf'     => '请选择优惠券可使用时间',
        'days.requireIf'     => '请填写优惠券有效天数',
    ];


    protected $scene = [
        'add'  =>  ['name', 'type', 'use_scope', 'items', 'amount', 'enough', 'stock', 'get_time', 'use_time_type', 'use_time', 'days'],
    ];
}
