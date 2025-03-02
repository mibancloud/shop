<?php

namespace app\admin\model\shopro\activity;

use app\admin\model\shopro\Common;

class GiftLog extends Common
{
    protected $name = 'shopro_activity_gift_log';

    protected $type = [
        'rules' => 'json',
        'errors' => 'json'
    ];

    // 追加属性
    protected $append = [
        'type_text',
        'status_text'
    ];


    /**
     * 默认状态列表
     *
     * @return array
     */
    public function typeList()
    {
        return [
            'coupon' => '优惠券',
            'score' => '积分',
            'money' => '余额',
            'goods' => '商品',
        ];
    }


    /**
     * 默认状态列表
     *
     * @return array
     */
    public function statusList()
    {
        return [
            'waiting' => '等待赠送',
            'finish' => '赠送完成',
            'fail' => '赠送失败',
        ];
    }


    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function scopeOpered($query)
    {
        return $query->whereIn('status', ['finish', 'fail']);
    }

    public function scopeFinish($query)
    {
        return $query->where('status', 'finish');
    }

    public function scopeFail($query)
    {
        return $query->where('status', 'fail');
    }
}
