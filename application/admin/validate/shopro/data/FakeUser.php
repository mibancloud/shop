<?php

namespace app\admin\validate\shopro\data;

use think\Validate;

class FakeUser extends Validate
{
    protected $rule = [
        'username' => 'require|alphaDash|length:5,20',
        'nickname' => 'require|chsDash|length:2,15',
        'mobile' => 'regex:/^1[3-9]\d{9}$/',
        'avatar' => 'require',
        'email' => 'email',
        'password' => 'length:6,16',
    ];

    protected $message  =   [
        'username.require'     => '用户名必须填写',
        'username.alphaDash'     => '用户名只能是字母和数字，下划线_及破折号-',
        'username.length'     => '用户名长度必须在 5-20 位',
        'nickname.require'     => '昵称必须填写',
        'nickname.chsDash'     => '昵称名称只能是汉字、字母、数字和下划线_及破折号-',
        'nickname.length'     => '昵称长度必须在 2-15 位',
        'mobile.regex'     => '手机号格式不正确',
        'avatar.require'     => '头像必须上传',
        'email.email'     => '邮箱格式不正确',
        'password.length'     => '密码长度必须在 6-16 位',
    ];


    protected $scene = [
        'add'  =>  ['username', 'nickname', 'mobile', 'password', 'avatar', 'email'],
        'edit'  =>  ['username', 'nickname', 'mobile', 'password', 'avatar', 'email'],
    ];
}
