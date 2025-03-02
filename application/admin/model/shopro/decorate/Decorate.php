<?php

namespace app\admin\model\shopro\decorate;

use app\admin\model\shopro\Common;
use traits\model\SoftDelete;

class Decorate extends Common
{
    use SoftDelete;

    protected $deleteTime = 'deletetime';

    protected $name = 'shopro_decorate';

    protected $type = [
    ];

    protected $append = [
        'status_text',
        'type_text'
    ];

    public function typeList()
    {
        return [
            'template' => '店铺模板',
            'diypage' => '自定义页面'
        ];
    }

    public function scopeTemplate($query)
    {
        return $query->where('type', 'template');
    }


    public function scopeTypeDiypage($query)
    {
        return $query->where('type', 'diypage');
    }

    public function scopeDesigner($query)
    {
        return $query->where('type', 'designer');
    }

    public function page()
    {
        return $this->hasMany(Page::class, 'decorate_id', 'id');
    }

    public function diypage()
    {
        return $this->hasOne(Page::class, 'decorate_id', 'id')->where('type', 'diypage');
    }

    public function getPlatformAttr($value, $data)
    {
        if($value) {
            return explode(',', $value);
        }else {
            return [];
        }
    }

    public function setPlatformAttr($value, $data)
    {
        if($value) {
            return implode(',', $value);
        }else {
            return "";
        }
    }
}
