<?php

namespace app\admin\validate\shopro\goods;

use think\Validate;

class StockWarning extends Validate
{
    protected $rule = [
        'stock' => 'require|integer|gt:0',
    ];

    protected $message  =   [
        'stock.require'     => '请填写补货数量',
        'stock.integer'     => '请填写补货数量',
        'stock.gt'     => '请填写正确的补货数量'
    ];


    protected $scene = [
        'add_stock'  =>  ['stock']
    ];
}
