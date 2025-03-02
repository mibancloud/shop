<?php

namespace app\admin\model\shopro;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\user\User;

class Withdraw extends Common
{
    protected $name = 'shopro_withdraw';
    protected $type = [
        'withdraw_info' => 'json'
    ];
    // 追加属性
    protected $append = [
        'status_text',
        'charge_rate_format',
        'withdraw_info_hidden',
        'withdraw_type_text'
    ];

    public function statusList()
    {
        return [
            -1 => '已拒绝',
            0 => '待审核',
            1 => '处理中',
            2 => '已处理'
        ];
    }


    public function withdrawTypeList()
    {
        return [
            'wechat' => '微信零钱',
            'alipay' => '支付包账户',
            'back' => '银行卡',
        ];
    }


    /**
     * 类型获取器
     */
    public function getWithdrawTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['withdraw_type'] ?? null);

        $list = $this->withdrawTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }



    public function getChargeRateFormatAttr($value, $data)
    {
        $value = $value ?: ($data['charge_rate'] ?? null);

        return bcmul((string)$value, '100', 1) . '%';
    }

    public function getWithdrawInfoHiddenAttr($value, $data) 
    {
        $withdraw_info = $value ?: ($this->withdraw_info ?? null);

        foreach ($withdraw_info as $key => &$info) {
            if (in_array($key, ['微信用户', '真实姓名'])) {
                $info = string_hide($info, 2);
            } elseif (in_array($key, ['银行卡号', '支付宝账户', '微信ID'])) {
                $info = account_hide($info);
            }
        }

        return $withdraw_info;
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->field('id, nickname, avatar, total_consume');
    }
}
