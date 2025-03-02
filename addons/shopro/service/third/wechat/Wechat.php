<?php

namespace addons\shopro\service\third\wechat;

use app\common\library\Auth;
use addons\shopro\service\user\UserAuth;
use app\admin\model\shopro\ThirdOauth;
use app\admin\model\shopro\user\User as UserModel;

class Wechat
{

    // 平台
    protected $platform;
    // 转发服务
    protected $service;

    public function __construct($platform, $payload = [])
    {
        $this->platform = $platform;
        $this->service = $this->setPlatformService($payload);
    }

    public function getApp()
    {
        return $this->service->wechat;
    }

    /**
     * 微信登陆
     *
     * @return array
     */
    public function login()
    {
        $wechatUser = $this->service->login();

        $oauthInfo = $this->createOrUpdateOauthInfo($wechatUser);

        $this->registerOrBindUser($oauthInfo, 0, ['mobile' => $wechatUser['mobile'] ?? '']);

        $oauthInfo->save();

        $auth = Auth::instance();
        $ret = $auth->direct($oauthInfo->user_id);

        if ($ret) {
            $oauthInfo->login_num += 1;
            set_token_in_header($auth->getToken());
            return true;
        } else {
            $oauthInfo->user_id = 0;
            $oauthInfo->save();
            return false;
        }
    }

    /**
     * 微信绑定
     *
     * @return array
     */
    public function bind(Object $user)
    {
        $wechatUser = $this->service->bind();
        $oauthInfo = $this->createOrUpdateOauthInfo($wechatUser);
        if ($this->registerOrBindUser($oauthInfo, $user->id)) {
            return true;
        }
        return false;
    }

    /**
     * 微信解绑
     *
     * @return array
     */
    public function unbind()
    {
        $user = auth_user();
        if (!$user->verification->mobile) {
            error_stop('请先绑定手机号后再进行操作');
        }
        $oauthInfo = ThirdOauth::where([
            'user_id' => $user->id,
            'platform' => $this->platform,
            'provider' => 'wechat'
        ])->find();

        if ($oauthInfo) {
            $oauthInfo->delete();
            return true;
        }
        return false;
    }

    /**
     * 创建/更新用户认证信息
     *
     * @return think\Model
     */
    private function createOrUpdateOauthInfo($wechatUser, $extend = [])
    {
        $oauthInfo = ThirdOauth::getByOpenid($wechatUser['openid']);

        if (!$oauthInfo) {   // 创建新的third_oauth条目
            $wechatUser['user_id'] = 0;
            if (!empty($wechatUser['unionid'])) {     // unionid账号合并策略
                $unionOauthInfo = ThirdOauth::getByUnionid($wechatUser['unionid']);
                if ($unionOauthInfo) {
                    $wechatUser['user_id'] = $unionOauthInfo->user_id;
                }
            }

            $wechatUser['provider'] = 'wechat';
            $wechatUser['platform'] = $this->platform;
            $wechatUser = array_merge($wechatUser, $extend);
            $thirdOauth = new ThirdOauth($wechatUser);
            $thirdOauth->allowField(true)->save();
            $oauthInfo = ThirdOauth::getByOpenid($wechatUser['openid']);
        }else {
            $oauthInfo->allowField(true)->save($wechatUser);
        }

        return $oauthInfo;
    }

    /**
     * 注册/绑定用户
     *
     * @return think\Model
     */
    private function registerOrBindUser($oauthInfo, $user_id = 0, $extend = [])
    {

        // 检查用户存在
        if ($oauthInfo->user_id) {
            $user = UserModel::get($oauthInfo->user_id);
            if ($user && $user_id > 0) {
                error_stop('该微信账号已绑定其他用户');
            }
            // 用户被删除,则重置第三方信息所绑定的用户id
            if (!$user) {
                $oauthInfo->user_id = 0;
            }
        }
        
        // 绑定用户
        if ($oauthInfo->user_id === 0 && $user_id > 0) {
            $user = UserModel::get($user_id);
            if ($user) {
                $oauthInfo->user_id = $user_id;
                return $oauthInfo->save();
            } else {
                error_stop('该用户暂不可绑定微信账号');
            }
            return false;
        }

        // 注册新用户
        if ($oauthInfo->user_id === 0 && $user_id === 0) {
            $auth = (new UserAuth())->register([
                'nickname' => $oauthInfo->nickname ?? '',
                'avatar' => $oauthInfo->avatar ?? '',
                'mobile' => $extend['mobile'] ?? ''
            ]);
            $user = $auth->getUser();
            $oauthInfo->user_id = $user->id;
            return $oauthInfo->save();
        }
    }

    /**
     * 设置平台服务类
     *
     * @return class
     */
    private function setPlatformService($payload)
    {
        switch ($this->platform) {
            case 'officialAccount':
                $service = new OfficialAccount($payload);
                break;
            case 'miniProgram':
                $service = new MiniProgram($payload);
                break;
            case 'openPlatform':
                $service = new OpenPlatform($payload);
                break;
        }
        if (!isset($service)) error_stop('平台参数有误');
        return $service;
    }

    /**
     * 方法转发到驱动提供者
     *
     * @param string $funcname
     * @param array $arguments
     * @return void
     */
    public function __call($funcname, $arguments)
    {
        return $this->service->{$funcname}(...$arguments);
    }
}
