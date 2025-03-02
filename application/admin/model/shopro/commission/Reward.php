<?php

namespace app\admin\model\shopro\commission;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\user\User as UserModel;
use app\admin\model\shopro\order\Order as OrderModel;
use app\admin\model\shopro\order\OrderItem as OrderItemModel;

class Reward extends Common
{

    const COMMISSION_REWARD_STATUS_PENDING = 0;  // 未结算、待入账
    const COMMISSION_REWARD_STATUS_ACCOUNTED = 1;  // 已结算、已入账
    const COMMISSION_REWARD_STATUS_CANCEL = -1;  // 已取消
    const COMMISSION_REWARD_STATUS_BACK = -2;  // 已退回

    protected $name = 'shopro_commission_reward';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $type = [
        'commission_rules' => 'json',
        'commission_time' => 'timestamp'
    ];
    protected $append = [
        'status_text',
        'type_text'
    ];

    public function statusList()
    {
        return [
            -2 => '已退回',
            -1 => '已取消',
            0 => '未结算',
            1 => '已结算'
        ];
    }

    public function typeList()
    {
        return [
            'commission' => '佣金钱包',
            'money' => '余额钱包',
            'score' => '积分钱包',
            'bank' => '企业付款到银行卡',
            'change' => '企业付款到零钱'
        ];
    }

    /**
     * 待入账
     */
    public function scopePending($query)
    {
        return $query->where('status', self::COMMISSION_REWARD_STATUS_PENDING);
    }
    /**
     * 已退回
     */
    public function scopeBack($query)
    {
        return $query->where('status', self::COMMISSION_REWARD_STATUS_BACK);
    }

    /**
     * 已入账
     */
    public function scopeAccounted($query)
    {
        return $query->where('status', self::COMMISSION_REWARD_STATUS_ACCOUNTED);
    }

    /**
     * 已取消
     */
    public function scopeCancel($query)
    {
        return $query->where('status', self::COMMISSION_REWARD_STATUS_CANCEL);
    }

    /**
     * 待入账和已入账
     *
     * @return void
     */
    public function scopeIncome($query)
    {
        return $query->where('status', 'in', [self::COMMISSION_REWARD_STATUS_ACCOUNTED, self::COMMISSION_REWARD_STATUS_PENDING]);
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
}
