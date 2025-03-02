<?php

namespace app\admin\validate\shopro\order;

use think\Validate;

class Aftersale extends Validate
{
    protected $rule = [
        'refuse_msg' => 'require',
        'refund_money' => 'require|float|gt:0',
        'content' => 'require'
    ];

    protected $message  =   [
        'refuse_msg.require'     => '请输入拒绝原因',
        'refund_money.require'     => '请输入正确的退款金额',
        'refund_money.float'     => '请输入正确的退款金额',
        'refund_money.gt'     => '请输入正确的退款金额',
        'content.require'     => '请输入留言内容',
    ];


    protected $scene = [
        'refuse'  =>  ['refuse_msg'],
        'refund' => ['refund_money'],
        'add_log' => ['content'],
    ];
}
