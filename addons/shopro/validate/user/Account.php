<?php

namespace addons\shopro\validate\user;

use think\Validate;

class Account extends Validate
{
    protected $rule = [
        'account_name' => 'require',
        'account_header' => 'require',
        'account_no' => 'require'
    ];

    protected $message  =   [
        'account_name.require'     => '请填写姓名',
        'account_header.require'     => '请填写开户行',
        'account_no.require'     => '请填写账号信息',

    ];


    protected $scene = [
        'wechat' => ['account_name', 'account_name', 'account_no'],
        'alipay' => ['account_name', 'account_name', 'account_no'],
        'bank' => ['account_name', 'account_name', 'account_no']
    ];
}
