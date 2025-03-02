<?php

namespace app\admin\validate\shopro\goods;

use think\Validate;
use app\admin\validate\shopro\traits\CustomRule;

class Goods extends Validate
{
    use CustomRule;

    protected $rule = [
        'title' => 'require', 
        // 'subtitle' => 'require', 
        'category_ids' => 'require', 
        'image' => 'require', 
        'images' => 'require|array',
        'is_sku' => 'require',
        // 'cost_price' => 'require', 
        // 'original_price' => 'requireIf:is_sku,0', 
        'price' => 'requireIf:is_sku,0', 
        'dispatch_id' => 'requireIfAll:dispatch_type,express,autosend', 


    ];

    protected $message  =   [
        'title.require'     => '请填写商品标题',
        // 'subtitle.require'     => '请填写商品副标题',
        'category_ids.require'     => '请选择商品分类',
        'image.require'     => '请选择商品封面图',
        'images.require'     => '请选择商品轮播图',
        'images.array'     => '请选择商品轮播图',
        // 'cost_price.require'     => '请填写商品成本价',
        // 'original_price.require'     => '请填写商品原价',
        'price.requireIf'     => '请填写商品现价',
        'dispatch_id.requireIfAll'     => '请选择配送模板',
    ];


    protected $scene = [
        'add'  =>  ['title', 'image', 'images', 'price', 'dispatch_id'],
        'sku_params' => ['price']
    ];
}
