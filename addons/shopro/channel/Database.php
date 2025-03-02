<?php

namespace addons\shopro\channel;

use addons\shopro\notification\Notification;
use app\admin\model\shopro\notification\Notification as NotificationModel;

class Database
{

    public function __construct()
    {
    }


    /**
     * 发送 模板消息
     *
     * @param  mixed  $notifiable       // 通知用户
     * @param  通知内容
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $data = [];

        if (method_exists($notification, 'toDatabase')) {
            $data = $notification->toDatabase($notifiable);

            $notificationModel = new NotificationModel();
            $notificationModel->id = \fast\Random::uuid();
            $notificationModel->notification_type = $notification->notification_type;
            $notificationModel->type = $notification->event;
            $notificationModel->notifiable_id = $notifiable['id'];
            $notificationModel->notifiable_type = $notifiable->getNotifiableType();
            $notificationModel->data = $data;

            $notificationModel->save();
        }

        return true;
    }
}
