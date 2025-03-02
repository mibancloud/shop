<?php

namespace addons\shopro\job;

use think\queue\Job;
use think\exception\HttpResponseException;

/**
 * 队列消息通知
 */
class Notification extends BaseJob
{
    /**
     * 发送通知
     */
    public function send(Job $job, $data)
    {
        // 删除 job，防止这个队列一直异常无法被删除
        $job->delete();
        
        try {
            // 这里获取到的 $notifiables 和 notification 两个都是数组，不是类，尴尬， 更可恨的 notification 只是 {"delay":0,"event":"changemobile"}
            $notifiables = $data['notifiables'];
            $notification = $data['notification'];
            // 因为 notification 只有参数，需要把对应的类传过来，在这里重新初始化
            $notification_name = $data['notification_name'];
            $notifiable_name = $data['notifiable_name'];

            // 重新实例化 notification 实例
            if (class_exists($notification_name)) {
                $notification = new $notification_name($notification['data']);
            }

            // 重新实例化 notifiable
            if (class_exists($notifiable_name)) {
                $notifiable = new $notifiable_name();
                $notifiables = $notifiable->where('id', 'in', array_column($notifiables, 'id'))->select();
            }

            // 发送消息
            \addons\shopro\library\notify\Notify::sendNow($notifiables, $notification);
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'Notification.send.HttpResponseException', $message);
        } catch (\Exception $e) {
            format_log_error($e, 'Notification.send.' . ($notification->event ?? ''));
        }
    }
}
