<?php

namespace app\admin\validate\shopro\chat;

use think\Validate;

class CommonWord extends Validate
{
    protected $rule = [
        'room_id' => 'require',
        'name' => 'require',
        'content' => 'require',
    ];

    protected $message  =   [
        'room_id.require'     => '客服房间号必须填写',
        'name.require'     => '常用语名称必须填写',
        'content.require'     => '常用于内容必须填写',
    ];


    protected $scene = [
        'add' => ['room_id', 'name', 'content']
    ];
}
