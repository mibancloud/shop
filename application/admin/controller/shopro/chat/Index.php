<?php

namespace app\admin\controller\shopro\chat;

use app\admin\controller\shopro\Common;
use app\admin\model\shopro\chat\CustomerService;

class Index extends Common
{

    protected $noNeedRight = ['init'];

    /**
     * socket 初始化
     *
     * @return void
     */
    public function init()
    {
        // 当前管理员
        $admin = auth_admin();
        $token = $this->getUnifiedToken('admin:' . $admin['id']);    // 统一验证 token

        // 客服配置
        $chatSystem = sheep_config('chat.system');

        // 初始化 socket ssl 类型, 默认 cert
        $ssl = $chatSystem['ssl'] ?? 'none';
        $chat_domain = ($ssl == 'none' ? 'http://' : 'https://') . request()->host(true) . ($ssl == 'reverse_proxy' ? '' : (':' . $chatSystem['port'])) . '/chat';

        $data = [
            'token' => $token,
            'chat_domain' => $chat_domain,
            'default_rooms' => CustomerService::defaultRooms()
        ];
        $this->success('获取成功', null, $data);
    }
}
