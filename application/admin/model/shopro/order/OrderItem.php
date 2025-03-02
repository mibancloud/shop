<?php

namespace app\admin\model\shopro\order;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\order\traits\OrderItemStatus;
use app\admin\model\shopro\activity\Activity;
use app\admin\model\shopro\user\User;

class OrderItem extends Common
{
    use OrderItemStatus;

    protected $name = 'shopro_order_item';

    protected $type = [
        'ext' => 'json'
    ];

    // 追加属性
    protected $append = [
        'dispatch_status_text',
        'dispatch_type_text',
        'aftersale_status_text',
        'refund_status_text',
        'comment_status_text',
        'status_code',
        'status_text',
        'status_desc',
        'btns',
        'activity_type_text',
        'promo_types_text'
    ];

    // 发货状态
    const DISPATCH_STATUS_REFUSE = -1;        // 已拒收
    const DISPATCH_STATUS_NOSEND = 0;       // 未发货
    const DISPATCH_STATUS_SENDED = 1;       // 已发货
    const DISPATCH_STATUS_GETED = 2;        // 已收货


    // 售后状态
    const AFTERSALE_STATUS_REFUSE = -1;       // 拒绝
    const AFTERSALE_STATUS_NOAFTER = 0;       // 未申请
    const AFTERSALE_STATUS_ING = 1;       // 申请售后
    const AFTERSALE_STATUS_COMPLETED = 2;        // 售后完成


    // 退款状态
    const REFUND_STATUS_NOREFUND = 0;       // 退款状态 未申请
    const REFUND_STATUS_AGREE = 1;       // 已同意
    const REFUND_STATUS_COMPLETED = 2;       // 退款完成     

    // 评价状态
    const COMMENT_STATUS_NO = 0;       // 待评价
    const COMMENT_STATUS_OK = 1;       // 已评价


    public function dispatchStatusList()
    {
        return [
            self::DISPATCH_STATUS_REFUSE => '已拒收',
            self::DISPATCH_STATUS_NOSEND => '待发货',
            self::DISPATCH_STATUS_SENDED => '待收货',
            self::DISPATCH_STATUS_GETED => '已收货'
        ];
    }

    public function dispatchTypeList()
    {
        return [
            'express' => '快递物流',
            'autosend' => '自动发货',
            'custom' => '手动发货'
        ];
    }

    public function aftersaleStatusList()
    {
        return [
            self::AFTERSALE_STATUS_REFUSE => '售后驳回',
            self::AFTERSALE_STATUS_NOAFTER => '未申请',
            self::AFTERSALE_STATUS_ING => '申请售后',
            self::AFTERSALE_STATUS_COMPLETED => '已完成'
        ];
    }

    public function refundStatusList()
    {
        return [
            self::REFUND_STATUS_NOREFUND => '未退款',
            self::REFUND_STATUS_AGREE => '退款完成',
            self::REFUND_STATUS_COMPLETED => '退款完成',
        ];
    }

    public function commentStatusList()
    {
        return [
            self::COMMENT_STATUS_NO => '待评价',
            self::COMMENT_STATUS_OK => '已评价',
        ];
    }

    public function getActivityTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['activity_type'] ?? null);

        $list = (new Activity)->typeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getDispatchTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['dispatch_type'] ?? null);

        $list = $this->dispatchTypeList();
        if (strpos(request()->url(), 'addons/shopro') !== false) {
            $list['custom'] = '商家发货';
        }
        return isset($list[$value]) ? $list[$value] : '';
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

    public function getDispatchStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['dispatch_status'] ?? null);

        $list = $this->dispatchStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getAftersaleStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['aftersale_status'] ?? null);

        $list = $this->aftersaleStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getRefundStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['refund_status'] ?? null);

        $list = $this->refundStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getCommentStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['comment_status'] ?? null);

        $list = $this->commentStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    /**
     * 获取建议退款金额，不考虑剩余可退款金额是否够退
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getSuggestRefundFeeAttr($value, $data)
    {
        $current_goods_amount = bcmul($data['goods_price'], (string)$data['goods_num'], 2);
        $total_amount = bcadd($current_goods_amount, $data['dispatch_fee'], 2);
        $suggest_refund_fee = bcsub($total_amount, $data['discount_fee'], 2);        // (商品金额 + 运费金额) - 总优惠(活动，优惠券，包邮优惠)

        return $suggest_refund_fee;
    }


    /**
     * 可以确认收货
     *
     * @param \think\db\Query $query
     * @return void
     */
    public function scopeCanConfirm($query) 
    {
        return $query->where('dispatch_status', OrderItem::DISPATCH_STATUS_SENDED)       // 已发货
            ->whereNotIn('refund_status', [OrderItem::REFUND_STATUS_AGREE, OrderItem::REFUND_STATUS_COMPLETED]);      // 没有退款完成
    }


    /**
     * 可以评价
     *
     * @param \think\db\Query $query
     * @return void
     */
    public function scopeCanComment($query) 
    {
        return $query->where('dispatch_status', OrderItem::DISPATCH_STATUS_GETED)       // 已收货
            ->where('comment_status', OrderItem::COMMENT_STATUS_NO)       // 未评价
            ->whereNotIn('refund_status', [OrderItem::REFUND_STATUS_AGREE, OrderItem::REFUND_STATUS_COMPLETED]);      // 没有退款完成
    }

    public function express()
    {
        return $this->belongsTo(Express::class, 'order_express_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
