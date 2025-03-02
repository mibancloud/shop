<?php

namespace app\admin\model\shopro;

use app\admin\model\shopro\Common;

class Pay extends Common
{
    protected $name = 'shopro_pay';

    // 追加属性
    protected $append = [
        'pay_type_text',
        'status_text'
    ];

    const PAY_STATUS_UNPAID = 'unpaid';
    const PAY_STATUS_PAID = 'paid';
    const PAY_STATUS_REFUND = 'refund';

    public function statusList()
    {
        return [
            self::PAY_STATUS_UNPAID => '未支付',
            self::PAY_STATUS_PAID => '已支付',
            self::PAY_STATUS_REFUND => '已退款'
        ];
    }


    public function payTypeList()
    {
        return [
            'wechat' => '微信支付',
            'alipay' => '支付宝',
            'money' => '钱包支付',
            'score' => '积分支付',
            'offline' => '货到付款',
        ];
    }


    public function scopeTypeOrder($query)      // scopeOrder 调用时候，和 order 排序方法冲突了
    {
        return $query->where('order_type', 'order');
    }


    public function scopeTypeTradeOrder($query)
    {
        return $query->where('order_type', 'trade_order');
    }


    public function scopePaid($query)
    {
        return $query->where('status', self::PAY_STATUS_PAID);
    }


    public function scopeIsMoney($query)
    {
        return $query->whereIn('pay_type', ['wechat', 'alipay', 'money']);
    }


    /**
     * 通用类型获取器
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getPayTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['pay_type'] ?? null);

        $list = $this->payTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }
}
