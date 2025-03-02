<?php

namespace app\admin\model\shopro\user;

use app\admin\model\shopro\Common;

class Account extends Common
{
    protected $name = 'shopro_user_account';
    protected $type = [];
    // 追加属性
    protected $append = [
        'type_text'
    ];

    protected $typeMap = [
        'wechat' => '微信零钱',
        'alipay' => '支付宝账户',
        'bank' => '银行卡转账'
    ];

    public function getTypeTextAttr($value, $data)
    {
        return $this->typeMap[$data['type']];
    }
}
