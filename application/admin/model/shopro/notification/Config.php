<?php

namespace app\admin\model\shopro\notification;

use app\admin\model\shopro\Common;

class Config extends Common
{

    protected $name = 'shopro_notification_config';

    protected $type = [
        'content' => 'json',
    ];


    protected $append = [
        'status_text',
    ];
}
