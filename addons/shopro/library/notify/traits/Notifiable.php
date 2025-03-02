<?php

namespace addons\shopro\library\notify\traits;

/**
 * 消息通知 trait
 */

trait Notifiable
{
    public function notify ($notification) {
        return \addons\shopro\library\notify\Notify::send([$this], $notification);
    }


    /**
     * 获取 notifiable 身份类型 admin， user
     *
     * @return void
     */
    public function getNotifiableType()
    {
        $notifiable_type = str_replace('\\', '', strtolower(strrchr(static::class, '\\')));
        return $notifiable_type;
    }
}
