<?php

namespace app\admin\model\shopro;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\user\User;

class Feedback extends Common
{
    protected $name = 'shopro_feedback';

    protected $type = [
        'images' => 'json'
    ];
    
    protected $append = [
        'status_text'
    ];


    /**
     * 类型列表
     *
     * @return array
     */
    public function statusList()
    {
        return ['0' => '待处理', '1' => '已处理'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->field('id, nickname, avatar, mobile');
    }

}
