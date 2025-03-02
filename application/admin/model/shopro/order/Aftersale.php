<?php

namespace app\admin\model\shopro\order;

use traits\model\SoftDelete;
use app\admin\model\shopro\Common;
use app\admin\model\shopro\user\User;

class Aftersale extends Common
{
    use SoftDelete;

    protected $name = 'shopro_order_aftersale';

    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'type_text',
        'btns',
        'dispatch_status_text',
        'aftersale_status_text',
        'aftersale_status_desc',
        'refund_status_text'
    ];


    // 发货状态
    const DISPATCH_STATUS_REFUSE = -1;       // 拒收
    const DISPATCH_STATUS_NOSEND = 0;       // 未发货
    const DISPATCH_STATUS_SENDED = 1;       // 已发货
    const DISPATCH_STATUS_GETED = 2;        // 已收货


    // 售后状态
    const AFTERSALE_STATUS_CANCEL = -2;       // 已取消
    const AFTERSALE_STATUS_REFUSE = -1;       // 拒绝
    const AFTERSALE_STATUS_NOOPER = 0;       // 未处理
    const AFTERSALE_STATUS_ING = 1;       // 申请售后
    const AFTERSALE_STATUS_COMPLETED = 2;        // 售后完成


    // 退款状态
    const REFUND_STATUS_NOREFUND = 0;       // 未退款
    const REFUND_STATUS_AGREE = 1;       // 已同意

    public function typeList()
    {
        return [
            'refund' => '仅退款',
            'return' => '退货退款',
            'other' => '其他'
        ];
    }


    public function dispatchStatusList()
    {
        return [
            self::DISPATCH_STATUS_REFUSE => '已拒收',
            self::DISPATCH_STATUS_NOSEND => '未发货',
            self::DISPATCH_STATUS_SENDED => '已发货',
            self::DISPATCH_STATUS_GETED => '已收货'
        ];
    }


    public function aftersaleStatusList()
    {
        return [
            self::AFTERSALE_STATUS_CANCEL => '已取消',
            self::AFTERSALE_STATUS_REFUSE => '拒绝',
            self::AFTERSALE_STATUS_NOOPER => '未处理',
            self::AFTERSALE_STATUS_ING => '申请售后',
            self::AFTERSALE_STATUS_COMPLETED => '售后完成'
        ];
    }

    public function aftersaleStatusDescList()
    {
        return [
            self::AFTERSALE_STATUS_CANCEL => '买家取消了售后申请',
            self::AFTERSALE_STATUS_REFUSE => '卖家拒绝了售后申请',
            self::AFTERSALE_STATUS_NOOPER => '买家申请了售后，请及时处理',
            self::AFTERSALE_STATUS_ING => '售后正在处理中',
            self::AFTERSALE_STATUS_COMPLETED => '售后已完成'
        ];
    }



    public function refundStatusList()
    {
        return [
            self::REFUND_STATUS_NOREFUND => '未退款',
            self::REFUND_STATUS_AGREE => '同意退款',
        ];
    }

    // 已取消
    public function scopeCancel($query)
    {
        return $query->where('aftersale_status', self::AFTERSALE_STATUS_CANCEL);
    }

    // 已拒绝
    public function scopeRefuse($query)
    {
        return $query->where('aftersale_status', self::AFTERSALE_STATUS_REFUSE);
    }

    public function scopeNoOper($query)
    {
        return $query->where('aftersale_status', self::AFTERSALE_STATUS_NOOPER);
    }

    // 处理中
    public function scopeIng($query)
    {
        return $query->where('aftersale_status', self::AFTERSALE_STATUS_ING);
    }


    // 处理完成
    public function scopeCompleted($query)
    {
        return $query->where('aftersale_status', self::AFTERSALE_STATUS_COMPLETED);
    }

    // 需要处理的，包含未处理，和处理中的，个人中心显示售后数量
    public function scopeNeedOper($query)
    {
        return $query->whereIn('aftersale_status', [self::AFTERSALE_STATUS_NOOPER, self::AFTERSALE_STATUS_ING]);
    }

    /**
     * 后台售后列表，主表是 order 表时不用用 scope 了
     *
     * @param [type] $scope
     * @return void
     */
    public static function getScopeWhere($scope)
    {
        $where = [];
        switch ($scope) {
            case 'cancel':
                $where['aftersale_status'] = self::AFTERSALE_STATUS_CANCEL;
                break;
            case 'refuse':
                $where['aftersale_status'] = self::AFTERSALE_STATUS_REFUSE;
                break;
            case 'nooper':
                $where['aftersale_status'] = self::AFTERSALE_STATUS_NOOPER;
                break;
            case 'ing':
                $where['aftersale_status'] = self::AFTERSALE_STATUS_ING;
                break;
            case 'completed':
                $where['aftersale_status'] = self::AFTERSALE_STATUS_COMPLETED;
                break;
        }

        return $where;
    }

    // 可以取消
    public function scopeCanCancel($query)
    {
        // 未处理，处理中，可以取消
        return $query->where('aftersale_status', 'in', [
            self::AFTERSALE_STATUS_NOOPER,
            self::AFTERSALE_STATUS_ING
        ]);
    }


    // 可以操作
    public function scopeCanOper($query)
    {
        // 未处理，处理中，可以 操作退款，拒绝，完成
        return $query->where('aftersale_status', 'in', [
            self::AFTERSALE_STATUS_NOOPER,
            self::AFTERSALE_STATUS_ING
        ]);
    }

    // 可以删除
    public function scopeCanDelete($query)
    {
        // 取消，拒绝，完成可以删除
        return $query->where('aftersale_status', 'in', [
            self::AFTERSALE_STATUS_CANCEL,
            self::AFTERSALE_STATUS_REFUSE,
            self::AFTERSALE_STATUS_COMPLETED
        ]);
    }


    public function getDispatchStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['dispatch_status'] ?? null);

        $list = $this->dispatchStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getBtnsAttr($value, $data)
    {
        $btns = [];
        switch ($data['aftersale_status']) {
            case self::AFTERSALE_STATUS_NOOPER:
            case self::AFTERSALE_STATUS_ING:
                $btns[] = 'cancel';
                break;
            case self::AFTERSALE_STATUS_CANCEL:
            case self::AFTERSALE_STATUS_REFUSE:
            case self::AFTERSALE_STATUS_COMPLETED:
                $btns[] = 'delete';
                break;
        }

        return $btns;
    }
    

    public function getAftersaleStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['aftersale_status'] ?? null);

        $list = $this->aftersaleStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }
    
    public function getAftersaleStatusDescAttr($value, $data)
    {
        $value = $value ?: ($data['aftersale_status'] ?? null);

        $list = $this->aftersaleStatusDescList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    


    public function getRefundStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['refund_status'] ?? null);

        $list = $this->refundStatusList();
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

    public function logs()
    {
        return $this->hasMany(AftersaleLog::class, 'order_aftersale_id', 'id')->order('id', 'desc');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
