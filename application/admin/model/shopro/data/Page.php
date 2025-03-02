<?php

namespace app\admin\model\shopro\data;

use app\admin\model\shopro\Common;

class Page extends Common
{

    // 表名
    protected $name = 'shopro_data_page';
    
    // 追加属性
    protected $append = [
    ];

    public function children()
    {
        return $this->hasMany(self::class, 'group', 'group')->order('id asc');
    }
}
