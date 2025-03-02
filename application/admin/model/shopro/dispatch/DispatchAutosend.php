<?php

namespace app\admin\model\shopro\dispatch;

use app\admin\model\shopro\Common;

class DispatchAutosend extends Common
{
    protected $name = 'shopro_dispatch_autosend';

    protected $append = [
    ];


    public function getContentAttr($value, $data)
    {
        $value = $value ?: ($data['content'] ?? '');
        $type = $data['type'] ?? 'text';
        if ($type === 'params') {
            $value = json_decode($value, true);
        }
        return $value;
    }


    public function setContentAttr($value, $data)
    {
        $type = $data['type'] ?? 'text';
        if ($type == 'params') {
            $value = json_encode($value);
        }

        return $value;
    }
}
