<?php

namespace addons\shopro\job;

use think\Log;

/**
 * BaseJob 基类
 */
class BaseJob
{


    public function failed($data)
    {
        // 失败队列，这里不用添加了， 后续程序会自动添加，这里可以发送邮件或者通知

        // 记录日志
        // \think\Db::name('shopro_failed_job')->insert([
        //     'data' => json_encode($data),
        //     'createtime' => time(),
        //     'updatetime' => time()
        // ]);
    }
}