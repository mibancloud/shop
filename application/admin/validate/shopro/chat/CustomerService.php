<?php

namespace app\admin\validate\shopro\chat;

use think\Validate;

class CustomerService extends Validate
{
    protected $rule = [
        'name' => 'require',
        'avatar' => 'require',
        'room_id' => 'require',
        'auth' => 'require',
        'auth_id' => 'require',
    ];

    protected $message  =   [
        'name.require'     => '客服名称必须填写',
        'avatar.require'     => '客服头像必须上传',
        'room_id.require'     => '客服房间号必须填写',
        'auth.require'     => '客服所属身份必须选择',
        'auth_id.require'     => '客服所属身份必须选择',
    ];


    protected $scene = [
        'add' => ['name', 'avatar', 'room_id', 'auth', 'auth_id']
    ];
}
