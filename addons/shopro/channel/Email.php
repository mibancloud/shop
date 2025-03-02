<?php

namespace addons\shopro\channel;

use addons\shopro\notification\Notification;
use think\Validate;
use app\common\library\Email as SendEmail;

class Email
{

    public function __construct()
    {
    }


    /**
     * 发送 微信模板消息
     *
     * @param  mixed  $notifiable       // 通知用户
     * @param  通知内容
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $data = [];

        if (method_exists($notification, 'toEmail')) {
            $data = $notification->toEmail($notifiable);

            if ($data && isset($notifiable['email']) && Validate::is($notifiable['email'], "email")) {
                try {
                    $email = new SendEmail;
                    $result = $email
                        ->to($notifiable['email'], $notifiable['nickname'])
                        ->subject(($data['data'] ? $data['data']['template'] : '邮件通知'))
                        ->message('<div style="min-height:550px; padding: 50px 20px 100px;">' . $data['content'] . '</div>')
                        ->send();
                    if ($result) {
                        // 发送成功
                        $notification->sendOk('Email');
                    } else {
                        // 邮件发送失败
                        \think\Log::error('邮件消息发送失败：用户：' . $notifiable['id'] . '；类型：' . get_class($notification) . "；发送类型：" . $notification->event . "；错误信息：" . json_encode($email->getError()));
                    }
                } catch (\Exception $e) {
                    // 因为配置较麻烦，这里捕获异常防止因为缺少字段，导致队列一直执行不成功
                    format_log_error($e, 'email_notification', '用户：' . $notifiable['id'] . '；类型：' . get_class($notification) . "；发送类型：" . $notification->event);
                }

                return true;
            }

            // 没有openid
            \think\Log::error('邮件消息发送失败，没有 email，或 email 格式不正确：用户：' . $notifiable['id'] . '；类型：' . get_class($notification) . "；发送类型：" . $notification->event);
        }

        return true;
    }
}
