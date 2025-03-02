<?php

namespace addons\shopro\notification;

use think\queue\ShouldQueue;
use addons\shopro\channel\Database;
use addons\shopro\channel\Websocket;
use addons\shopro\channel\Sms;
use addons\shopro\channel\WechatMiniProgram;
use addons\shopro\channel\WechatOfficialAccount;
use addons\shopro\channel\WechatOfficialAccountBizsend;
use addons\shopro\channel\Email;
use app\admin\model\shopro\notification\Config;

/**
 * 消息通知基类
 */
class Notification implements ShouldQueue
{
    // 队列名称，必须继承 ShouldQueue 接口

    protected $config = null;


    public function initConfig() 
    {
        // 缓存 5 分钟
        $config = Config::where('event', $this->event)->select();

        $this->config = array_column($config, null, 'channel');
    }


    // 返回发送方式
    public function channels($notifiable)
    {
        $channels = [Database::class, Websocket::class];

        if (isset($this->config['Sms']) && $this->config['Sms']['status'] == 'enable') {
            $channels[] = Sms::class;
        }

        if (isset($this->config['Email']) && $this->config['Email']['status'] == 'enable') {
            $channels[] = Email::class;
        }

        if (isset($this->config['WechatOfficialAccount']) && $this->config['WechatOfficialAccount']['status'] == 'enable') {
            $channels[] = WechatOfficialAccount::class;
        }

        if (isset($this->config['WechatMiniProgram']) && $this->config['WechatMiniProgram']['status'] == 'enable') {
            $channels[] = WechatMiniProgram::class;
        }

        return $channels;
    }


    // 格式化模板数据
    public function formatParams($params, $type) 
    {
        $paramsData = $params['data'] ?? [];

        $config = $this->config[$type] ?? [];

        if (!$config) {
            // 不能发送
            return $params;
        }
        
        $newData = [];
        if ($type == 'Email') {
            $newContent = $config['content'];
            if (preg_match_all("/(?<=(p:{)).+?(?=})/", $config['content'], $matches)) {
                foreach ($matches[0] as $k => $field) {
                    $fieldVal = $paramsData[$field] ?? '';
                    $newContent = str_replace("p:{" . $field . "}", $fieldVal, $newContent);
                }
            }

            $params['content'] = $newContent;
        } else {
            $content_arr = $config['content'];

            if (isset($content_arr['template_id']) && isset($content_arr['fields'])) {
                if (in_array($type, ['WechatOfficialAccountBizsend', 'WechatOfficialAccount', 'WechatMiniProgram', 'Sms'])) {
                    $params['template_id'] = $content_arr['template_id'];
                }

                foreach ($content_arr['fields'] as $key => $data) {
                    // 用户填写了才处理，没填的字段直接 pass
                    if (isset($data['template_field']) && $data['template_field']) {
                        if (isset($data['field'])) {
                            $value = $paramsData[$data['field']] ?? '-';
                        } else {
                            $value = $data['value'];
                        }
                        $value = $value ?: '-';

                        $value = $this->substrParams($data['template_field'], $value, $type);

                        if (in_array($type, ['WechatMiniProgram', 'WechatOfficialAccountBizsend'])) {
                            $newData[$data['template_field']] = ['value' => $value];
                        } else {
                            $newData[$data['template_field']] = $value;
                        }
                    }
                }
            }

            $params['data'] = $newData;
        }

        return $params;
    }


    // 字符串截取
    public function substrParams($key, $value, $type) 
    {
        if ($type == 'sms') {
            $value = mb_substr($value, 0, 18);
        } else if (in_array($type, ['WechatMiniProgram', 'WechatOfficialAccount', 'WechatOfficialAccountBizsend'])) {
            $value = $this->substrMiniParams($key, $value);
        }

        return $value;
    }


    // 小程序裁剪参数
    private function substrMiniParams($key, $value) 
    {
        switch(true) {
            case strpos($key, 'thing') !== false;           // 事物
                $value = mb_substr($value, 0, 20);
                break;
            case strpos((string)$key, 'number') !== false;          // 数字
                $value = mb_substr((string)$value, 0, 32);
                break;
            case strpos($key, 'letter') !== false;          // 字母
                $value = mb_substr($value, 0, 32);
                break;
            case strpos($key, 'symbol') !== false;          // 符号
                $value = mb_substr($value, 0, 5);
                break;
            case strpos($key, 'character_string') !== false;// 字符串
                $value = mb_substr($value, 0, 32);
                break;
            case strpos($key, 'phone_number') !== false;    // 电话	
                $value = mb_substr($value, 0, 17);
                break;
            case strpos($key, 'car_number') !== false;      // 车牌	
                $value = mb_substr($value, 0, 8);
                break;
            case strpos($key, 'name') !== false;            // 姓名	
                $value = mb_substr($value, 0, 10);
                break;
            case strpos($key, 'phrase') !== false;          // 汉字	
                $value = mb_substr($value, 0, 5);
                break;
        }

        return $value;
    }



    /**
     * 发送成功
     */
    public function sendOk($channel) {
        // 更新发送条数
        Config::where('event', $this->event)->where('channel', $channel)->setInc('send_num');
    }


    /**
     * 设置延迟时间
     */
    public function delay($second = 0) {
        if (!($this instanceof ShouldQueue)) {
            error_stop("该消息类型不支持队列，请先继承队列");
        }
        $this->delay = $second;

        return $this;
    }


    
}
