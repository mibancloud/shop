<?php

namespace app\admin\model\shopro\commission;

use app\admin\model\shopro\Common;

class Level extends Common
{
    protected $pk = 'level';

    protected $name = 'shopro_commission_level';
    
    protected $autoWriteTimestamp = false;

    protected $type = [
        'commission_rules' => 'json',
        'upgrade_rules' => 'json'
    ];

}
