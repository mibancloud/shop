<?php

namespace app\admin\model\shopro\user;

use app\admin\model\shopro\Common;

class Address extends Common
{
    protected $name = 'shopro_user_address';

    protected $type = [
        'is_default' => 'boolean'
    ];

    // 追加属性
    protected $append = [];


    public function scopeDefault($query)
    {
        return $query->where('is_default', 1);
    }

}
