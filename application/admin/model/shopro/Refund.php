<?php

namespace app\admin\model\shopro;

use app\admin\model\shopro\Common;

class Refund extends Common
{
    protected $name = 'shopro_refund';

    // 追加属性
    protected $append = [
        'status_text'
    ];

    const STATUS_ING = 'ing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAIL = 'fail';

    public function statusList()
    {
        return [
            self::STATUS_ING => '退款中',
            self::STATUS_COMPLETED => '退款完成',
            self::STATUS_FAIL => '退款失败'
        ];
    }


    public function refundTypeList()
    {
        return [
            'back' => '原路退回',
            'money' => '退回到余额'
        ];
    }

}
