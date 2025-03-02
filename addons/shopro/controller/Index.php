<?php

namespace addons\shopro\controller;

use addons\shopro\controller\traits\Util;
use addons\shopro\library\easywechatPlus\WechatMiniProgramShop;
use app\admin\model\shopro\decorate\Decorate;
use app\admin\model\shopro\decorate\Page;
use app\common\library\Sms as Smslib;
use app\admin\model\shopro\user\User as UserModel;
use addons\shopro\facade\Wechat;
use think\Hook;

class Index extends Common
{
    use Util;

    protected $noNeedLogin = ['init', 'pageSync', 'page', 'feedback', 'send', 'test'];
    protected $noNeedRight = ['*'];

    public function init()
    {
        $platform = $this->request->header('platform');
        $templateId = $this->request->param('templateId', 0);
        $platformConfig = sheep_config("shop.platform.$platform");

        if (empty($platformConfig['status']) || !$platformConfig['status']) {
            $this->error('暂不支持该平台,请前往商城配置启用对应平台');
        }

        $template = Decorate::template()->whereRaw("find_in_set('$platform', platform)");

        if ($templateId) {
            $template->where('id', $templateId);
        } else {
            $template->where('status', 'enable');
        }
        $template = $template->find();
        if ($template) {
            $template = Page::where('decorate_id', $template->id)->select();
            $template = collection($template)->column('page', 'type');
        }

        $shopConfig = sheep_config('shop.basic');

        // 客服配置
        $chatSystem = sheep_config('chat.system');
        // 客服应用配置
        $chatConfig = sheep_config('chat.application.shop');
        // 初始化 socket ssl 类型, 默认 none
        $ssl = $chatSystem['ssl'] ?? 'none';
        $chat_domain = ($ssl == 'none' ? 'http://' : 'https://') . request()->host(true) . ($ssl == 'reverse_proxy' ? '' : (':' . $chatSystem['port'])) . '/chat';
        $chatConfig['chat_domain'] = $chat_domain;

        $data = [
            'app' => [
                'name' => $shopConfig['name'],
                'logo' => $shopConfig['logo'],
                'cdnurl' => cdnurl('', true),
                'version' => $shopConfig['version'],
                'user_protocol' => $shopConfig['user_protocol'],
                'privacy_protocol' =>  $shopConfig['privacy_protocol'],
                'about_us' => $shopConfig['about_us'],
                'copyright' => $shopConfig['copyright'],
                'copytime' => $shopConfig['copytime'],
            ],
            'platform' => [
                'auto_login' => $platformConfig['auto_login'] ?? 0,
                'bind_mobile' => $platformConfig['bind_mobile'] ?? 0,
                'payment' => $platformConfig['payment']['methods'],
                'recharge_payment' => sheep_config('shop.recharge_withdraw.recharge.methods'),      // 充值支持的支付方式
                'share' => $platformConfig['share'],
            ],
            'template' => $template,
            'chat' => $chatConfig
        ];

        if ($platform == 'WechatMiniProgram') {
            $uploadshoppingInfo = new WechatMiniProgramShop(Wechat::miniProgram());
            $data['has_wechat_trade_managed'] = intval($uploadshoppingInfo->isTradeManaged());
        }

        $this->success('初始化', $data);
    }

    public function pageSync()
    {
        $pages = $this->request->post('pages/a');
        foreach ($pages as $page) {
            if (!empty($page['meta']['sync']) && $page['meta']['sync']) {
                $data = \app\admin\model\shopro\data\Page::getByPath($page['path']);
                $name = $page['meta']['title'] ?? '未命名';
                $group = $page['meta']['group'] ?? '其它';
                if ($data) {
                    $data->name = $name;
                    $data->group = $group;
                    $data->save();
                } else {
                    \app\admin\model\shopro\data\Page::create([
                        'name' => $name,
                        'group' => $group,
                        'path' => $page['path']
                    ]);
                }
            }
        }
        $this->success();
    }

    public function page()
    {
        $id = $this->request->param('id');

        $template = \app\admin\model\shopro\decorate\Decorate::typeDiypage()->with('diypage')->where('id', $id)->find();
        if (!$template) {
            $this->error(__('No Results were found'));
        }

        $this->success('', $template);
    }

    public function test()
    {
    }

    public function feedback()
    {
        $user = auth_user();
        $params = $this->request->only(['type', 'content', 'images', 'phone']);
        if ($user) {
            $params['user_id'] = $user->id;
        }
        $result = \app\admin\model\shopro\Feedback::create($params);
        if ($result) {
            $this->success('感谢您的反馈');
        }
    }



    /**
     * 发送验证码
     *
     * @param string $mobile 手机号
     * @param string $event 事件名称
     */
    public function send()
    {
        $mobile = $this->request->post("mobile");
        $event = $this->request->post("event");
        $event = $event ? strtolower($event) : 'register';

        if (!$mobile || !\think\Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('手机号不正确'));
        }

        $last = Smslib::get($mobile, $event);
        if ($last && time() - $last['createtime'] < 60) {
            $this->error(__('发送频繁'));
        }
        $ipSendTotal = \app\common\model\Sms::where(['ip' => $this->request->ip()])->whereTime('createtime', '-1 hours')->count();
        if ($ipSendTotal >= 5) {
            $this->error(__('发送频繁'));
        }
        if ($event) {
            $userinfo = UserModel::getByMobile($mobile);
            if ($event == 'register' && $userinfo) {
                //已被注册
                $this->error(__('手机号已经被注册'));
            } elseif (in_array($event, ['changemobile']) && $userinfo) {
                //被占用
                $this->error(__('手机号已经被占用'));
            } elseif (in_array($event, ['changepwd', 'resetpwd', 'mobilelogin']) && !$userinfo) {
                //未注册
                $this->error(__('手机号未注册'));
            }
        }
        if (!Hook::get('sms_send')) {
            $this->error(__('请在后台插件管理安装短信验证插件'));
        }
        $ret = Smslib::send($mobile, null, $event);
        if ($ret) {
            $this->success(__('发送成功'));
        } else {
            $this->error(__('发送失败，请检查短信配置是否正确'));
        }
    }


    /**
     * 获取统一验证 token
     *
     * @return void
     */
    public function unifiedToken()
    {
        $user = auth_user();

        $token = $this->getUnifiedToken('user:' . $user->id);

        $this->success('获取成功', [
            'token' => $token
        ]);
    }
}
