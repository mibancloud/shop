<?php

namespace app\admin\model\shopro\data;

use app\admin\model\shopro\Common;

class Area extends Common
{
    protected $autoWriteTimestamp = false;
    
    // 表名
    protected $name = 'shopro_data_area';


    public function children()
    {
        return $this->hasMany(self::class, 'pid', 'id');
    }
}
