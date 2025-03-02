<?php

return [
    [
        'type' => 'string',
        'name' => 'h5_url',
        'title' => 'H5地址',
        'value' => 'http://shopro.cn/h5/#/',
        'content' => '',
        'tip' => '此处填写H5运行目录，/结尾',
        'rule' => '',
        'extend' => '',
    ],
    [
        'name' => '__tips__',
        'title' => '温馨提示',
        'type' => 'string',
        'content' => [],
        'value' => '填写H5运行目录，以/结尾，注意发布模式路由，若默认hash,结尾/#/',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
];
