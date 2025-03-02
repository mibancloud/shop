<?php

namespace app\admin\controller\shopro\notification\traits;

/**
 * 消息通知，额外方法
 */
trait Notification
{

    protected $notificationTypes = [
        \addons\shopro\notification\order\OrderNew::class,
        \addons\shopro\notification\order\OrderDispatched::class,
        \addons\shopro\notification\order\aftersale\OrderAftersaleChange::class,
        \addons\shopro\notification\order\aftersale\OrderAdminAftersaleChange::class,
        \addons\shopro\notification\order\OrderRefund::class,
        \addons\shopro\notification\order\OrderApplyRefund::class,
        // 商品
        \addons\shopro\notification\goods\StockWarning::class,
        // 钱包
        \addons\shopro\notification\wallet\CommissionChange::class,
        \addons\shopro\notification\wallet\MoneyChange::class,
        \addons\shopro\notification\wallet\ScoreChange::class,
        // 活动
        \addons\shopro\notification\activity\GrouponFail::class,
        \addons\shopro\notification\activity\GrouponFinish::class,
    ];


    /**
     * 根据接收人类型，获取消息类型
     *
     * @param array|string $receiverType
     * @return array
     */
    protected function getNotificationsByReceiverType($receiverType = 'user')
    {
        $receiverType = is_array($receiverType) ? $receiverType : [$receiverType];

        $notifications = $this->getNotifications();

        $receiverNotifications = [];
        foreach ($notifications as $notification) {
            if (in_array($notification['receiver_type'], $receiverType)) {
                $receiverNotifications[] = $notification;
            }
        }

        return $receiverNotifications;
    }



    /**
     * 根据事件类型获取消息
     *
     * @param string $event
     * @return void
     */
    protected function getNotificationByEvent($event)
    {
        $notifications = $this->getNotifications();

        $notifications = array_column($notifications, null, 'event');
        return $notifications[$event] ?? null;
    }



    /**
     * 按照事件类型获取配置分组
     *
     * @param string $event
     * @return array
     */
    protected function getGroupConfigs($event = null)
    {
        // 获取所有配置
        $configs = $this->model->select();
        $newConfigs = [];
        foreach ($configs as $config) {
            $newConfigs[$config['event']][$config['channel']] = $config;
        }

        return $event ? ($newConfigs[$event] ?? []) : $newConfigs;
    }



    /**
     * 获取所有消息类型
     *
     * @return array
     */
    protected function getNotifications()
    {
        $types = [];
        foreach ($this->notificationTypes as $key => $class_name) {
            $class = new $class_name();
            $currentFields = $class->returnField;
            $currentFields['event'] = $class->event;
            $currentFields['receiver_type'] = $class->receiver_type;
            $currentFields['template'] = $class->template;

            $types[] = $currentFields;
        }

        return $types;
    }



    /**
     * 格式化详情返回结果
     *
     * @param array $notification
     * @param string $event
     * @param string $channel
     * @return array
     */
    protected function formatNotification($notification, $event, $channel) 
    {
        $currentConfigs = $this->getGroupConfigs($event);
        $currentConfig = $currentConfigs[$channel] ?? null;
        
        if (in_array($channel, ['WechatOfficialAccount', 'WechatMiniProgram', 'WechatOfficialAccountBizsend'])) {
            $currentTemplate = $notification['template'][$channel] ?? [];
            unset($notification['template']);
            $notification['wechat'] = $currentTemplate;
        }
        
        $notification['type'] = $currentConfig['type'] ?? 'default';
        $content = $currentConfig['content'] ?? null;
        if (!is_array($content)) {
            $notification['content_text'] = $content;
        }
        if ($content && is_array($content)) {
            $contentFields = [];
            if (isset($content['fields']) && $content['fields']) {    // 判断数组是否存在 fields 设置
                $contentFields = array_column($content['fields'], null, 'field');
            }

            $tempFields = array_column($notification['fields'], null, 'field');
            $configField = array_merge($tempFields, $contentFields);

            $content['fields'] = array_values($configField);
            $notification['content'] = $content;
        } else {
            $notification['content'] = [
                'template_id' => '',
                'fields' => $notification['fields']
            ];
        }

        unset($notification['fields']);

        return $notification;
    }



    /**
     * 格式化微信公众号，小程序默认模板时 自动配置 模板字段
     *
     * @return void
     */
    protected function formatWechatTemplateFields($event, $channel, $fields)
    {
        $notification = $this->getNotificationByEvent($event);

        $channelFields = $notification['template'][$channel]['fields'] ?? [];
        $channelFields = array_column($channelFields, null, 'field');

        foreach ($fields as $key => &$field) {
            $field_name = $field['field'] ?? '';
            if ($field_name && isset($channelFields[$field_name])) {
                $field['template_field'] = $channelFields[$field_name]['template_field'] ?? '';
            }
        }
        
        return $fields;
    }
}
