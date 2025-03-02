<?php

namespace addons\shopro\service\third\wechat;

use fast\Http;
use addons\shopro\facade\Wechat;


class OpenPlatform
{
    public $wechat;
    protected $request;
    protected $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
        $this->request = request();
        $this->wechat = Wechat::openPlatform();
    }

    public function login()
    {
        $payload = $this->payload;
        if (empty($payload['code'])) {
            error_stop('登陆失败');
        }
        $config = sheep_config('shop.platform.App');

        // 获取accessToken & openid
        $res = Http::get('https://api.weixin.qq.com/sns/oauth2/access_token', [
            'appid' => $config['app_id'],
            'secret' => $config['secret'],
            'code' => $payload['code'],
            'grant_type' => 'authorization_code'
        ]);
        $decryptedData = json_decode($res, true);
        if (isset($decryptedData['errmsg'])) {
            error_stop($decryptedData['errmsg']);
        }

        // 获取userInfo
        $res = Http::get('https://api.weixin.qq.com/sns/userinfo', ['access_token' => $decryptedData['access_token'], 'openid' => $decryptedData['openid']]);
        $userInfo = is_string($res) ? json_decode($res, true) : $res;
        if (isset($userInfo['errmsg'])) {
            error_stop($userInfo['errmsg']);
        }

        $wechatUser = [
            'openid' => $userInfo['openid'],
            'unionid' => $userInfo['unionid'] ?? '',
            'avatar' => $userInfo['headimgurl'],
            'nickname' => $userInfo['nickname'],
        ];
        return $wechatUser;
    }

    public function bind()
    {
        return $this->login();
    }
}
