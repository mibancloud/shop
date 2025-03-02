<?php

namespace addons\shopro\channel;

use addons\shopro\notification\Notification;

class Sms
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

        if (method_exists($notification, 'toSms')) {
            $data = $notification->toSms($notifiable);

            if ($data && $data['mobile'] && isset($data['template_id'])) {
                $mobile = $data['mobile'];
                $sendData = $data['data'] ?? [];

                $params = [
                    'mobile'   => $mobile,
                    'msg'      => $sendData,
                    'template' => $data['template_id'],
                    'default_content' => $notification->template['MessageDefaultContent'] ?? null       // 短信宝使用
                ];

                if (in_array('smsbao', get_addonnames())) {
                    // 如果是短信宝，msg 就是 default_content 的内容
                    $params['msg'] = $params['default_content'];
                }
                $result = \think\Hook::listen('sms_notice', $params, null, true);

                if (!$result) {
                    // 短信发送失败
                    \think\Log::error('短信发送失败：用户：'. $notifiable['id'] . '；类型：' . get_class($notification) . "；发送类型：" . $notification->event);
                } else {
                    // 发送成功
                    $notification->sendOk('Sms');
                }

                return true;
            }
            // 没有手机号
            \think\Log::error('短信发送失败，没有手机号：用户：' . $notifiable['id'] . '；类型：' . get_class($notification) . "；发送类型：" . $notification->event);
        }

        return true;
    }
}
