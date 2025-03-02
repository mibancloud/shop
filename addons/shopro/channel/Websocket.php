<?php

namespace addons\shopro\channel;

use addons\shopro\notification\Notification;
use addons\shopro\library\Websocket as WebsocketSend;

class Websocket
{

    /**
     * 发送 Websocket 通知
     * @param Notifiable   $notifiable
     * @param Notification $notification
     */
    public function send($notifiable, Notification $notification)
    {
        $data = [];

        if (method_exists($notification, 'toSms')) {
            $data = $notification->toWebsocket($notifiable);

            if ($notification->receiver_type != 'admin') {
                // 目前只有 admin 消息类型发送 socket
                return true;
            }

            // 发送数据
            $requestData = [
                'notifiable' => $notifiable->toArray(),
                'notification_type' => $notification->notification_type,
                'type' => $notification->event,
                'data' => $data,
                'read_time' => null,
                'createtime' => date('Y-m-d H:i:s')
            ];
            // 接收人
            $receiver = [
                'ids' => $notifiable->id,
                'type' => $notifiable->getNotifiableType()
            ];

            try {
                $websocket = new WebsocketSend();
                $result = $websocket->notification([
                    'receiver' => $receiver,
                    'data' => $requestData
                ]);

                if ($result !== true) {
                    // 发送失败
                    \think\Log::error('websocket 通知发送失败：用户：' . $notifiable['id'] . '；类型：' . get_class($notification) . "；发送类型：" . $notification->event . "；错误信息：" . json_encode($result, JSON_UNESCAPED_UNICODE));
                }
            } catch (\Exception $e) {
                // 因为配置较麻烦，这里捕获异常防止因为缺少字段，导致队列一直执行不成功
                format_log_error($e, 'websocket_notification', '用户：' . $notifiable['id'] . '；类型：' . get_class($notification) . "；发送类型：" . $notification->event);
            }
        }

        return true;
    }
}