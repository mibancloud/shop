<?php

namespace app\admin\validate\shopro\chat;

use think\Validate;

class Question extends Validate
{
    protected $rule = [
        'room_id' => 'require',
        'title' => 'require',
        'content' => 'require',
    ];

    protected $message  =   [
        'room_id.require'     => '客服房间号必须填写',
        'title.require'     => '猜你想问标题必须填写',
        'content.require'     => '猜你想问内容必须填写',
    ];


    protected $scene = [
        'add' => ['room_id', 'title', 'content']
    ];
}
