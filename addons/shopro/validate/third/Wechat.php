<?php

namespace addons\shopro\validate\third;

use think\Validate;

class Wechat extends Validate
{

    protected $regex = [
        'mobile' => '/^1[3456789]\d{9}$/',
    ];

    protected $rule = [
        'mobile' => 'require|regex:mobile|unique:user'
    ];

    protected $message  =   [
        'mobile.require'     => '手机号必须填写',
        'mobile.regex'     => '手机号格式不正确',
        'mobile.unique'     => '手机号已被占用',
    ];


    protected $scene = [
        'bindWechatMiniProgramMobile'  =>  ['mobile']
    ];
}
