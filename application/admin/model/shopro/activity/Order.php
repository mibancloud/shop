<?php

namespace app\admin\model\shopro\activity;

use app\admin\model\shopro\Common;
use addons\shopro\facade\Activity as ActivityFacade;

class Order extends Common
{

    protected $name = 'shopro_activity_order';

    protected $type = [
        'ext' => 'json',
    ];

    // 追加属性
    protected $append = [
        'status_text',
        'activity_type_text',
        'discount_text'
    ];

    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';

    public function statusList()
    {
        return [
            'unpaid' => '未支付',
            'paid' => '已支付',
        ];
    }

    // 未支付
    public function scopeUnpaid($query)
    {
        return $query->where('status', self::STATUS_UNPAID);
    }

    // 已支付
    public function scopePaid($query)
    {
        return $query->whereIn('status', [self::STATUS_PAID]);
    }



    public function getActivityTypeTextAttr($value, $data) 
    {
        $value = $value ?: ($data['activity_type'] ?? null);
        $ext = $this->ext;

        $list = (new Activity)->typeList();
        $text = isset($list[$value]) ? $list[$value] : '';

        if (in_array($value, ['groupon', 'groupon_ladder'])) {
            if (in_array($data['status'], [self::STATUS_PAID]) && (!isset($ext['groupon_id']) || !$ext['groupon_id'])) {
                // 已支付，并且没有团 id,就是单独购买
                $text .= '-单独购买';
            }
        }

        return $text;
    }


    public function getDiscountTextAttr($value, $data) 
    {
        $ext = $this->ext;
        $discount_text = '';
        if ($ext && isset($ext['rules']) && $ext['rules']) {
            $tags = ActivityFacade::formatRuleTags([
                'type' => $ext['rules']['rule_type'],
                'discounts' => [$ext['rules']['discount_rule']]
            ], $data['activity_type']);

            $discount_text = $tags[0] ?? '';
        }

        return $discount_text ?: $this->activity_type_text;
    }



    public function getGoodsIdsAttr($value, $data)
    {
        return $this->attrFormatComma($value, $data, 'goods_ids', true);
    }
    


}
