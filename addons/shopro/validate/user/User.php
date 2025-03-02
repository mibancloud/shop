<?php

namespace addons\shopro\validate\user;

use think\Validate;

class User extends Validate
{
    protected $regex = [
        'password' => '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]+\S{5,12}$/',
        'notPureNumber'  => '^[a-zA-Z][a-zA-Z0-9_]{4,15}$',
        'mobile' => '/^1[3456789]\d{9}$/',
    ];

    protected $rule = [
        'account' => 'require',
        'username' => 'require|alphaDash|length:5,12|unique:user|regex:notPureNumber',
        'nickname' => 'require|length:2,20',
        'mobile' => 'require|regex:mobile',
        'password' => 'require|length:6,16|regex:password',
        'oldPassword' => 'require',
        'newPassword' => 'require|length:6,16|regex:password',
        'avatar' => 'require',
        'email' => 'email|unique:user',
        'code' => 'require',
    ];

    protected $message  =   [
        'account.require' => '账号必须填写',
        'username.require'     => '用户名必须填写',
        'username.alphaDash'     => '用户名只能包含字母,数字,_和-',
        'username.length'     => '用户名长度必须在 5-12 位',
        'username.unique'     => '用户名已被占用',
        'username.regex'     => '用户名需以字母开头',

        'nickname.require'     => '昵称必须填写',
        'nickname.chsDash'     => '昵称只能包含汉字,字母,数字,_和-',
        'nickname.length'     => '昵称长度必须在 2-10 位',

        'mobile.require'     => '手机号必须填写',
        'mobile.regex'     => '手机号格式不正确',
        'mobile.unique'     => '手机号已被占用',

        'password.require'     => '请填写密码',
        'password.length'     => '密码长度必须在 6-16 位',
        'password.regex'     => '密码必须包含字母和数字',

        'oldPassword.require'     => '请填写旧密码',
        
        'newPassword.require'     => '请填写新密码',
        'newPassword.length'     => '密码长度必须在 6-16 位',
        'newPassword.regex'     => '密码必须包含字母和数字',

        'avatar.require'     => '头像必须上传',

        'email.email'     => '邮箱格式不正确',
        'email.unique'     => '邮箱已被占用',

        'code.require'     => '请填写验证码',
    ];


    protected $scene = [
        'accountLogin'  =>  ['account', 'password'],
        'smsLogin'  =>  ['mobile', 'code'],
        'smsRegister'  =>  ['mobile' => 'require|regex:mobile|unique:user', 'code', 'password'],
        'changePassword'  =>  ['oldPassword', 'newPassword'],
        'resetPassword'  =>  ['mobile', 'code', 'password'],
        'changeemail'  =>  ['email', 'code'],
        'changeMobile' => ['mobile' => 'require|regex:mobile|unique:user', 'code'],
        'changeUsername' => ['username'],
        'updateMpUserInfo' => ['avatar', 'nickname'],
    ];
}
