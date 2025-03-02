<?php

namespace addons\shopro\facade;

use think\Cache;

class Wechat extends Base
{

    public static function getFacadeClass() 
    {
        return self::officialAccount();         // 默认是公众号
    }


    /**
     * 公众号
     *
     * @return \EasyWeChat\OfficialAccount\Application
     */
    public static function officialAccount()
    {
        if (isset($GLOBALS['WECHAT']['OFFICIALACCOUNT'])) {
            return $GLOBALS['WECHAT']['OFFICIALACCOUNT'];
        }

        $defaultConfig = self::defaultConfig();
        $officialAccount = sheep_config('shop.platform.WechatOfficialAccount', false);
        $config = array_merge($defaultConfig, [
            'app_id'  => $officialAccount['app_id'],
            'secret'  => $officialAccount['secret'],
        ]);
        $app = new \EasyWeChat\OfficialAccount\Application($config);
        $GLOBALS['WECHAT']['OFFICIALACCOUNT'] = $app;

        return $GLOBALS['WECHAT']['OFFICIALACCOUNT'];
    }


    /**
     * 公众号管理
     *
     * @return \EasyWeChat\OfficialAccount\Application
     */
    public static function officialAccountManage()
    {
        if (isset($GLOBALS['WECHAT']['OFFICIALACCOUNT_MANAGE'])) {
            return $GLOBALS['WECHAT']['OFFICIALACCOUNT_MANAGE'];
        }

        $defaultConfig = self::defaultConfig();
        $officialAccount = sheep_config('wechat.officialAccount', false);
        $config = array_merge($defaultConfig, [
            'app_id'  => $officialAccount['app_id'],
            'secret'  => $officialAccount['secret'],
            'token'  => $officialAccount['token'],
            'aes_key'  => $officialAccount['aes_key'],
        ]);
        $app = new \EasyWeChat\OfficialAccount\Application($config);
        $GLOBALS['WECHAT']['OFFICIALACCOUNT_MANAGE'] = $app;

        return $GLOBALS['WECHAT']['OFFICIALACCOUNT_MANAGE'];
    }



    /**
     * 小程序
     *
     * @return \EasyWeChat\MiniProgram\Application
     */
    public static function miniProgram()
    {
        if (isset($GLOBALS['WECHAT']['MINIPROGRAM'])) {
            return $GLOBALS['WECHAT']['MINIPROGRAM'];
        }

        $defaultConfig = self::defaultConfig();
        $miniProgram = sheep_config('shop.platform.WechatMiniProgram', false);
        $config = array_merge($defaultConfig, [
            'app_id'  => $miniProgram['app_id'],
            'secret'  => $miniProgram['secret'],
        ]);
        $app = new \EasyWeChat\MiniProgram\Application($config);
        $GLOBALS['WECHAT']['MINIPROGRAM'] = $app;

        return $GLOBALS['WECHAT']['MINIPROGRAM'];
    }



    /**
     * 小程序
     *
     * @return \EasyWeChat\OpenPlatform\Application
     */
    public static function openPlatform()
    {
        if (isset($GLOBALS['WECHAT']['OPENPLATFORM'])) {
            return $GLOBALS['WECHAT']['OPENPLATFORM'];
        }

        $defaultConfig = self::defaultConfig();
        $openPlatform = sheep_config('shop.platform.App', false);
        $config = array_merge($defaultConfig, [
            'app_id'  => $openPlatform['app_id'],
            'secret'  => $openPlatform['secret'],
        ]);
        $app = new \EasyWeChat\OpenPlatform\Application($config);
        $GLOBALS['WECHAT']['OPENPLATFORM'] = $app;

        return $GLOBALS['WECHAT']['OPENPLATFORM'];
    }


    protected static function defaultConfig () {
        return [
            'response_type'     => 'array',
            // 日志配置 level: 日志级别, 可选为：debug/info/notice/warning/error/critical/alert/emergency path：日志文件位置(绝对路径!!!)，要求可写权限
            'log' => [
                'default' => config('app_debug') ? 'dev' : 'prod', // 默认使用的 channel，生产环境可以改为下面的 prod
                'channels' => [
                    // 测试环境
                    'dev' => [
                        'driver' => 'single',
                        'path' => RUNTIME_PATH . 'log/wechat/easywechat-dev.log',
                        'level' => 'debug',
                    ],
                    // 生产环境
                    'prod' => [
                        'driver' => 'daily',
                        'path' => RUNTIME_PATH . 'log/wechat/easywechat-prod.log',
                        'level' => 'info',
                    ]
                ]
            ],
            'http' => [
                'connect_timeout' => 5,
                'max_retries' => 1,
                'retry_delay' => 500,
                'timeout' => 5,
                'verify' => ROOT_PATH . 'addons/shopro/library/cacert.pem',
                // 'base_uri' => 'https://api.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
            ],
        ];
    }



}
