<?php

namespace addons\shopro\library\easywechatPlus;


/**
 * 补充 easywechat
 */
class EasywechatPlus
{

    protected $app = null;

    public function __construct($app)
    {
        $this->app = $app;        
    }


    // 返回实例
    public function getApp()
    {
        return $this->app;
    }


    //获取accessToken
    protected function getAccessToken()
    {
        $accessToken = $this->app->access_token;
        $token = $accessToken->getToken(); // token 数组  token['access_token'] 字符串
        //$token = $accessToken->getToken(true); // 强制重新从微信服务器获取 token.
        return $token;
    }
}
