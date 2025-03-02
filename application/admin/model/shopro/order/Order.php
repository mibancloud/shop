<?php

namespace app\admin\model\shopro\order;

use app\admin\model\shopro\Common;
use traits\model\SoftDelete;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\Pay as PayModel;
use app\admin\model\shopro\activity\Activity;
use app\admin\model\shopro\order\traits\OrderScope;
use app\admin\model\shopro\order\traits\OrderStatus;
use app\admin\model\shopro\activity\Order as ActivityOrder;

class Order extends Common
{
    use SoftDelete, OrderScope, OrderStatus;

    protected $name = 'shopro_order';

    protected $deleteTime = 'deletetime';

    protected $type = [
        'ext' => 'json',
        'paid_time' => 'timestamp',
    ];

    // 追加属性
    protected $append = [
        'type_text',
        'status_code',
        'status_text',
        'status_desc',
        'apply_refund_status_text',
        'btns',
        'platform_text',
        'activity_type_text',
        'promo_types_text',
        'wechat_extra_data'
    ];


    // 订单状态
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCEL = 'cancel';
    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';
    const STATUS_COMPLETED = 'completed';
    const STATUS_PENDING = 'pending';       // 待定 后付款


    const APPLY_REFUND_STATUS_NOAPPLY = 0;
    const APPLY_REFUND_STATUS_APPLY = 1;
    const APPLY_REFUND_STATUS_FINISH = 2;
    const APPLY_REFUND_STATUS_REFUSE = -1;


    public function statusList()
    {
        return [
            'closed' => '交易关闭',
            'cancel' => '已取消',
            'unpaid' => '未支付',
            'pending' => '待定',        // 货到付款未付款状态
            'paid' => '已支付',
            'completed' => '已完成'
        ];
    }


    public function applyRefundStatusList()
    {
        return [
            self::APPLY_REFUND_STATUS_NOAPPLY => '未申请',
            self::APPLY_REFUND_STATUS_APPLY => '申请退款',
            self::APPLY_REFUND_STATUS_FINISH => '退款完成',
            self::APPLY_REFUND_STATUS_REFUSE => '拒绝申请'
        ];
    }


    /**
     * 订单列表状态搜索
     */
    public function searchStatusList()
    {
        return [
            'unpaid' => '待付款',
            'paid' => '已支付',      // 包括刚支付的，发货中，和已退款的，以及已完成的所有付过款的订单，不包含货到付款还未真实付款的订单
            'nosend' => '待发货',
            'noget' => '待收货',
            'refuse' => '已拒收',
            'nocomment' => '待评价',
            'completed' => '已完成',
            'aftersale' => '售后',
            'applyRefundIng' => '申请退款',
            'refund' => '已退款',
            'cancel' => '已取消',
            'closed' => '交易关闭',     // 包含货到付款，拒收的商品
        ];
    }


    public function typeList()
    {
        return [
            'goods' => '商城订单',
            'score' => '积分订单'
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


    public function getExtAttr($value, $data)
    {
        $ext = (isset($data['ext']) && $data['ext']) ? json_decode($data['ext'], true) : [];

        // 每类活动优惠金额聚合（可能订单购买多个商品，然后同时参与了两种满减，金额累加）
        $ext['promo_discounts'] = [
            'full_reduce' => 0,
            'full_discount' => 0,
            'free_shipping' => 0,
            'full_gift' => 0
        ];
        if ($ext && isset($ext['promo_infos']) && $ext['promo_infos']) {
            foreach ($ext['promo_infos'] as $key => $info) {
                if ($info['activity_type'] == 'full_gift') {
                    $ext['promo_discounts']['full_gift'] = 1;
                    continue;
                }
                $ext['promo_discounts'][$info['activity_type']] = bcadd((string)$ext['promo_discounts'][$info['activity_type']], (string)$info['promo_discount_money'], 2);
            }
        }

        // 格式化
        $ext['promo_discounts'] = array_map(function ($value) {
            return number_format(floatval($value), 2, '.', '');
        }, $ext['promo_discounts']);

        // 处理时间
        if (isset($ext['closed_time']) && $ext['closed_time']) {
            $ext['closed_date'] = date('Y-m-d H:i:s', $ext['closed_time']);
        }
        if (isset($ext['cancel_time']) && $ext['cancel_time']) {
            $ext['cancel_date'] = date('Y-m-d H:i:s', $ext['cancel_time']);
        }
        if (isset($ext['pending_time']) && $ext['pending_time']) {
            $ext['pending_date'] = date('Y-m-d H:i:s', $ext['pending_time']);
        }
        if (isset($ext['apply_refund_time']) && $ext['apply_refund_time']) {
            $ext['apply_refund_date'] = date('Y-m-d H:i:s', $ext['apply_refund_time']);
        }
        if (isset($ext['send_time']) && $ext['send_time']) {
            $ext['send_date'] = date('Y-m-d H:i:s', $ext['send_time']);
        }
        if (isset($ext['confirm_time']) && $ext['confirm_time']) {
            $ext['confirm_date'] = date('Y-m-d H:i:s', $ext['confirm_time']);
        }
        if (isset($ext['comment_time']) && $ext['comment_time']) {
            $ext['comment_date'] = date('Y-m-d H:i:s', $ext['comment_time']);
        }
        if (isset($ext['completed_time']) && $ext['completed_time']) {
            $ext['completed_date'] = date('Y-m-d H:i:s', $ext['completed_time']);
        }
        if (isset($ext['refund_time']) && $ext['refund_time']) {
            $ext['refund_date'] = date('Y-m-d H:i:s', $ext['refund_time']);
        }
        return $ext;
    }


    public function getPlatformTextAttr($value, $data)
    {
        $value = $value ?: ($data['platform'] ?? null);

        $list = $this->platformList();
        return isset($list[$value]) ? $list[$value] : '';
    }



    /**
     * 微信小程序发货信息管理所需参数
     *
     * @param string|null $value
     * @param array $data
     * @return array
     */
    public function getWechatExtraDataAttr($value, $data)
    {
        $extraData = [];

        if (strpos(request()->url(), 'addons/shopro') !== false && $data['platform'] == 'WechatMiniProgram') {
            // 前端接口，并且是 微信小程序订单，才返这个参数
            $pays = $this->pays;
            foreach ($pays as $pay) {
                if ($pay->status != PayModel::PAY_STATUS_UNPAID && $pay->pay_type == 'wechat') {
                    $extraData['merchant_trade_no'] = $pay->pay_sn;
                    $extraData['transaction_id'] = $pay->transaction_id;
                }
            }
        }

        return $extraData;
    }



    /**
     * 申请退款状态
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getApplyRefundStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['apply_refund_status'] ?? null);

        $list = $this->applyRefundStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getActivityTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['activity_type'] ?? null);
        $ext = $this->ext;

        $list = (new Activity)->typeList();
        $text = isset($list[$value]) ? $list[$value] : '';

        if (in_array($value, ['groupon', 'groupon_ladder'])) {
            // 订单已支付的，或者线下支付(货到付款)的
            if ((in_array($data['status'], [self::STATUS_PAID, self::STATUS_COMPLETED]) || $this->isOffline($data)) 
                && (!isset($ext['groupon_id']) || !$ext['groupon_id'])) 
            {
                // 已支付，并且没有团 id,就是单独购买
                $text .= '-单独购买';
            }
        }

        return $text;
    }


    public function getPromoTypesTextAttr($value, $data)
    {
        $value = $value ?: ($data['promo_types'] ?? null);

        $promoTypes = array_filter(explode(',', $value));
        $texts = [];
        $list = (new Activity)->typeList();
        foreach ($promoTypes as $type) {
            $text = isset($list[$type]) ? $list[$type] : '';
            if ($text) {
                $texts[] = $text;
            }
        }
        return $texts;
    }


    /**
     * 已支付订单，支付类型
     *
     * @param string $value
     * @param array $data
     * @return void
     */
    public function getPayTypesAttr($value, $data)
    {
        $status = $data['status'] ?? '';
        $payTypes = [];
        // 订单已支付的，或者线下支付(货到付款)的
        if (in_array($status, [self::STATUS_PAID, self::STATUS_COMPLETED]) || $this->isOffline($data)) {
            $payTypes = PayModel::typeOrder()->where('order_id', $data['id'])->where('status', '<>', PayModel::PAY_STATUS_UNPAID)->group('pay_type')->column('pay_type');
        }

        return $payTypes;
    }
    /**
     * 已支付订单，支付类型文字
     *
     * @param string $value
     * @param array $data
     * @return void
     */
    public function getPayTypesTextAttr($value, $data)
    {
        $payTypes = $this->pay_types;
        $list = (new PayModel)->payTypeList();
        
        $texts = [];
        foreach ($payTypes as $pay_type) {
            $text = isset($list[$pay_type]) ? $list[$pay_type] : '';

            if ($text) {
                $texts[] = $text;
            }
        }

        return $texts;
    }


    public function getExpressAttr($value, $data)
    {
        return Express::with(['items', 'logs'])->where('order_id', $data['id'])->select();
    }



    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function aftersales()
    {
        return $this->hasMany(Aftersale::class, 'order_id')->order('id', 'desc');
    }


    public function address()
    {
        return $this->hasOne(Address::class, 'order_id', 'id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'order_id', 'id');
    }


    public function pays()
    {
        return $this->hasMany(PayModel::class, 'order_id', 'id')->typeOrder()->order('id', 'desc');
    }


    public function activityOrders()
    {
        return $this->hasMany(ActivityOrder::class, 'order_id');
    }
}
