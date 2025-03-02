<?php

namespace addons\shopro\controller\third;

use addons\shopro\controller\Common;
use addons\shopro\service\third\apple\Apple as AppleService;

class Apple extends Common
{
    protected $noNeedLogin = ['login'];

    // 苹果登陆（仅对接App登录）
    public function login()
    {
        $payload = $this->request->post('payload/a');

        $apple = new AppleService();
        $result = $apple->login($payload);

        if ($result) {
            $this->success('登陆成功');
        }

        $this->error('登陆失败');
    }
}
