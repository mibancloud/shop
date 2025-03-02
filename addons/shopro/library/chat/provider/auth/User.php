<?php

namespace addons\shopro\library\chat\provider\auth;

use addons\shopro\exception\ShoproException;
use addons\shopro\library\chat\traits\DebugEvent;
use addons\shopro\library\chat\traits\Helper;
use addons\shopro\library\chat\traits\Session;
use addons\shopro\library\chat\traits\NspData;
use addons\shopro\library\chat\provider\auth\traits\Customer;
use addons\shopro\library\chat\Chat;
use addons\shopro\library\chat\Sender;


/**
 * 用户
 */
class User
{

    /**
     * debug 方式注册事件
     */
    use DebugEvent;

    /**
     * session 存储助手
     */
    use Session;
    /**
     * 助手方法
     */
    use Helper;

    /**
     * 顾客事件
     */
    use Customer;
    /**
     * 绑定数据到 nsp
     */
    use NspData;

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

    /**
     * 初始化
     *
     * @param Chat $chat
     */
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
        // 用户相关事件

        // 处理用户登录，将用户未登录时候产生的 连接，聊天记录，更新成当前用户
        // 更新信息

        // 直接注册顾客相关事件
        $this->customerOn();


        // 用户事件，待补充
        // ......
    }
    
}
