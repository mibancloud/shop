<?php

namespace app\admin\validate\shopro;

use think\Validate;

class Category extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name' => 'require',
        'style' => 'require',
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'name.require'     => '请填写自定义分类名称',
        'style.require'     => '请选择分类样式',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  =>  ['name', 'style'],
        'edit' => ['name', 'style'],
    ];
    
}
