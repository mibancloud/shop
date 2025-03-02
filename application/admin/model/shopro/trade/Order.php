<?php

namespace app\admin\model\shopro\trade;

use app\admin\model\shopro\Common;
use traits\model\SoftDelete;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\Pay as PayModel;

class Order extends Common
{
    use SoftDelete;

    protected $name = 'shopro_trade_order';

    protected $deleteTime = 'deletetime';

    protected $type = [
        'ext' => 'json',
        'paid_time' => 'timestamp',
    ];

    // 追加属性
    protected $append = [
        'type_text',
        'status_text',
        'platform_text',
    ];


    // 订单状态
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCEL = 'cancel';
    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';
    const STATUS_COMPLETED = 'completed';

    public function statusList()
    {
        return [
            'closed' => '交易关闭',
            'cancel' => '已取消',
            'unpaid' => '未支付',
            'paid' => '已支付',
            'completed' => '已完成'
        ];
    }


    /**
     * 订单列表状态搜索
     */
    public function searchStatusList()
    {
        return [
            'unpaid' => '待付款',
            'paid' => '已支付',      // 包括刚支付的，以及已完成的所有付过款的订单
            'completed' => '已完成',
            'cancel' => '已取消',
            'closed' => '交易关闭',
        ];
    }


    public function typeList()
    {
        return [
            'recharge' => '充值订单',
        ];
    }


    public function platformList()
    {
        return [
            'H5' => 'H5',
            'WechatOfficialAccount' => '微信公众号',
            'WechatMiniProgram' => '微信小程序',
            'App' => 'App',
        ];
    }


    public function getPlatformTextAttr($value, $data)
    {
        $value = $value ?: ($data['platform'] ?? null);

        $list = $this->platformList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    /**
     * 已支付订单，支付类型
     *
     * @param string $value
     * @param array $data
     * @return void
     */
    public function getPayTypeAttr($value, $data)
    {
        $status = $data['status'] ?? '';
        $payTypes = [];
        if (in_array($status, [self::STATUS_PAID, self::STATUS_COMPLETED])) {
            $payTypes = PayModel::typeTradeOrder()->where('order_id', $data['id'])->where('status', '<>', PayModel::PAY_STATUS_UNPAID)->group('pay_type')->column('pay_type');
        }

        return $payTypes[0] ?? '';
    }


    /**
     * 已支付订单，支付类型文字
     *
     * @param string $value
     * @param array $data
     * @return void
     */
    public function getPayTypeTextAttr($value, $data)
    {
        $pay_types = $this->pay_type;
        $list = (new PayModel)->payTypeList();

        return isset($list[$pay_types]) ? $list[$pay_types] : '';
    }



    // 已关闭
    public function scopeClosed($query)
    {
        return $query->where('status', Order::STATUS_CLOSED);
    }

    // 已取消
    public function scopeCancel($query)
    {
        return $query->where('status', Order::STATUS_CANCEL);
    }

    // 未支付
    public function scopeUnpaid($query)
    {
        return $query->where('status', Order::STATUS_UNPAID);
    }

    // 已支付
    public function scopePaid($query)
    {
        return $query->whereIn('status', [Order::STATUS_PAID, Order::STATUS_COMPLETED]);
    }

    // 已完成
    public function scopeCompleted($query)
    {
        return $query->where('status', Order::STATUS_COMPLETED);
    }


    public function scopeRecharge($query)
    {
        return $query->where('type', 'recharge');
    }



    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function pays()
    {
        return $this->hasMany(PayModel::class, 'order_id', 'id')->typeTradeOrder();
    }
}
