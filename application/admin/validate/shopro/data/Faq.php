<?php

namespace app\admin\validate\shopro\data;

use think\Validate;

class Faq extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'title' => 'require',
        'content' => 'require',
        'status' => 'require',
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'title.require' => '请填写 Faq 标题',
        'content.require' => '请填写 Faq 内容',
        'status.require' => '请选择 Faq 状态',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['title', 'content', 'status'],
        'edit' => ['title', 'content', 'status'],
    ];
    
}
