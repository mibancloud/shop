<?php

namespace addons\shopro\service\third\wechat;

use addons\shopro\facade\Wechat;

class OfficialAccount
{
    public $wechat;
    protected $request;
    protected $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
        $this->request = request();
        $this->wechat = Wechat::officialAccount();
    }

    public function login()
    {
        $code = $this->request->get('code');
        if (empty($code)) {
            error_stop('缺少code参数');
        }
        $decryptData = $this->wechat->oauth->user()->getOriginal();
        
        $wechatUser = [
            'openid' => $decryptData['openid'],
            'unionid' => $decryptData['unionid'] ?? '',
            'avatar' => $decryptData['headimgurl'],
            'nickname' => $decryptData['nickname'],
        ];
        return $wechatUser;
    }

    public function bind()
    {
        return $this->login();
    }

    /**
     * 获取网页登录地址redirect+返回code
     *
     * @return string
     */
    public function oauthLogin()
    {
        // 返回前端
        if (!empty($this->request->param('code'))) {
            if($this->payload['event'] === 'bind') {
                $query['bind_code'] = $this->request->param('code');
            }else {
                $query['login_code'] = $this->request->param('code');
            }
            
            return [
                'redirect_url' => $this->payload['page'] . '?' .  http_build_query($query)
            ];
        } else {
            $query = [
                'platform' => 'officialAccount',
                'payload' => urlencode(json_encode($this->payload))
            ];
            $loginUrl = $this->request->domain() . '/addons/shopro/third.wechat/oauthLogin?' . http_build_query($query);
            return [
                'login_url' => $this->wechat->oauth->scopes(['snsapi_userinfo'])->redirect($loginUrl)->getTargetUrl()
            ];
        }
    }

    public function jssdk($APIs)
    {
        $this->wechat->jssdk->setUrl($this->payload['url']);
        return $this->wechat->jssdk->buildConfig($APIs, false, false, false);
    }
}
