<?php

namespace addons\shopro\service\third\apple;

use app\common\library\Auth;
use addons\shopro\service\user\UserAuth;
use app\admin\model\shopro\ThirdOauth;

class Apple
{

    public function __construct()
    {
    }
    /**
     * AppleId登陆
     *
     * @return array
     */
    public function login($payload)
    {
        $identityToken = $payload['identityToken'];
        $openId = $payload['openId'];
        $appleSignInPayload = \AppleSignIn\ASDecoder::getAppleSignInPayload($identityToken);
        $isValid = $appleSignInPayload->verifyUser($openId);
        if (!$isValid) return false;

        $nickname = '';
        if (!empty($payload['fullName'])) {
            $hasFamilyName = !empty($payload['fullName']['familyName']);
            $nickname = ($hasFamilyName ? $payload['fullName']['familyName'] : '') . ($hasFamilyName ? ' ' : '') . $payload['fullName']['giveName'];
        }
        $appleUser = [
            'openid' => $openId,
            'nickname' => $nickname,
            'avatar' => ''

        ];


        $oauthInfo = $this->createOrUpdateOauthInfo($appleUser);

        if (!$oauthInfo->user_id) {
            $this->registerOrBindUser($oauthInfo);
        }

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
     * 创建/更新用户认证信息
     *
     * @return think\Model
     */
    private function createOrUpdateOauthInfo($appleUser)
    {
        $oauthInfo = ThirdOauth::where([
            'openid' => $appleUser['openid'],
            'provider' => 'apple',
            'platform' => 'App'
        ])->find();
        if (!$oauthInfo) {   // 创建新的third_oauth条目
            $appleUser['provider'] = 'apple';
            $appleUser['platform'] = 'App';
            ThirdOauth::create($appleUser);
            $oauthInfo = ThirdOauth::where([
                'openid' => $appleUser['openid'],
                'provider' => 'apple',
                'platform' => 'App'
            ])->find();
        }
        if ($oauthInfo) {  // 更新授权信息
            $oauthInfo->save($appleUser);
        }
        return $oauthInfo;
    }

    /**
     * 注册/绑定用户
     *
     * @return think\Model
     */
    private function registerOrBindUser($oauthInfo, $user_id = 0)
    {
        if ($oauthInfo->user_id) {
            error_stop('该账号已绑定其他用户');
        }
        if ($user_id === 0) {   // 注册新用户
            $user = (new UserAuth())->register([
                'nickname' => $oauthInfo->nickname,
                'avatar' => $oauthInfo->avatar,
            ]);
            $user_id = $user->id;
        }
        $oauthInfo->user_id = $user_id;
        return $oauthInfo->save();
    }
}
