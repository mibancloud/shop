<?php

namespace addons\shopro\validate\order;

use think\Validate;

class Order extends Validate
{
    protected $rule = [
        'goods_list' => 'require|array',
        
        // 评价
        'comments' => 'require|array',
        'item_id' => 'require',
        'level' => 'require|number|between:1,5',
        
    ];

    protected $message  =   [
        'goods_list.require'     => '请选择要购买的商品',
        
        // 评价
        'comments.require'     => '请选择要评价的商品',
        'comments.array'     => '请选择要评价的商品',
        'item_id.require' => '缺少订单商品参数',
        'level.require' => '描述相符必须选择',
        'level.number' => '描述相符必须选择',
        'level.between' => '描述相符必须选择',
    ];


    protected $scene = [
        'calc'  =>  ['goods_list'],
        'create'  =>  ['goods_list'],
        'comment'  =>  ['comments'],
        'comment_item'  =>  ['item_id', 'level'],
    ];
}
