<?php

namespace app\admin\validate\shopro\goods;

use think\Validate;

class Comment extends Validate
{
    protected $rule = [
        'goods_id' => 'require', 
        'user_id' => 'require', 
        'level' => 'require|number|between:1,5', 
        'content' => 'require', 
    ];

    protected $message  =   [
        'goods_id.require'     => '请选择商品',
        'user_id.require'     => '请选择用户',
        'level.require' => '请选择评价星级',
        'level.number' => '请选择评价星级',
        'level.between' => '请选择评价星级',
        'content.require'     => '请填写内容',
    ];


    protected $scene = [
        'add' => ['goods_id', 'user_id', 'level', 'content'],
        'reply'  =>  ['content'],
    ];
}
