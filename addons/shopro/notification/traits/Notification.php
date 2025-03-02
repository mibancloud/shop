<?php

namespace addons\shopro\notification\traits;

use app\admin\model\shopro\Config;

/**
 * 消息通知，额外方法
 */
trait Notification
{

    public function __construct($data = [])
    {
        $this->data = $data;
        $this->initConfig();
    }


    /**
     * 组合发送参数
     *
     * @param \think\Model $notifiable
     * @param string $type
     * @param \Closure|null $callback
     * @return array
     */
    protected function getParams($notifiable, $type, \Closure $callback = null)
    {
        $params = [];
        $params['data'] = $this->getData($notifiable);

        if ($callback) {
            $params = $callback($params);
        }

        $params = $this->formatParams($params, $type);

        return $params;
    }


    /**
     * 数据库通知数据
     *
     * @param \think\Model $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        $type = str_replace('to', '', __FUNCTION__);
        $params = $this->getParams($notifiable, $type);
        $params['message_type'] = 'notification';
        $params['class_name'] = static::class;
        $params['message_text'] = $this->strReplace($this->template['MessageDefaultContent'], $params['data']);
        $params['message_title'] = $this->returnField['name'];

        return $params;
    }


    /**
     * 短信通知数据
     *
     * @param \think\Model $notifiable
     * @return array
     */
    public function toSms($notifiable)
    {
        $type = str_replace('to', '', __FUNCTION__);
        $params = $this->getParams($notifiable, $type, function ($params) use ($notifiable) {
            $params['mobile'] = $notifiable['mobile'] ? $notifiable['mobile'] : '';
            $this->template['MessageDefaultContent'] = $this->strReplace($this->template['MessageDefaultContent'], $params['data']);
            return $params;
        });

        return $params;
    }


    /**
     * 邮件通知数据
     *
     * @param \think\Model $notifiable
     * @return array
     */
    public function toEmail($notifiable)
    {
        $type = str_replace('to', '', __FUNCTION__);

        $params = $this->getParams($notifiable, $type);

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
        $type = str_replace('to', '', __FUNCTION__);
        $params = $this->getParams($notifiable, $type);
        $params['message_type'] = 'notification';
        $params['class_name'] = static::class;
        $params['message_text'] = $this->strReplace($this->template['MessageDefaultContent'], $params['data']);
        $params['message_title'] = $this->returnField['name'];

        return $params;
    }


    /**
     * 微信公众号通知数据
     *
     * @param \think\Model $notifiable
     * @return array
     */
    public function toWechatOfficialAccount($notifiable)
    {
        $type = str_replace('to', '', __FUNCTION__);
        $params = $this->getParams($notifiable, $type, function ($params) use ($notifiable) {
            if ($oauth = $this->getWxOauth($notifiable, 'WechatOfficialAccount')) {
                // 公众号跳转地址，订单详情
                $path = $params['data']['h5_url'] ?? ($params['data']['jump_url'] ?? '');

                // 获取 h5 域名
                $url = $this->getH5DomainUrl($path);

                $params['openid'] = $oauth->openid;
                $params['url'] = $url;
            }

            return $params;
        });

        return $params;
    }


    /**
     * 微信小程序通知数据
     *
     * @param \think\Model $notifiable
     * @return array
     */
    public function toWechatMiniProgram($notifiable)
    {
        $type = str_replace('to', '', __FUNCTION__);
        $params = $this->getParams($notifiable, $type, function ($params) use ($notifiable) {
            if ($oauth = $this->getWxOauth($notifiable, 'WechatMiniProgram')) {
                // 小程序跳转地址，订单详情
                $path = $params['data']['mini_url'] ?? ($params['data']['jump_url'] ?? '');

                // 获取小程序完整路径
                $page = $this->getMiniDomainPage($path);

                $params['openid'] = $oauth->openid;
                $params['page'] = $page;
            }

            return $params;
        });

        return $params;
    }

    /**
     * 替换字符串中的标识
     *
     * @param [type] $content
     * @param [type] $data
     * @return void
     */
    protected function strReplace($content, $data)
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', (string)$value, $content);
        }

        return $content;
    }


    /**
     * 获取微信授权 oauth
     */
    protected function getWxOauth($notifiable, $platform)
    {
        if ($this->receiver_type == 'admin') {          // 后台管理员绑定的都是公众号，但是在 thirdOauth 中存的是 admin
            $platform = 'admin';
        } else {
            $platform = lcfirst(str_replace('Wechat', '', $platform));
        }
        
        $oauth = \app\admin\model\shopro\ThirdOauth::where($this->receiver_type . '_id', $notifiable['id'])
            ->where('provider', 'Wechat')
            ->where('platform', $platform)->find();

        if ($oauth && $oauth->openid) {
            return $oauth;
        }

        return null;
    }


    /**
     * 获取拼接域名的地址
     */
    protected function getH5DomainUrl($path)
    {
        $url = $path;
        $domain = Config::getConfigField('shop.basic.domain');
        if ($domain) {
            $domain = rtrim($domain, '/');
            $url = $domain . "/?page=" . urlencode($path);
        }

        return $url;
    }



    /**
     * 获取拼接的小程序地址
     */
    protected function getMiniDomainPage($path)
    {
        return "pages/index/index?page=" . urlencode($path);
    }
}
