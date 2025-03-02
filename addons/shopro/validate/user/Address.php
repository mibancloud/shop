<?php

namespace addons\shopro\validate\user;

use think\Validate;

class Address extends Validate
{
    protected $rule = [
        'consignee' => 'require',
        'mobile' => 'require',
        'province_name' => 'require',
        'city_name' => 'require',
        'district_name' => 'require',
        'address' => 'require',
        // 'province_id' => 'require',
        // 'city_id' => 'require',
        // 'district_id' => 'require',
    ];

    protected $message  =   [
        'consignee.require'     => '请填写收货人信息',
        'mobile.require'     => '请填写手机号',
        'province_name.require'     => '请选择省份',
        'city_name.require'     => '请选择城市',
        'district_name.require'     => '请选择地区',
        'address.require'     => '请填写详细收货信息',
        // 'province_id.require'     => '请选择省份',
        // 'city_id.require'     => '请选择城市',
        // 'district_id.require'     => '请选择地区',
    ];


    protected $scene = [
        'add' => ['consignee', 'mobile', 'province_name', 'city_name', 'district_name', 'address'],

        'edit' => ['consignee', 'mobile', 'province_name', 'city_name', 'district_name', 'address']
    ];
}
