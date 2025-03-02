<?php

namespace app\admin\validate\shopro\chat;

use think\Validate;

class ScoreShop extends Validate
{
    protected $rule = [
        'goods_id' => 'require', 
        'sku_prices' => 'require|array', 


    ];

    protected $message  =   [
        'goods_id.require'     => '请选择要添加的商品',
        'sku_prices.require'     => '请填写积分商品规格',
        'sku_prices.array'     => '请填写积分商品规格',
    ];


    protected $scene = [
        'add'  =>  ['goods_id', 'sku_prices'],
        'edit' => ['sku_prices']
    ];
}
