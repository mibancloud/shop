<?php

namespace addons\shopro\service\user;

use fast\Random;
use app\common\library\Auth;
use app\admin\model\shopro\user\User as UserModel;

class UserAuth
{

    /**
     * 认证用户
     *
     * @var object|null
     */
    protected $auth = null;

    public function __construct()
    {
        $this->auth = Auth::instance();
    }

    /**
     * 用户注册
     *
     * @param array $params 注册信息
     * @param array $params 至少包含 mobile 和 email 中的一个
     * @return object|array
     */
    public function register($params)
    {
        $verification = [];

        if(!empty($params['username'])) {
            $username =  $params['username'];
            $verification['username'] = 1;
        }else {
            $username = Random::alnum(8);
        }
        if(!empty($params['mobile'])) {
            $mobile =  $params['mobile'];
            $verification['mobile'] = 1;
        }else {
            $mobile = '';
        }
        if(!empty($params['email'])) {
            $email =  $params['email'];
            $verification['email'] = 1;
        }else {
            $email = '';
        }
        if(!empty($params['password'])) {
            $password =  $params['password'];
            $verification['password'] = 1;
        }else {
            $password = Random::alnum(8);
        }

        if ($username || $mobile || $email) {
            $user = UserModel::where(function ($query) use ($mobile, $email, $username) {
                if ($mobile) {
                    $query->whereOr('mobile', $mobile);
                }
                if ($email) {
                    $query->whereOr('email', $email);
                }
                if ($username) {
                    $query->whereOr('username', $username);
                }
            })->find();

            if ($user) {
                error_stop('账号已注册，请直接登录');
            }
        }

        $userDefaultConfig = $this->getUserDefaultConfig();

        $extend = [
            'avatar' => !empty($params['avatar']) ? $params['avatar'] : $userDefaultConfig['avatar'],
            'nickname' => !empty($params['nickname']) ? $params['nickname'] : $userDefaultConfig['nickname'] . $username,
            'group_id' => $userDefaultConfig['group_id'] ?? 1
        ];

        $ret = $this->auth->register($username, $password, $email, $mobile, $extend);
        if ($ret) {
            $user = $this->auth->getUser();

            $user->verification = $verification;
            $user->save();

            $hookData = ['user' => $user];
            \think\Hook::listen('user_register_after', $hookData);

            return $this->auth;
        } else {
            error_stop($this->auth->getError());
        }
    }

    /**
     * 重置密码
     *
     * @param array $params 至少包含 mobile 和 email 中的一个
     * @return boolean
     */
    public function resetPassword($params)
    {
        $mobile = $params['mobile'] ?? null;
        $email = $params['email'] ?? null;
        $password = $params['password'] ?? null;

        if (!$params['mobile'] && !$params['email']) {
            error_stop('参数错误');
        }

        $user = UserModel::where(function ($query) use ($mobile, $email) {
            if ($mobile) {
                $query->whereOr('mobile', $mobile);
            }
            if ($email) {
                $query->whereOr('email', $email);
            }
        })->find();
        if (!$user) {
            error_stop(__('User not found'));
        }

        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($password, '', true);

        if(!$ret) {
            error_stop($this->auth->getError());
        }

        if ($ret) {
            $user = $this->auth->getUser();

            $verification = $user->verification;
            $verification->password = 1;
            $user->verification = $verification;
            $user->save();
        }

        return $ret;
    }

    /**
     * 修改密码
     *
     * @param string $old_password
     * @param string $password
     * @return boolean
     */
    public function changePassword($new_password, $old_password)
    {
        $ret = $this->auth->changepwd($new_password, $old_password);
        if(!$ret) {
            error_stop($this->auth->getError());
        }

        return $ret;
    }

    /**
     * 修改手机号
     * @param array $params
     * @return bool
     */
    public function changeMobile($params)
    {
        $user = auth_user();

        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $params['mobile'];
        $user->save();

        return true;
    }

    /**
     * 修改用户名
     * @param array $params
     * @return bool
     */
    public function changeUsername($params)
    {
        $user = auth_user();

        $verification = $user->verification;
        $verification->username = 1;
        $user->verification = $verification;
        $user->username = $params['username'];
        $user->save();

        return true;
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $this->auth->logout();
    }

    /**
     * 注销用户
     */
    public function logoff()
    {
        $user = auth_user();
        $user = UserModel::get($user->id);

        $user->delete();

        $this->logout();
    }

    /**
     * 获取用户默认值配置
     *
     * @return object|array
     */
    private function getUserDefaultConfig()
    {
        $config = sheep_config('shop.user');
        return $config;
    }
}
