<?php

namespace app\admin\model\shopro\chat;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\chat\traits\ChatCommon;

class ServiceLog extends Common
{
    use ChatCommon;

    protected $name = 'shopro_chat_service_log';

    protected $append = [
        'room_name'
    ];

    public function chatUser() 
    {
        return $this->belongsTo(User::class, 'chat_user_id');
    }
}
