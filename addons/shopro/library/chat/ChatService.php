<?php

namespace addons\shopro\library\chat;

use addons\shopro\exception\ShoproException;
use addons\shopro\library\chat\traits\Helper;
use addons\shopro\library\chat\traits\Session;
use addons\shopro\library\chat\traits\NspData;
use addons\shopro\library\chat\traits\BindUId;
use app\admin\model\shopro\chat\User as ChatUser;
use app\admin\model\shopro\Config;
use PHPSocketIO\SocketIO;
use PHPSocketIO\Socket;
use PHPSocketIO\Nsp;
use Workerman\Timer;

/**
 * ChatService 服务类
 */
class ChatService
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
     * nsp 对象存储全局数据
     */
    use NspData;

    /**
     * 助手方法
     */
    use Helper;

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
     * 初始化
     *
     * @param Socket $socket
     * @param SocketIO $io
     * @param Nsp $nsp
     * @param Getter $getter
     */
    public function __construct(Socket $socket = null, SocketIO $io, Nsp $nsp, Getter $getter)
    {
        $this->socket = $socket;
        $this->io = $io;
        $this->nsp = $nsp;

        $this->getter = $getter;
    }


    /**
     * 通过 client_id 将当前 client_id，加入对应 room
     *
     * @param string $client_id
     * @param string $room
     * @return boolean
     */
    public function joinByClientId($client_id, $room)
    {
        // 找到 client_id 对应的 socket 实例
        $client = $this->nsp->sockets[$client_id];

        $client->join($room);

        return true;
    }


    /**
     * 通过 session_id 将 session_id 对应的所有客户端加入 room
     *
     * @param string $room
     * @param string $session_id
     * @param string $type
     * @return void
     */
    public function joinBySessionId($session_id, $type, $room)
    {
        // 当前用户 uid 绑定的所有客户端 clientIds
        $clientIds = $this->getClientIdByUId($session_id, $type);

        // 将当前 session_id 绑定的 client_id 都加入当前客服组
        foreach ($clientIds as $client_id) {
            $this->joinByClientId($client_id, $room);
        }
    }



    /**
     * 通过 client_id 将当前 client_id，离开对应 room
     *
     * @param string $client_id
     * @param string $room
     * @return boolean
     */
    public function leaveByClientId($client_id, $room)
    {
        // 找到 client_id 对应的 socket 实例
        $client = $this->nsp->sockets[$client_id];

        $client->leave($room);

        return true;
    }


    /**
     * 判断 auth 是否登录
     *
     * @return boolean
     */
    public function isLogin()
    {
        $user = $this->session('auth_user');
        return $user ? true : false;
    }


    /**
     * auth 登录
     *
     * @param array $data   登录参数，token session_id 等
     * @return boolean
     */
    public function authLogin($data)
    {
        if ($this->isLogin()) {
            return true;
        }
        $auth = $this->session('auth');
        $user = null;
        $token = $data['token'] ?? '';                     // fastadmin token
        $session_id = $data['session_id'] ?? '';           // session_id 如果没有，则后端生成
        $session_id = $session_id ?: $this->session('session_id');     // 如果没有，默认取缓存中的

        // 根据 token 获取当前登录的用户
        if ($token) {
            $user = $this->getter('db')->getAuthByToken($token, $auth);
        }

        if (!$user && $session_id) {
            $chatUser = $this->getter('db')->getChatUserBySessionId($session_id);
            $auth_id = $chatUser ? $chatUser['auth_id'] : 0;
            $user = $this->getter('db')->getAuthById($auth_id, $auth);
        }

        // 初始化连接，需要获取 session_id
        if (!$session_id) {
            // 如果没有 session_id
            if ($user) {
                // 如果存在 user
                $chatUser = $this->getter('db')->getChatUserByAuth($user['id'], $auth);
                $session_id = $chatUser ? $chatUser['session_id'] : '';
            }
        }

        if (!$session_id) {
            // 如果依然没有 session_id, 随机生成 session_id
            $session_id = md5(time() . mt_rand(1000000, 9999999));
        }

        // 更新顾客用户信息
        $chatUser = $this->updateChatUser($session_id, $user, $auth);
        $this->session('chat_user', $chatUser->toArray());     // 转为数组
        $this->session('session_id', $session_id);
        $this->session('auth_user', $user ? $user->toArray() : $user);

        // bind auth标示session_id，绑定 client_id
        $this->bindUId($this->session('session_id'), $auth);

        if ($user) {
            $this->loginOk();
            return true;
        }

        return false;
    }


    /**
     * auth 登录成功，注册相关事件
     *
     * @return void
     */
    public function loginOk()
    {
        $auth = $this->session('auth');
        $session_id = $this->session('session_id');

        // 将登陆的 auth 记录下来
        // $this->nspSessionIdAdd($session_id, $auth);
    }



    /**
     * 更新 chat_user
     *
     * @param string $session_id
     * @param array $auth
     * @param string $auth
     * @return array|object
     */
    public function updateChatUser($session_id, $authUser, $auth)
    {
        // 这里只根据 session_id 查询，
        // 当前 session_id 如果已经绑定了 auth 和 auth_id,会被 authUser 覆盖掉，无论是否是同一个 auth 和 auth_id
        // 不在 根据 authUser 或者 session_id 同时查
        // 用户匿名使用客服，登录绑定用户之后，又退出登录其他用户，那么这个 session_id 和老的聊天记录直接归新用户所有
        $chatUser = ChatUser::where('auth', $auth)->where(function ($query) use ($session_id, $authUser) {
            $query->where('session_id', $session_id);
                // ->whereOr(function ($query) use ($authUser) {
                //     $query->where('auth_id', '<>', 0)
                //     ->where('auth_id', ($authUser ? $authUser['id'] : 0));
                // });
        })->find();

        $defaultUser = Config::getConfigs('basic.user');
        $defaultAvatar = $defaultUser['avatar'] ?? null;
        // $defaultNickname = $defaultUser['nickname'] ?? null;

        if (!$chatUser) {
            $chatUser = new ChatUser();

            $chatUser->session_id = $session_id;
            $chatUser->auth = $auth;
            $chatUser->auth_id = $authUser ? $authUser['id'] : 0;
            $chatUser->nickname = $authUser ? $authUser['nickname'] : ('游客-' . substr($session_id, 0, 5));
            $chatUser->avatar = $authUser ? $authUser->getData('avatar') : $defaultAvatar;
            $chatUser->customer_service_id = 0;      // 断开连接的时候存入
            $chatUser->last_time = time();
        } else {
            if ($authUser) {
                // 更新用户信息
                $chatUser->auth = $auth;
                $chatUser->auth_id = $authUser['id'] ?? 0;
                $chatUser->nickname = $authUser['nickname'] ? $authUser['nickname'] : ('游客-' . substr($session_id, 0, 5));
                $chatUser->avatar = $authUser['avatar'] ? $authUser->getData('avatar') : $defaultAvatar;
            }

            $chatUser->last_time = time();        // 更新时间
        }

        $chatUser->save();
        return $chatUser;
    }



    /**
     * 检测并且分配客服
     *
     * @return void
     */
    public function checkAndAllocatCustomerService()
    {
        $room_id = $this->session('room_id');
        $session_id = $this->session('session_id');
        $auth = $this->session('auth');

        $customerService = null;
        // 获取用户临时存的 客服
        $customer_service_id = $this->nspGetConnectionCustomerServiceId($room_id, $session_id);
        if ($customer_service_id) {
            $customerService = $this->getter('socket')->getCustomerServiceById($room_id, $customer_service_id);
        }

        if (!$customerService) {
            // 获取当前顾客有没有其他端正在连接客服，如果有，直接获取该客服
            $customerService = $this->getter('socket')->getCustomerServiceBySessionId($room_id, $session_id);
        }

        if (!$customerService) {
            $chatBasic = $this->getConfig('basic');
            if ($chatBasic['auto_customer_service']) {
                // 自动分配客服
                $chatUser = $this->session('chat_user');
                $customerService = $this->allocatCustomerService($room_id, $chatUser);
            }
        }

        // 分配了客服
        if ($customerService) {
            // 将当前 用户与 客服绑定
            $this->bindCustomerServiceBySessionId($room_id, $session_id, $customerService);
        } else {
            // 加入等待组中
            $room = $this->getRoomName('customer_service_room_waiting', ['room_id' => $room_id]);
            $this->joinBySessionId($session_id, $auth, $room);

            // 将用户 session_id 加入到等待排名中,自动排重
            $this->nspWaitingAdd($room_id, $session_id);
        }

        return $customerService;
    }


    /**
     * 分配客服
     *
     * @param string $room_id, 客服房间号
     * @return array
     */
    private function allocatCustomerService($room_id, $chatUser)
    {
        $config = $this->getConfig('basic');

        $last_customer_service = $config['last_customer_service'] ?? 1;
        $allocate = $config['allocate'] ?? 'busy';

        // 分配的客服
        $currentCustomerService = null;

        // 使用上次客服
        if ($last_customer_service) {
            // 获取上次连接的信息
            $lastServiceLog = $this->getter('db')->getLastServiceLogByChatUser($room_id, $chatUser['id']);

            if ($lastServiceLog) {
                // 获取上次客服信息
                $currentCustomerService = $this->getter('socket')->getCustomerServiceById($room_id, $lastServiceLog['customer_service_id']);       // 通过连接房间，获取socket 连接里面上次客服,不在线为 null
            }
        }

        // 没有客服，随机分配
        if (!$currentCustomerService) {
            // 在线客服列表
            $onlineCustomerServices = $this->getter('socket')->getCustomerServicesByRoomId($room_id);

            if ($onlineCustomerServices) {
                if ($allocate == 'busy') {
                    // 将客服列表，按照工作繁忙程度正序排序, 这里没有离线的客服
                    $onlineCustomerServices = array_column($onlineCustomerServices, null, 'busy_percent');
                    ksort($onlineCustomerServices);

                    // 取忙碌度最小，并且客服为 正常在线状态
                    foreach ($onlineCustomerServices as $customerService) {
                        if ($customerService['status'] == 'online') {
                            $currentCustomerService = $customerService;
                            break;
                        }
                    }
                } else if ($allocate == 'turns') {
                    // 按照最后接入时间正序排序，这里没有离线的客服
                    $onlineCustomerServices = array_column($onlineCustomerServices, null, 'last_time');
                    ksort($onlineCustomerServices);

                    // 取最后接待最早，并且客服为 正常在线状态
                    foreach ($onlineCustomerServices as $customerService) {
                        if ($customerService['status'] == 'online') {
                            $currentCustomerService = $customerService;
                            break;
                        }
                    }
                } else if ($allocate == 'random') {
                    // 随机获取一名客服

                    // 挑出来状态为在线的客服
                    $onlineStatus = [];
                    foreach ($onlineCustomerServices as $customerService) {
                        if ($customerService['status'] == 'online') {
                            $onlineStatus[] = $customerService;
                        }
                    }

                    $onlineStatus = array_column($onlineStatus, null, 'id');

                    $customer_service_id = 0;
                    if ($onlineStatus) {
                        $customer_service_id = array_rand($onlineStatus);
                    }

                    $currentCustomerService = $onlineStatus[$customer_service_id] ?? null;
                }

                if (!$currentCustomerService) {
                    // 如果都不是 online 状态(说明全是 busy)，默认取第一条
                    $currentCustomerService = $onlineCustomerServices[0] ?? null;
                }
            }
        }

        return $currentCustomerService;
    }




    /**
     * 通过 session_id 记录客服信息，并且加入对应的客服组
     *
     * @param string $room_id    客服房间号
     * @param string $session_id    用户
     * @param array $customerService    客服信息
     * @return void
     */
    public function bindCustomerServiceBySessionId($room_id, $session_id, $customerService)
    {
        // 当前用户 uid 绑定的所有客户端 clientIds
        $clientIds = $this->getClientIdByUId($session_id, 'customer');

        // 将当前 session_id 绑定的 client_id 都加入当前客服组
        foreach ($clientIds as $client_id) {
            self::bindCustomerServiceByClientId($room_id, $client_id, $customerService);
        }

        // 添加 serviceLog
        $chatUser = $this->getter('db')->getChatUserBySessionId($session_id);
        $this->getter('db')->createServiceLog($room_id, $chatUser, $customerService);
    }


    /**
     * 顾客session 记录客服信息，并且加入对应的客服组
     *
     * @param string $room_id    客服房间号
     * @param string $client_id    用户
     * @param array $customerService    客服信息
     * @return null
     */
    public function bindCustomerServiceByClientId($room_id, $client_id, $customerService)
    {
        // 更新用户的客服信息
        $this->updateSessionByClientId($client_id, [
            'customer_service_id' => $customerService['id'],
            'customer_service' => $customerService
        ]);

        // 更新客服的最后接入用户时间
        $this->getter()->updateCustomerServiceInfo($customerService['id'], ['last_time' => time()]);

        // 加入对应客服组，统计客服信息，通知用户客服上线等
        $room = $this->getRoomName('customer_service_room_user', ['room_id' => $room_id, 'customer_service_id' => $customerService['id']]);
        $this->joinByClientId($client_id, $room);

        // 从等待接入组移除
        $room = $this->getRoomName('customer_service_room_waiting', ['room_id' => $room_id]);
        $this->leaveByClientId($client_id, $room);
    }



    /**
     * 通过 session_id 将用户移除客服组
     *
     * @param string $room_id    房间号
     * @param string $session_id    用户
     * @param array $customerService    客服信息
     * @return null
     */
    public function unBindCustomerServiceBySessionId($room_id, $session_id, $customerService)
    {
        // 当前用户 uid 绑定的所有客户端 clientIds
        $clientIds = $this->getClientIdByUId($session_id, 'customer');

        // 将当前 session_id 绑定的 client_id 都加入当前客服组
        foreach ($clientIds as $client_id) {
            $this->unBindCustomerService($room_id, $client_id, $customerService);
        }
    }


    /**
     * 将用户的客服信息移除
     *
     * @param string $room_id    房间号
     * @param string $client_id    用户
     * @param array $customerService    客服信息
     * @return null
     */
    public function unBindCustomerService($room_id, $client_id, $customerService)
    {
        // 清空连接的客服的 session
        $this->updateSessionByClientId($client_id, [
            'customer_service_id' => 0,
            'customer_service' => []
        ]);

        // 移除对应客服组
        $room = $this->getRoomName('customer_service_room_user', ['room_id' => $room_id, 'customer_service_id' => $customerService['id']]);
        $this->leaveByClientId($client_id, $room);
    }


    /**
     * 客服转接，移除旧的客服，加入新的客服
     *
     * @param string $room_id    客服房间号
     * @param string $session_id    用户
     * @param array $customerService    客服信息
     * @param array $newCustomerService    新客服信息
     * @return void
     */
    public function transferCustomerServiceBySessionId($room_id, $session_id, $customerService, $newCustomerService)
    {
        // 获取session_id 的 chatUser
        $chatUser = $this->getter('db')->getChatUserBySessionId($session_id);
        // 结束 serviceLog,如果没有，会创建一条记录
        $this->endService($room_id, $chatUser, $customerService);

        // 解绑老客服
        $this->unBindCustomerServiceBySessionId($room_id, $session_id, $customerService);

        // 接入新客服
        $this->bindCustomerServiceBySessionId($room_id, $session_id, $newCustomerService);
    }



    /**
     * 结束服务
     *
     * @param string $room_id    客服房间号
     * @param object $chatUser    顾客信息
     * @param array $customerService    客服信息
     * @return void
     */
    public function endService($room_id, $chatUser, $customerService)
    {
        // 结束掉服务记录
        $this->getter('db')->endServiceLog($room_id, $chatUser, $customerService);

        // 记录客户最后服务的客服
        $chatUser->customer_service_id = $customerService['id'] ?? 0;
        $chatUser->save();
    }



    /**
     * 断开用户的服务
     *
     * @param string $room_id
     * @param string $session_id
     * @param array $customerService
     * @return void
     */
    public function breakCustomerServiceBySessionId($room_id, $session_id, $customerService)
    {
        // 获取session_id 的 chatUser
        $chatUser = $this->getter('db')->getChatUserBySessionId($session_id);
        if ($chatUser) {
            // 结束 serviceLog,如果没有，会创建一条记录
            $this->endService($room_id, $chatUser, $customerService);
        }

        // 解绑老客服
        $this->unBindCustomerServiceBySessionId($room_id, $session_id, $customerService);
    }


    /**
     * 检查当前用户的客服身份
     *
     * @param string $room_id       房间号
     * @param string $auth          用户类型
     * @param integer $auth_id      用户 id
     * @return boolean|object
     */
    public function getCustomerServicesByAuth($auth_id, $auth)
    {
        $customerServices = $this->getter('db')->getCustomerServicesByAuth($auth_id, $auth);

        return $customerServices;
    }



    /**
     * 检查当前用户在当前房间是否是客服身份
     *
     * @param string $room_id       房间号
     * @param string $auth          用户类型
     * @param integer $auth_id      用户 id
     * @return boolean|object
     */
    public function checkIsCustomerService($room_id, $auth_id, $auth)
    {
        $currentCustomerService = $this->getter('db')->getCustomerServiceByAuthAndRoom($room_id, $auth_id, $auth);
        
        return $currentCustomerService ? : false;
    }



    /**
     * 客服登录
     *
     * @param string $room_id
     * @param integer $auth_id
     * @param string $auth
     * @return boolean
     */
    public function customerServiceLogin($room_id, $auth_id, $auth) : bool
    {
        if ($customerService = $this->checkIsCustomerService($room_id, $auth_id, $auth)) {
            // 保存 客服信息
            $this->session('customer_service_id', $customerService['id']);
            $this->session('customer_service', $customerService->toArray());    // toArray 减少内存占用
            return true;
        }

        return false;
    }


    
    /**
     * 客服上线
     *
     * @param string $room_id
     * @param integer $customer_service_id
     * @return void
     */
    public function customerServiceOnline($room_id, $customer_service_id)
    {
        // 当前 socket 绑定 客服 id
        $this->bindUId($customer_service_id, 'customer_service');

        // 更新客服状态
        $this->getter()->updateCustomerServiceStatus($customer_service_id, 'online');

        // 只把当前连接加入在线客服组，作为服务对象，多个连接同时登录一个客服，状态相互隔离
        $this->socket->join($this->getRoomName('identify', ['identify' => 'customer_service']));

        // 只把当前连接加入当前频道的客服组，为后续多商户做准备
        $this->socket->join($this->getRoomName('customer_service_room', ['room_id' => $room_id]));
        
        // 保存当前客服身份
        $this->session('identify', 'customer_service');
    }


    
    /**
     * 客服离线
     *
     * @param string $room_id
     * @param integer $customer_service_id
     * @return void
     */
    public function customerServiceOffline($room_id, $customer_service_id)
    {
        // 只把当前连接移除在线客服组，多个连接同时登录一个客服，状态相互隔离
        $this->socket->leave($this->getRoomName('identify', ['identify' => 'customer_service']));

        // 只把当前连接移除当前频道的客服组，为后续多商户做准备
        $this->socket->leave($this->getRoomName('customer_service_room', ['room_id' => $room_id]));

        // 当前 socket 解绑 客服 id
        $this->unBindUId($customer_service_id, 'customer_service');

        // 更新客服状态为离线
        $this->getter()->updateCustomerServiceStatus($customer_service_id, 'offline');
    }



    /**
     * 客服忙碌
     *
     * @param string $room_id
     * @param integer $customer_service_id
     * @return void
     */
    public function customerServiceBusy($room_id, $customer_service_id)
    {
        // 当前 socket 绑定 客服 id
        $this->bindUId($customer_service_id, 'customer_service');

        // （尝试重新加入，避免用户是从离线切换过来的）只把当前连接加入在线客服组，作为服务对象，多个连接同时登录一个客服，状态相互隔离
        $this->socket->join($this->getRoomName('identify', ['identify' => 'customer_service']));

        // （尝试重新加入，避免用户是从离线切换过来的）只把当前连接加入当前频道的客服组，为后续多商户做准备
        $this->socket->join($this->getRoomName('customer_service_room', ['room_id' => $room_id]));
        
        // 更新客服状态为忙碌
        $this->getter()->updateCustomerServiceStatus($customer_service_id, 'busy');
    }



    /**
     * 客服退出
     *
     * @param [type] $room_id
     * @param [type] $customer_service_id
     * @return void
     */
    public function customerServiceLogout($room_id, $customer_service_id)
    {
        // 退出房间
        $this->session('room_id', null);

        // 移除当前客服身份
        $this->session('identify', null);

        // 移除客服信息
        $this->session('customer_service_id', null);
        $this->session('customer_service', null);    // toArray 减少内存占用
    }



    /**
     * 顾客上线
     *
     * @return void
     */
    public function customerOnline()
    {
        // 用户信息
        $session_id = $this->session('session_id');
        $auth = $this->session('auth');

        // 当前 socket 绑定 顾客 id
        $this->bindUId($session_id, 'customer');
        // 加入在线顾客组，作为被服务对象
        $this->socket->join($this->getRoomName('identify', ['identify' => 'customer']));
        // 保存当前顾客身份
        $this->session('identify', 'customer');
    }


    /**
     * 顾客下线
     *
     * @return void
     */
    public function customerOffline()
    {
        // 用户信息
        $session_id = $this->session('session_id');
        $customer_service_id = $this->session('customer_service_id');
        $room_id = $this->session('room_id');
        $customerService = $this->session('customer_service');
        $chatUser = $this->session('chat_user');

        // 退出房间
        $this->session('room_id', null);

        // 当前 socket 绑定 顾客 id
        $this->unbindUId($session_id, 'customer');

        // 离开在线顾客组，作为被服务对象
        $this->socket->leave($this->getRoomName('identify', ['identify' => 'customer']));
        // 移除当前顾客身份
        $this->session('identify', null);
        
        // 如果有客服正在服务，移除
        if ($customer_service_id) {
            // 离开所在客服的服务对象组
            $this->socket->leave($this->getRoomName('customer_service_room_user', ['room_id' => $room_id, 'customer_service_id' => $customer_service_id]));

            // 移除客服信息
            $this->session('customer_service_id', null);
            $this->session('customer_service', null);

            // 获取session_id 的 chatUser
            $chatUser = $this->getter('db')->getChatUserBySessionId($session_id);

            if ($this->getter('socket')->isOnLineCustomerById($session_id)) {
                // 结束 serviceLog,如果没有，会创建一条记录
                $this->endService($room_id, $chatUser, $customerService);
            }
        } else {
            // 离开等待组
            $this->socket->leave($this->getRoomName('customer_service_room_waiting', ['room_id' => $room_id]));
        }
    }



    /**
     * 断开连接解绑
     *
     * @return void
     */
    public function disconnectUnbindAll()
    {
        // 因为 session 是存在 socket 上的，服务端重启断开连接，或者刷新浏览器 socket 会重新连接，所以存的 session 会全部清空
        // 服务端重启 会导致 bind 到 io 实例的 bind 的数据丢失，但是如果是前端用户刷新浏览器，discount 事件中就必须要解绑 bind 数据

        $room_id = $this->session('room_id');
        $session_id = $this->session('session_id');
        $auth = $this->session('auth');
        $identify = $this->session('identify') ? : '';
        $customerService = $this->session('customer_service');
        
        // 解绑顾客身份
        if ($identify == 'customer') {
            $this->unbindUId($session_id, 'customer');

            if ($customerService) {
                // 连接着客服，将客服信息暂存 nsp 中，防止刷新重新连接
                $this->nspConnectionAdd($room_id, $session_id, $customerService['id']);

                // 如果有客服，定时判断，如果客服掉线了，关闭
                Timer::add(10, function () use ($room_id, $session_id, $customerService) {
                    // 十秒之后顾客不在线，说明是真的下线了
                    if (!$this->getter('socket')->isOnLineCustomerById($session_id)) {
                        $chatUser = $this->getter('db')->getChatUserBySessionId($session_id);
                        $this->endService($room_id, $chatUser, $customerService);
                    }
                }, null, false);
            }
        }

        // 解绑客服身份
        if ($identify == 'customer_service') {
            $customer_service_id = $this->session('customer_service_id');
            $this->unbindUId($customer_service_id, 'customer_service');
        }

        // 将当前的 用户与 client 解绑
        $this->unbindUId($session_id, $auth);
    }
}
