<?php

namespace addons\shopro\library\chat;

use addons\shopro\exception\ShoproException;
use addons\shopro\library\chat\traits\DebugEvent;
use addons\shopro\library\chat\traits\Helper;
use addons\shopro\library\chat\traits\Session;
use addons\shopro\library\chat\traits\NspData;
use addons\shopro\library\chat\traits\BindUId;
use PHPSocketIO\SocketIO;
use PHPSocketIO\Socket;
use PHPSocketIO\Nsp;

class Chat
{
    /**
     * session 存储助手
     */
    use Session;

    /**
     * 绑定 UID 助手
     */
    use BindUId;
    /**
     * 绑定数据到 nsp 作为全局数据
     */
    use NspData;
    /**
     * 助手方法
     */
    use Helper;

    /**
     * debug 方式注册事件
     */
    use DebugEvent;

    /**
     * 当前 phpsocket.io 实例
     * 
     * @var SocketIO
     */
    public $io;

    /**
     * 当前连接实例
     * 
     * @var Socket
     */
    public $socket;

    /**
     * 当前 namespace 实例
     * 
     * @var Nsp
     */
    public $nsp;

    /**
     * 当前发送实例
     * 
     * @var Sender
     */
    public $sender;

    /**
     * 当前获取数据
     * 
     * @var Getter
     */
    public $getter;

    /**
     * chat 操作类
     *
     * @var ChatService
     */
    public $chatService;

    protected $auth = [
        'user',
        'admin',
    ];


    /**
     * 初始化 chat 系统
     *
     * @param SocketIo $io
     * @param Socket $socket
     * @param Nsp $nsp
     */
    public function __construct(SocketIo $io, Nsp $nsp, Socket $socket = null)
    {
        $this->io = $io;
        $this->socket = $socket;
        $this->nsp = $nsp;

        // 初始化获取更改数据实例
        $this->getter = new Getter($socket, $io, $nsp);
        // 初始化发送实例
        $this->sender = new Sender($socket, $io, $nsp, $this->getter);
        // 初始化 客服公共方法实例
        $this->chatService = new ChatService($socket, $io, $nsp, $this->getter);
    }

    public function on()
    {
        // on 方法只有在连接的时候走一次
        $this->register('test', function ($data, $callback) {

            // $class = "\\app\\chat\\library\\provider\\auth\\User";
            // $provider = new $class($this);

            // $this->socket->removeAllListeners('message');

            // 注册相关身份事件
            // $provider->customerEvent();


            $customer_service_room = $this->getRoomName('customer_service_room', ['room_id' => 'admin']);
            $customerServices = $this->getter('socket')->getSessionsByRoom($customer_service_room, 'customer_service');

            $this->sender->successSocket($callback, '连接成功', [
                'msg' => '恭喜鏈接成功',
                'bind' => $this->nsp->bind ?? [],
                'nsp_room_ids' => $this->nspData('room_ids'),
                'customer_service' => $customerServices,
                'nsp_data' => $this->nsp->nspData ?? [],
                'rooms' => isset($this->nsp->adapter->rooms) ? $this->nsp->adapter->rooms : [],
                'current_rooms' => $this->socket->rooms,
                'session' => $this->session(),
                'client_id' => $this->socket->id,
                'session_ids' => $this->nspData('session_ids')
            ]);

            
            $this->sender->successUId('new message', '消息桶送', ['aaa' => 'bbb'], [
                'id' => $this->session('session_id'),
                'type' => $this->session('auth'),
            ]);

            // foreach ($clientIds as $client_id) {
            //     $this->sender->successSocket('new message', ['aaa' => 'bbb']);
            // }

            $this->socket->on('test-child', function ($data, $callback) {
                echo "子集消息来了";

                $this->session('text:child', 'aaa');
                $this->sender->successSocket($callback, '连接成功', [
                    'msg' => '子事件夜之星成功了'
                ]);
            });
        });


        // socket 连接初始化，socket.io-client 连接后的第一步
        $this->register('connection', function ($data, $callback) {
            // 初始化连接
            $auth = $data['auth'] ?? '';

            if (!in_array($auth, $this->auth)) {
                throw new ShoproException('身份错误');
            }

            // 存储当前 auth 驱动
            $this->session('auth', $auth);

            // 加入对应身份组
            $this->socket->join($this->getRoomName('auth', ['auth' => $auth]));

            // 加入在线连接组
            $this->socket->join('online');

            // 检测并自动登录
            $result = $this->chatService->authLogin($data);

            // 注册各自身份的事件
            $this->authEvent($auth);

            // 连接成功，发送给自己
            $this->sender->authSuccess($callback);
        });

        // auth 身份登录，管理员或者用户
        $this->register('login', function ($data, $callback) {
            // 登录，和系统中的用户或者管理员绑定
            $result = $this->chatService->authLogin($data);
            if ($result) {
                // 登录成功
                $this->sender->authSuccess($callback);

                return true;
            }

            // 登录失败
            throw new ShoproException('登录失败');
        });


        /**
         * 断开连接
         */
        $this->register('disconnect', function ($data, $callback) {
            $customer_service_id = $this->session('customer_service_id');
            $session_id = $this->session('session_id');
            $identify = $this->session('identify') ?: '';
            // $auth = $this->session('auth');
            // $authUser = $this->session('auth_user');

            // 断开连接，解绑 bind 的身份
            $this->chatService->disconnectUnbindAll();

            // 如果登录了，并且所有客户端都下线了， 删除相关 auth 的 ids
            // if ($this->chatService->isLogin() && !$this->getter('socket')->isOnlineAuthBySessionId($session_id, $auth)) {
            //     $this->nspSessionIdDel($session_id, $auth);
            // }

            // 如果是顾客
            if ($identify == 'customer') {
                // 顾客所在房间
                $room_id = $this->session('room_id');

                // 顾客断开连接
                if (!$this->getter('socket')->isOnLineCustomerById($session_id)) {
                    // 当前所遇用户端都断开了

                    $waiting_room_name = $this->getRoomName('customer_service_room_waiting', ['room_id' => $room_id]);
                    $rooms = $this->socket->rooms;
                    // 判断是否在排队中
                    if (in_array($waiting_room_name, $rooms)) {
                        // 这里顾客的所有客户端都断开了，在排队排名中移除
                        $this->nspWaitingDel($room_id, $session_id);
    
                        // 排队发生变化，通知房间中所有排队用户
                        $this->sender->allWaitingQueue($room_id);

                        // 离开排队中房间（将离线的用户从等待中移除）
                        $this->socket->leave($waiting_room_name);
                        // 通知更新排队中列表，把当前下线用户移除
                        $this->sender->waiting();
                    }

                    // 通知所有客服，顾客下线
                    $this->sender->customerOffline();
                }
            }

            // 如果是客服
            if ($identify == 'customer_service') {
                // 客服断开连接
                if (!$this->getter('socket')->isOnLineCustomerServiceById($customer_service_id)) {
                    // 当前客服的所有客户端都下线了

                    // 更新客服状态为离线
                    $this->getter()->updateCustomerServiceStatus($customer_service_id, 'offline');

                    // 通知连接的用户(在当前客服服务的房间里面的用户)，客服下线了
                    $this->sender->customerServiceOffline();

                    // 通知当前房间的在线客服，更新当前在线客服列表
                    $this->sender->customerServiceUpdate();
                }
            }
        });
    }


    /**
     * 注册相关身份事件
     *
     * @param string $auth
     * @return void
     */
    private function authEvent($auth) 
    {
        // 实例化相关身份事件
        $class = "\\addons\\shopro\\library\\chat\\provider\\auth\\" . ucfirst($auth);
        $provider = new $class($this);

        // 注册相关身份事件
        $provider->on();
    }


    /**
     * 站内信通知
     *
     * @param object $http_connection
     * @param string $uri
     * @param array $data
     * @return void
     */
    public function innerWorker($httpConnection, $uri, $data)
    {
        if ($uri == '/notification') {
            $this->exec($httpConnection, function () use ($data) {
                $receiver = $data['receiver'] ?? [];
                $sendData = $data['data'] ?? [];

                $receiver_type = $receiver['type'] ?? 'user';
                $receiverIds = $receiver['ids'] ?? '';
                $receiverIds = is_array($receiverIds) ? $receiverIds : explode(',', $receiverIds);
                
                // 循环给接收者发送消息 
                foreach ($receiverIds as $id) {
                    // 获取接收人的 session_id
                    $session_id = $this->getter()->getSessionIdByAuth($id, $receiver_type);
                    if ($session_id) {
                        $this->sender->successUId('notification', '收到通知', $sendData, [
                            'id' => $session_id, 'type' => $receiver_type
                        ]);
                    }
                }
            });
        }

        // 这句话必须有，否则会提示超时
        $httpConnection->send('ok');
    }
}
