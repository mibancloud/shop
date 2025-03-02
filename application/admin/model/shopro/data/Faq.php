<?php

namespace app\admin\model\shopro\data;

use app\admin\model\shopro\Common;

class Faq extends Common
{

    
    // 表名
    protected $name = 'shopro_data_faq';
    

    // 追加属性
    protected $append = [
        'status_text'
    ];
    
}
