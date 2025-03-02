<?php

namespace app\admin\model\shopro\commission;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\user\User as UserModel;
use app\admin\model\shopro\order\Order as OrderModel;
use app\admin\model\shopro\order\OrderItem as OrderItemModel;

class Order extends Common
{
    const COMMISSION_ORDER_STATUS_NO = 0;  // 不计入
    const COMMISSION_ORDER_STATUS_YES = 1;  // 已计入
    const COMMISSION_ORDER_STATUS_CANCEL = -1;  // 已取消
    const COMMISSION_ORDER_STATUS_BACK = -2;  // 已扣除

    protected $name = 'shopro_commission_order';

    protected $type = [
        'commission_rules' => 'json',
        'commission_time' => 'timestamp'
    ];

    protected $append = [
        'reward_event_text',
        'reward_type_text',
        'commission_order_status_text',
        'commission_reward_status_text'
    ];

    public function getRewardEventTextAttr($value, $data)
    {
        $value = $value ?: ($data['reward_event'] ?? '');
        $eventMap = [
            'paid' => '支付后结算',
            'confirm' => '收货后结算',
            'finish' => '订单完成结算',
            'admin' => '手动结算'
        ];
        return isset($eventMap[$value]) ? $eventMap[$value] : '-';
    }

    public function getRewardTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['reward_type'] ?? '');
        $eventMap = [
            'goods_price' => '商品价',
            'pay_price' => '实际支付价'
        ];
        return isset($eventMap[$value]) ? $eventMap[$value] : '-';
    }

    public function getCommissionOrderStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['commission_order_status'] ?? '');
        $eventMap = [
            -2 => '已扣除',
            -1 => '已取消',
            0 => '不计入',
            1 => '已计入'
        ];
        return isset($eventMap[$value]) ? $eventMap[$value] : '-';
    }

    public function getCommissionRewardStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['commission_reward_status'] ?? '');
        $eventMap = [
            -2 => '已退回',
            -1 => '已取消',
            0 => '未结算',
            1 => '已结算'
        ];
        return isset($eventMap[$value]) ? $eventMap[$value] : '-';
    }

    public function scopeBack($query)
    {
        return $query->where('commission_order_status', self::COMMISSION_ORDER_STATUS_BACK);
    }

    public function scopeYes($query)
    {
        return $query->where('commission_order_status', self::COMMISSION_ORDER_STATUS_YES);
    }

    public function scopeCancel($query)
    {
        return $query->where('commission_order_status', self::COMMISSION_ORDER_STATUS_CANCEL);
    }

    public function buyer()
    {
        return $this->belongsTo(UserModel::class, 'buyer_id', 'id')->field('id, nickname, avatar, mobile');
    }

    public function agent()
    {
        return $this->belongsTo(UserModel::class, 'agent_id', 'id')->field('id, nickname, avatar, mobile');
    }

    public function order()
    {
        return $this->belongsTo(OrderModel::class, 'order_id', 'id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItemModel::class, 'order_item_id', 'id');
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class, 'commission_order_id', 'id');
    }
}
