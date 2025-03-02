<?php

namespace app\admin\model\shopro\wechat;

use app\admin\model\shopro\Common;
use traits\model\SoftDelete;

class Menu extends Common
{
    protected $name = 'shopro_wechat_menu';

    protected $type = [
        'rules' => 'json',
        'publishtime' => 'timestamp',
    ];

    // 追加属性
    protected $append = [
        'status_text'
    ];

    /**
     * 状态列表
     *
     * @return array
     */
    public function statusList()
    {
        return [0 => '未发布', 1 => '已发布'];
    }
}
