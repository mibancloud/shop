<?php

namespace app\admin\validate\shopro\order;

use think\Validate;

class Order extends Validate
{
    protected $rule = [
        'order_id' => 'require',
        'order_item_ids' => 'require',
        'custom_type' => 'require',
        'custom_content' => 'require',

        'pay_fee' => 'require|float|gt:0',
        'change_msg' => 'require',

        // 编辑订单收货地址
        'consignee' => 'require',
        'mobile' => 'require',
        'province_name' => 'require',
        'city_name' => 'require',
        'district_name' => 'require',
        'address' => 'require',
        'province_id' => 'require',
        'city_id' => 'require',
        'district_id' => 'require',

        // 编辑卖家备注
        'memo' => 'require',

        // 订单退款
        'refund_money' => 'require|float|gt:0'
    ];

    protected $message  =   [
        'order_id' => '参数错误',
        'order_item_ids' => '参数错误',
        'custom_type' => '请选择发货内容类型',
        'custom_content' => '请填写发货内容',

        'pay_fee.require'     => '请输入正确的应支付金额',
        'pay_fee.float'     => '请输入正确的应支付金额',
        'pay_fee.gt'     => '请输入正确的应支付金额',
        'change_msg.require'     => '请输入改价备注',

        // 编辑订单收货地址
        'consignee.require'     => '请填写收货人信息',
        'mobile.require'     => '请填写手机号',
        'province_name.require'     => '请选择省份',
        'city_name.require'     => '请选择城市',
        'district_name.require'     => '请选择地区',
        'address.require'     => '请填写详细收货信息',
        'province_id.require'     => '请选择省份',
        'city_id.require'     => '请选择城市',
        'district_id.require'     => '请选择地区',

        // 编辑卖家备注
        'memo.require'     => '请输入卖家备注',

        // 订单退款
        'refund_money.require'     => '请输入正确的退款金额',
        'refund_money.float'     => '请输入正确的退款金额',
        'refund_money.gt'     => '请输入正确的退款金额',
    ];


    protected $scene = [
        'custom_dispatch'  =>  ['order_id', 'order_item_ids', 'custom_type', 'custom_content'],
        'change_fee'  =>  ['pay_fee', 'change_msg'],
        'edit_consignee'  => ['consignee', 'mobile', 'province_name', 'city_name', 'district_name', 'address', 'province_id', 'city_id', 'district_id'],
        'edit_memo'  => ['memo'],
        'refund'  => ['refund_money'],
    ];
}
