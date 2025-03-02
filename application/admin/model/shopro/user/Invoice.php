<?php

namespace app\admin\model\shopro\user;

use app\admin\model\shopro\Common;

class Invoice extends Common
{
    protected $name = 'shopro_user_invoice';

    // 追加属性
    protected $append = [
        'type_text',
    ];


    public function typeList()
    {
        return [
            'person' => '个人',
            'company' => '企/事业单位',
        ];
    }

}
