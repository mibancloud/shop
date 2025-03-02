<?php

namespace app\admin\model\shopro\activity;

use app\admin\model\shopro\Common;

class Signin extends Common
{

    protected $name = 'shopro_activity_signin';

    protected $type = [
        'rules' => 'json'
    ];

    // 追加属性
    protected $append = [
        
    ];

}
