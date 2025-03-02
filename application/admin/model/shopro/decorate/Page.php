<?php

namespace app\admin\model\shopro\decorate;

use app\admin\model\shopro\Common;

class Page extends Common
{

    protected $autoWriteTimestamp = false;

    protected $name = 'shopro_decorate_page';

    protected $append = [
        // 'type_text'
    ];

    public function getPageAttr($value, $data)
    {
        // 图片默认存本地，使用接口域名
        // $value = str_replace("\"/storage/", "\"" . request()->domain() . "/storage/", $value);
        // $value = str_replace("\"\/storage\/", "\"" . request()->domain() . "/storage/", $value);
        
        return json_decode($value, true);
    }

    public static function buildData($template)
    {
        foreach ($template['home']['data'] as $data) {
            switch ($data['type']) {
                case 'goodsCard':
                    break;
            }
        }
        //    exit();
    }
}
