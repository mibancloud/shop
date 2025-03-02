<?php

namespace app\admin\model\shopro\goods;

use app\admin\model\shopro\Common;

class Sku extends Common
{    
    protected $name = 'shopro_goods_sku';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    protected $append = [
    ];

    public function children() 
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
