<?php

namespace addons\shopro\notification;

use addons\shopro\channel\Database;
use addons\shopro\channel\Websocket;
use addons\shopro\notification\traits\Notification as NotificationTrait;

/**
 * 自定义通知
 */
class CustomeNotice extends Notification
{
    use NotificationTrait;

    // 队列延迟时间，必须继承 ShouldQueue 接口
    public $delay = 0;

    public $receiver_type = 'admin';

    // 消息类型  Notification::$notificationType
    public $notification_type = 'system';

    // 发送类型
    public $event = 'custom';

    // 额外发送渠道
    public $channels = [];

    // 额外数据
    public $data = [];

    public $template = [
        'MessageDefaultContent' => '',
    ];

    // 返回的字段列表
    public $returnField = [];



    public function __construct($params = [], $data = [])
    {
        $this->receiver_type = $params['receiver_type'] ?? 'admin';
        $this->notification_type = $params['notification_type'] ?? 'system';
        $this->channels = $params['channels'] ?? [];

        $this->data = $data;
    }



    public function channels($notifiable)
    {
        // 默认发送渠道
        $channels = [Database::class, Websocket::class];

        return array_merge($channels, $this->channels);
    }



    /**
     * 数据库通知数据
     *
     * @param \think\Model $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        $params = $this->getArray();

        return $params;
    }


    /**
     * socket 通知数据
     *
     * @param \think\Model $notifiable
     * @return array
     */
    public function toWebsocket($notifiable)
    {
        $params = $this->getArray();

        return $params;
    }


    /**
     * 组合数据参数
     *
     * @param \think\Model $notifiable
     * @return array
     */
    protected function getArray()
    {
        $params['data'] = [
            'jump_url' => $this->data['jump_url'] ?? ''
        ];

        $params['message_type'] = 'notification';
        $params['class_name'] = static::class;
        $params['message_text'] = $this->data['message_text'] ?? '';
        $params['message_title'] = $this->data['message_title'] ?? '';

        $this->template['MessageDefaultContent'] = $this->data['message_title'] ?? '';

        // 统一跳转地址
        return $params;
    }
}
