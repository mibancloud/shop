<?php

namespace app\admin\model\shopro\chat;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\chat\traits\ChatCommon;

class CommonWord extends Common
{
    use ChatCommon;

    protected $name = 'shopro_chat_common_word';

    protected $append = [
        'status_text',
        'room_name'
    ];

    public function scopeRoomId($query, $room_id)
    {
        return $query->where('room_id', $room_id);
    }
}
