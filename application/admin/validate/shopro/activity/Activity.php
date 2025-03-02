<?php

namespace app\admin\validate\shopro\activity;

use think\Validate;

class Activity extends Validate
{
    protected $rule = [
        'title' => 'require',
        'type' => 'require',
        'start_time' => 'require',
        'end_time' => 'require',
        'rules' => 'require|array',
    ];

    protected $message  =   [
        'title.require'     => '请填写活动标题',
        'type.require'     => '活动类型不正确',
        'start_time.require'     => '请选择活动开始时间',
        'end_time.require'     => '请选择活动结束时间',
        'rules.require'     => '缺少活动规则',
        'rules.array'     => '活动规则不正确',
    ];


    protected $scene = [
        'add'  =>  ['title', 'type', 'start_time', 'end_time', 'rules'],
    ];
}
