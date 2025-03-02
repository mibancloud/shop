<?php

namespace app\admin\model\shopro\user;

use app\admin\model\shopro\Common;

class WalletLog extends Common
{
    protected $name = 'shopro_user_wallet_log';

    protected $updateTime = false;

    protected $type = [
        'ext' => 'json'
    ];
    // 追加属性
    protected $append = [
        'event_text'
    ];

    const TYPE_MAP = [
        'money' => '余额',
        'score' => '积分',
        'commission' => '佣金'
    ];

    protected $eventMap = [
        'score' => [
            'signin' => '签到-赠送积分',
            'replenish_signin' => '签到-补签',
            'activity_gift' => '活动-赠送积分',
            'score_shop_pay' => '积分商城-积分支付',
            'order_pay' => '商城订单-积分抵扣',
            'order_refund' => '订单退款-退还积分',
            'admin_recharge' => '后台-积分充值',
            'recharge_gift' => '线上充值-赠送积分'
        ],
        'money' => [
            'order_pay' => '商城订单-余额支付',
            'order_recharge' => '线上充值',
            'admin_recharge' => '后台-余额充值',
            'recharge_gift' => '线上充值-赠送余额',
            'activity_gift' => '活动-赠送余额',
            'order_refund' => '订单退款-退还余额',
            'transfer_by_commission' => '佣金-转入到余额'
        ],
        'commission' => [
            'withdraw' => '提现',
            'withdraw_error' => '提现失败-返还佣金',
            'reward_income' => '佣金-收益',
            'reward_back' => '佣金-退还',
            'transfer_to_money' => '佣金-转出到余额'
        ]
    ];

    public function getEventMap() {
        return $this->eventMap;
    }

    public function scopeMoney($query)
    {
        return $query->where('type', 'money');
    }

    public function scopeCommission($query)
    {
        return $query->where('type', 'commission');
    }

    public function scopeScore($query)
    {
        return $query->where('type', 'score');
    }

    public function getEventTextAttr($value, $data)
    {
        return $this->eventMap[$data['type']][$data['event']] ?? '';
    }
}
