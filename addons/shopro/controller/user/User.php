<?php

namespace addons\shopro\controller\user;

use app\common\library\Sms;
use addons\shopro\controller\Common;
use addons\shopro\service\user\UserAuth;
use app\admin\model\shopro\user\User as UserModel;
use app\admin\model\shopro\user\Coupon as UserCouponModel;
use app\admin\model\shopro\order\Order as OrderModel;
use app\admin\model\shopro\order\Aftersale as AftersaleModel;
use app\admin\model\shopro\ThirdOauth;

class User extends Common
{

    protected $noNeedLogin = ['smsRegister', 'accountLogin', 'smsLogin', 'resetPassword'];
    protected $noNeedRight = ['*'];


    public function _initialize()
    {
        parent::_initialize();
        \think\Lang::load(APP_PATH . 'api/lang/zh-cn/user.php');        // 加载语言包
    }

    /**
     * 用户数据
     */
    public function data()
    {
        $user = auth_user();
        // 查询用户优惠券数量
        $data['coupons_num'] = UserCouponModel::geted()->where('user_id', $user->id)->count();

        // 订单数量
        $orderNum = [];
        $orderNum['unpaid'] = OrderModel::where('user_id', $user->id)->unpaid()->count();
        $orderNum['nosend'] = OrderModel::where('user_id', $user->id)->pretendPaid()->nosend()->count();
        $orderNum['noget'] = OrderModel::where('user_id', $user->id)->pretendPaid()->noget()->count();
        $orderNum['nocomment'] = OrderModel::where('user_id', $user->id)->paid()->nocomment()->count();
        $orderNum['aftersale'] = AftersaleModel::where('user_id', $user->id)->needOper()->count();

        $data['order_num'] = $orderNum;

        $this->success('用户数据', $data);
    }

    /**
     * 第三方授权信息
     */
    public function thirdOauth()
    {
        $user = auth_user();

        $provider = $this->request->param('provider', '');
        $platform = $this->request->param('platform', '');
        if (!in_array($platform, ['miniProgram', 'officialAccount', 'openPlatform'])) {
            $this->error(__('Invalid parameters'));
        }
        $where = [
            'platform' => $platform,
            'user_id' => $user->id
        ];
        if ($provider !== '') {
            $where['provider'] = $provider;
        }
        $oauth = ThirdOauth::where($where)->field('nickname, avatar, platform, provider')->find();
        $this->success('', $oauth);
    }


    /**
     * 用户信息
     */
    public function profile()
    {
        //TODO @ldh: 1.账号被禁用 2.连表查group
        $user = auth_user(true);

        $user = UserModel::with(['parent_user', 'third_oauth'])->where('id', $user->id)->find();

        $user->hidden(['password', 'salt', 'createtime', 'updatetime', 'deletetime', 'remember_token', 'login_fail', 'login_ip', 'login_time']);

        $this->success('个人详情', $user);
    }

    /**
     * 更新用户资料
     */
    public function update()
    {
        $user = auth_user();

        $params = $this->request->only(['avatar', 'nickname', 'gender']);
        $this->svalidate($params);

        $user->save($params);
        $user->hidden(['password', 'salt', 'createtime', 'updatetime', 'deletetime', 'remember_token', 'login_fail', 'login_ip', 'login_time']);

        $this->success('更新成功', $user);
    }

    /**
     * 账号密码登录
     */
    public function accountLogin()
    {
        $user = auth_user();

        if ($user) {
            $this->error('您已登录,不需要重新登录');
        }

        $params = $this->request->only(['account', 'password']);
        $this->svalidate($params, '.accountLogin');

        $ret = $this->auth->login($params['account'], $params['password']);
        if ($ret) {
            set_token_in_header($this->auth->getToken());
            $this->success(__('Logged in successful'));
        } else {
            $this->error($this->auth->getError() ?: '注册失败');
        }
    }

    /**
     * 短信验证码登陆
     */
    public function smsLogin()
    {
        $user = auth_user();

        if ($user) {
            $this->error('您已登录,不需要重新登录');
        }

        $params = $this->request->only(['mobile', 'code']);
        $this->svalidate($params, '.smsLogin');
        if (!Sms::check($params['mobile'], $params['code'], 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = UserModel::getByMobile($params['mobile']);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        }else {
            $this->error('该手机号暂未注册');
        }
        if (isset($ret) && $ret) {
            Sms::flush($params['mobile'], 'mobilelogin');
            set_token_in_header($this->auth->getToken());
            $this->success(__('Logged in successful'));
        } else {
            $this->error($this->auth->getError() ?: '登录失败');
        }
    }

    /**
     * 短信验证码注册
     */
    public function smsRegister()
    {
        $user = auth_user();
        if ($user) {
            $this->error('您已登录,请先退出登录');
        }

        $params = $this->request->only(['mobile', 'code', 'password']);
        $this->svalidate($params, '.smsRegister');

        $ret = Sms::check($params['mobile'], $params['code'], 'register');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }

        // 注册
        $userAuth = new UserAuth();
        $auth = $userAuth->register($params);
        set_token_in_header($auth->getToken());

        $this->success(__('Sign up successful'));
    }

    /**
     * 修改密码
     */
    public function changePassword()
    {
        $user = auth_user();

        $params = $this->request->only(['oldPassword', 'newPassword']);
        $this->svalidate($params, '.changePassword');

        $userAuth = new UserAuth();
        $userAuth->changePassword($params['newPassword'], $params['oldPassword']);

        $this->auth->direct($user->id);
        set_token_in_header($this->auth->getToken());

        $this->success(__('Change password successful'));
    }

    /**
     * 重置/忘记密码
     */
    public function resetPassword()
    {
        $params = $this->request->only(['mobile', 'code', 'password']);
        $this->svalidate($params, '.resetPassword');

        $ret = Sms::check($params['mobile'], $params['code'], 'resetpwd');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }

        $userAuth = new UserAuth();
        $userAuth->resetPassword($params);

        $this->success(__('Reset password successful'));
    }

    /**
     * 更换手机号
     */
    public function changeMobile()
    {
        $params = $this->request->only(['mobile', 'code']);
        $this->svalidate($params, '.changeMobile');

        $ret = Sms::check($params['mobile'], $params['code'], 'changemobile');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }

        $userAuth = new UserAuth();
        $userAuth->changeMobile($params);

        $this->success('绑定成功');
    }

    /**
     * 修改用户名
     */
    public function changeUsername()
    {
        $user = auth_user(true);

        $params = $this->request->only(['username']);
        $this->svalidate($params, '.changeUsername');

        $userAuth = new UserAuth();
        $userAuth->changeUsername($params);

        $this->success('绑定成功');
    }

    /**
     * 更新小程序头像和昵称
     */
    public function updateMpUserInfo()
    {
        $user = auth_user(true);

        $params = $this->request->only(['avatar', 'nickname']);
        $this->svalidate($params, '.updateMpUserInfo');

        $user->save($params);

        $thirdOauth = \app\admin\model\shopro\ThirdOauth::where('user_id', $user->id)->where([
            'provider' => 'wechat',
            'platform' => 'miniProgram'
        ])->find();
        $thirdOauth->save($params);
        $this->success('绑定成功');
    }



    /**
     * 登出
     */
    public function logout()
    {
        $userAuth = new UserAuth();
        $userAuth->logout();

        $this->success(__('Logout successful'));
    }


    /**
     * 用户注销
     */
    public function logoff()
    {
        $userAuth = new UserAuth();
        $userAuth->logoff();

        $this->success('注销成功');
    }
}
