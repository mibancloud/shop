<?php

namespace app\admin\controller\shopro\wechat;

use fast\Random;
use addons\shopro\facade\Wechat;
use app\admin\model\shopro\ThirdOauth;
use app\admin\controller\shopro\Common;

class Admin extends Common
{

    protected $wechat;
    protected $noNeedRight = ['getQrcode', 'checkScan', 'unbind'];


    public function _initialize()
    {
        parent::_initialize();
        $this->wechat = Wechat::officialAccountManage();
    }

    // 获取公众号二维码
    public function getQrcode()
    {
        $event = $this->request->param('event');

        if (!in_array($event, ['bind'])) {
            $this->error('参数错误');
        }

        $adminId = $this->auth->id;
        $thirdOauth = ThirdOauth::where([
            'provider' => 'wechat',
            'platform' => 'admin',
            'admin_id' => $adminId
        ])->find();

        if ($thirdOauth) {
            error_stop('已绑定微信账号', -2, $thirdOauth);
        }

        // 二维码和缓存过期时间
        $expireTime = 1 * 60;

        // 事件唯一标识
        $eventId = Random::uuid();

        $cacheKey = "wechatAdmin.{$event}.{$eventId}";

        cache($cacheKey, ['id' => 0], $expireTime);

        try {
            $result = $this->wechat->qrcode->temporary($cacheKey, $expireTime);
            $qrcode = $this->wechat->qrcode->url($result['ticket']);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        
        $this->success('', null, [
            'url' => $qrcode,
            'eventId' => $eventId
        ]);
    }

    // 检查扫码结果
    public function checkScan()
    {
        $event = $this->request->param('event');
        $eventId = $this->request->param('eventId');

        if (!in_array($event, ['bind'])) {
            error_stop('参数错误');
        }

        $cacheKey = "wechatAdmin.{$event}.{$eventId}";

        $cacheValue = cache($cacheKey);

        if (empty($cacheValue)) {
            error_stop('二维码已过期, 请重新扫码');
        }

        if ($cacheValue['id'] === 0) {
            error_stop('等待扫码', -1);
        }

        if ($cacheValue['id'] !== 0) {
            switch ($event) {
                case 'bind':
                    $adminId = $this->auth->id;

                    $thirdOauth = ThirdOauth::where([
                        'provider' => 'wechat',
                        'platform' => 'admin',
                        'openid' => $cacheValue['id'],
                    ])->find();

                    if ($thirdOauth && $thirdOauth->admin_id !== 0) {
                        error_stop('该微信账号已被绑定');
                    }

                    if (!$thirdOauth) {
                        $thirdOauth = ThirdOauth::create([
                            'provider' => 'wechat',
                            'platform' => 'admin',
                            'openid' => $cacheValue['id'],
                            'admin_id' => $adminId
                        ]);
                    } else {
                        $thirdOauth->admin_id = $adminId;
                        $thirdOauth->save();
                    }
                    break;
            }
            $this->success();
        }
    }

    // 解绑
    public function unbind()
    {
        $adminId = $this->auth->id;

        $thirdOauth = ThirdOauth::where([
            'provider' => 'wechat',
            'platform' => 'admin',
            'admin_id' => $adminId
        ])->find();

        if ($thirdOauth) {
            $thirdOauth->admin_id = 0;
            $thirdOauth->save();
        }
        $this->success();
    }
}
