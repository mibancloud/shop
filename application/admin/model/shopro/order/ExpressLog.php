<?php

namespace app\admin\model\shopro\order;

use app\admin\model\shopro\Common;

class ExpressLog extends Common
{
    protected $name = 'shopro_order_express_log';

    protected $type = [

    ];

    // 追加属性
    protected $append = [
        'status_text'
    ];


    public function statusList()
    {
        return [
            'noinfo' => '',
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
}
