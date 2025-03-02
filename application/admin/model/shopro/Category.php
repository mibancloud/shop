<?php

namespace app\admin\model\shopro;

use app\admin\model\shopro\Common;

class Category extends Common
{
    protected $name = 'shopro_category';

    // 追加属性
    protected $append = [
        'status_text',
    ];


    public function getChildrenString($category)
    {
        $style = $category->style;
        $string = 'children';
        if (strpos($style, 'second') === 0) {
            $string .= '.children';
        } else if (strpos($style, 'third') === 0) {
            $string .= '.children.children';
        }

        return $string;
    }


    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->normal()->order('weigh', 'desc')->order('id', 'asc');
    }
}
