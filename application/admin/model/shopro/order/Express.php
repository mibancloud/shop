<?php

namespace app\admin\model\shopro\order;

use app\admin\model\shopro\Common;

class Express extends Common
{
    protected $name = 'shopro_order_express';

    protected $type = [
        'ext' => 'json'
    ];

    // 追加属性
    protected $append = [
        'status_text'
    ];


    public function statusList()
    {
        return [
            'noinfo' => '暂无信息',
            'collect' => '已揽件',
            'transport' => '运输中',
            'delivery' => '派送中',
            'signfor' => '已签收',
            'refuse' => '用户拒收',
            'difficulty' => '问题件',
            'invalid' => '无效件',
            'timeout' => '超时单',
            'fail' => '签收失败',
            'back' => '退回',
        ];
    }


    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_express_id', 'id');
    }


    public function logs()
    {
        return $this->hasMany(ExpressLog::class, 'order_express_id', 'id')->order('id', 'desc');
    }
}
