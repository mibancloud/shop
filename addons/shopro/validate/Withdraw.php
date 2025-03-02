<?php

namespace addons\shopro\validate;

use think\Validate;
use app\admin\validate\shopro\traits\CustomRule;

class Withdraw extends Validate
{
    use CustomRule;
    
    protected $rule = [
        'type' => 'require',
        'money' => 'require|float|gt:0',
        'account_name' => 'require',
        'account_no' => 'requireIfAll:type,alipay,bank',
        'account_header' => 'requireIf:type,bank',
    ];

    protected $message  =   [
        'type.require' => '请选择提现类型',
        'money.require' => '请输入提现金额',
        'money.float' => '请输入正确的提现金额',
        'money.gt' => '请输入正确的提现金额',
        'account_name.require'     => '请填写真实姓名',
        'account_no.requireIfAll'     => '请填写账号信息',
        'account_header.requireIf'     => '请填写开户行',
    ];



    protected $scene = [
        'apply' => ['type', 'money', 'account_name', 'account_no', 'account_header'],
    ];
}
