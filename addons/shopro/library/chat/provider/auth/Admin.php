<?php

namespace addons\shopro\library\chat\provider\auth;

use addons\shopro\exception\ShoproException;
use addons\shopro\library\chat\traits\DebugEvent;
use addons\shopro\library\chat\traits\Helper;
use addons\shopro\library\chat\traits\Session;
use addons\shopro\library\chat\traits\NspData;
use addons\shopro\library\chat\provider\auth\traits\CustomerService;
use addons\shopro\library\chat\Chat;
use addons\shopro\library\chat\ChatService;
use addons\shopro\library\chat\Getter;
use addons\shopro\library\chat\Sender;
use PHPSocketIO\SocketIO;
use PHPSocketIO\Socket;
use PHPSocketIO\Nsp;

/**
 * 管理员身份
 */
class Admin
{

    /**
     * debug 方式注册事件
     */
    use DebugEvent;

    /**
     * 助手方法
     */
    use Helper;
    /**
     * session 存储助手
     */
    use Session;
    /**
     * 绑定数据到 nsp
     */
    use NspData;
    /**
     * 客服事件
     */
    use CustomerService;

    /**
     * 当前 Chat 实例
     * 
     * @var Chat
     */
    protected $chat;
    /**
     * 当前 phpsocket.io 实例
     * 
     * @var SocketIO
     */
    protected $io;
    /**
     * 当前socket 连接
     * 
     * @var Socket
     */
    protected $socket;
    /**
     * 当前 命名空间
     * 
     * @var Nsp
     */
    public $nsp;

    /**
     * getter 实例
     *
     * @var Getter
     */
    protected $getter;
    /**
     * sender 实例
     *
     * @var Getter
     */
    protected $sender;
    /**
     * getter 实例
     *
     * @var ChatService
     */
    protected $chatService;

    public function __construct(Chat $chat)
    {
        $this->chat = $chat;

        $this->io = $chat->io;
        $this->socket = $chat->socket;
        $this->nsp = $chat->nsp;
        $this->getter = $chat->getter;
        $this->chatService = $chat->chatService;
        $this->sender = $chat->sender;
    }



    public function on() 
    {
        // 检测当前 auth 是否是客服
        $this->register('check_identify', function ($data, $callback) {
            // 检查当前管理员登录状态
            if (!$this->chatService->isLogin()) {
                throw new ShoproException('请先登录管理后台');
            }

            $admin = $this->session('auth_user');
            // 获取当前管理员的客服身份
            $customerServices = $this->chatService->getCustomerServicesByAuth($admin['id'], $this->session('auth'));
            if (!$customerServices) {
                throw new ShoproException('');       // 您还不是客服，后台不提示，留空
            }

            // 注册客服事件
            $this->customerServiceOn();

            $this->sender->successSocket($callback, '验证成功', [
                'customer_services' => $customerServices
            ]);
        });
    }
    
}
