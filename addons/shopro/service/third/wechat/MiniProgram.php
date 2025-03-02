<?php

namespace addons\shopro\service\third\wechat;

use addons\shopro\facade\Wechat;
use fast\Random;
use app\common\library\Auth;
use app\admin\model\shopro\ThirdOauth;


class MiniProgram
{
    public $wechat;
    protected $request;
    protected $payload;

    public function __construct($payload = [])
    {
        $this->payload = $payload;
        $this->wechat = Wechat::miniProgram();
    }

    // 小程序登录
    public function login()
    {
        // https://developers.weixin.qq.com/community/develop/doc/00022c683e8a80b29bed2142b56c01
        if (empty($this->payload['sessionId'])) {
            error_stop('未获取到登陆态, 请重试', -1);
        }

        $sessionData = redis_cache($this->payload['sessionId']);

        if (empty($sessionData)) {
            error_stop('登陆态已过期, 请重试', -1);
        }

        $wechatUser = [
            'openid' => $sessionData['openid'],
            'unionid' => $sessionData['unionid'] ?? '',
            'mobile' => '',
            'avatar' => '',
            'nickname' => '',
        ];
        return $wechatUser;
    }

    public function bind()
    {
        if (empty($this->payload['sessionId'])) {
            error_stop('未获取到登陆态, 请重试', -1);
        }

        $sessionData = redis_cache($this->payload['sessionId']);

        if (empty($sessionData)) {
            error_stop('登陆态已过期, 请重试', -1);
        }

        $wechatUser = [
            'openid' => $sessionData['openid'],
            'unionid' => $sessionData['unionid'] ?? '',
            'avatar' => '',
            'nickname' => '',
        ];
        return $wechatUser;
    }

    // 解密微信小程序手机号
    public function getUserPhoneNumber()
    {
        if (empty($this->payload['sessionId'])) {
            error_stop('未获取到登陆态, 请重试', -1);
        }

        $sessionData = redis_cache($this->payload['sessionId']);

        if (empty($sessionData)) {
            error_stop('登陆态已过期, 请重试', -1);
        }

        $phoneInfo = $this->wechat->encryptor->decryptData($sessionData['session_key'], $this->payload['iv'], $this->payload['encryptedData']);

        if (empty($phoneInfo['purePhoneNumber'])) {
            error_stop('获取失败,请重试');
        }

        if ($phoneInfo['countryCode'] !== '86') {
            error_stop('仅支持大陆地区手机号');
        }
        return $phoneInfo['purePhoneNumber'];
    }

    /**
     * 获取session_id, 缓存 session_key, openid (unionid), 自动登录
     *
     * @return string
     */
    public function getSessionId()
    {
        if (empty($this->payload['code'])) {
            error_stop('缺少code参数');
        }
        
        $decryptData = $this->wechat->auth->session($this->payload['code']);

        if(!empty($decryptData['errmsg'])) {
            error_stop($decryptData['errmsg']);
        }

        if (empty($decryptData['session_key'])) {
            error_stop('未获取到登陆态, 请重试', -1);
        }
        
        $auto_login = $this->payload['auto_login'] ?? false;

        // 自动登录流程
        if($auto_login) {
            $oauthInfo = ThirdOauth::getByOpenid($decryptData['openid']);
            if($oauthInfo && $oauthInfo->user_id) {
                $auth = Auth::instance();
                $ret = $auth->direct($oauthInfo->user_id);
                if ($ret) {
                    set_token_in_header($auth->getToken());
                }
            }
        }
     
        $session_id = Random::uuid();
        redis_cache($session_id, $decryptData, 60 * 60 * 24 * 7);   // session_key缓存保留一周
        return ['session_id' => $session_id, 'auto_login' => $auto_login];
    }
}
