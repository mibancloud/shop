<?php

namespace addons\shopro\controller\third;

use think\Db;
use think\exception\HttpResponseException;
use addons\shopro\controller\Common;
use addons\shopro\service\third\wechat\Wechat as WechatService;
use app\admin\model\shopro\notification\Config as NotificationConfig;

class Wechat extends Common
{
    protected $noNeedLogin = ['login', 'getSessionId', 'oauthLogin', 'jssdk', 'wxacode', 'subscribeTemplate'];
    protected $noNeedRight = ['*'];
    protected $payload = [];
    protected $wechat;
    protected $platform;
    
    public function _initialize()
    {
        parent::_initialize();

        $this->platform = $this->request->param('platform', '');

        if ($this->platform === '') {
            $this->error('参数错误');
        }

        $payloadString = htmlspecialchars_decode($this->request->param('payload', ''));

        $this->payload = json_decode(urldecode($payloadString), true) ?? [];

        $this->wechat = new WechatService($this->platform, $this->payload);
    }

    // 微信登陆（小程序+公众号+开放平台）
    public function login()
    {
        $result = Db::transaction(function () {
            return $this->wechat->login();
        });

        if ($result) {
            $this->success('登陆成功');
        }
        $this->error('登陆失败');
    }

    // 获取小程序sessionId+自动登录
    public function getSessionId()
    {
        $result = $this->wechat->getSessionId();
        $this->success('', $result);
    }

    // 获取网页授权地址
    public function oauthLogin()
    {
        $result = $this->wechat->oauthLogin();
        if (isset($result['login_url'])) {
            $this->success('', $result);
        }

        if (isset($result['redirect_url'])) {
            return redirect($result['redirect_url']);
        }
    }

    // 绑定用户手机号
    public function bindUserPhoneNumber()
    {
        $result = Db::transaction(function () {
            $user = auth_user();
            $mobile = $this->wechat->getUserPhoneNumber();

            $this->svalidate(['mobile' => $mobile], '.bindWechatMiniProgramMobile');

            $user->mobile = $mobile;
            $verification = $user->verification;
            $verification->mobile = 1;
            $user->verification = $verification;

            return $user->save();
        });
        if ($result) {

            $this->success('绑定成功');
        }
        $this->error('操作失败');
    }

    // 绑定微信账号
    public function bind()
    {
        $result = Db::transaction(function () {
            $user = auth_user();
            return $this->wechat->bind($user);
        });

        if ($result) {
            $this->success('绑定成功');
        }
        $this->error('绑定失败');
    }

    // 解绑微信账号
    public function unbind()
    {
        $result = Db::transaction(function () {
            return $this->wechat->unbind();
        });

        if ($result) {
            $this->success('解绑成功');
        }
        $this->error('解绑失败');
    }

    // 微信网页jssdk
    public function jssdk()
    {
        $apis = [
            'checkJsApi',
            'updateTimelineShareData',
            'updateAppMessageShareData',
            'getLocation', //获取位置
            'openLocation', //打开位置
            'scanQRCode', //扫一扫接口
            'chooseWXPay', //微信支付
            'chooseImage', //拍照或从手机相册中选图接口
            'previewImage', //预览图片接口       'uploadImage', //上传图片
            'openAddress',   // 获取微信地址
        ];
        // $openTagList = [
        //     'wx-open-subscribe'
        // ];
        try {
            $data = $this->wechat->jssdk($apis);
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            $this->error($message);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success('jssdkApi', $data);
    }


    /**
     * 微信小程序码接口
     */
    public function wxacode()
    {
        $mp = $this->wechat->getApp();
        $path = $this->payload['path'];
        list($page, $scene) = explode('?', $path);

        $content = $mp->app_code->getUnlimit($scene, [
            'page' => substr($page, 1),
            'is_hyaline' => true,
            // 'env_version' => 'develop'
            'env_version' => 'release'
        ]);

        if ($content instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            return response($content->getBody(), 200, ['Content-Length' => strlen($content->getBodyContents())])->contentType('image/png');
        } else {
            // 小程序码获取失败
            $msg = $content['errcode'] ?? '-';
            $msg .= $content['errmsg'] ?? '';

            $this->error($msg);
        }
    }

    /**
     * 微信小程序订阅模板消息
     */
    public function subscribeTemplate()
    {
        $templates = [];
        // 获取订阅消息模板
        $notificationConfig = NotificationConfig::where('channel', 'WechatMiniProgram')->enable()->select();

        foreach ($notificationConfig as $k => $config) {
            if ($config['content'] && isset($config['content']['template_id']) && $config['content']['template_id']) {
                $templates[$config['event']] = $config['content']['template_id'];
            }
        }

        $this->success('获取成功', $templates);
    }
}
