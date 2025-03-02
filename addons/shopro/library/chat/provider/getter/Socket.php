<?php

namespace addons\shopro\library\chat\provider\getter;

use addons\shopro\exception\ShoproException;
use addons\shopro\library\chat\traits\Helper;
use addons\shopro\library\chat\traits\Session;
use addons\shopro\library\chat\traits\BindUId;
use addons\shopro\library\chat\Getter;
use PHPSocketIO\SocketIO;
use PHPSocketIO\Socket as PhpSocket;
use PHPSocketIO\Nsp;

/**
 * 从 socket 连接中获取
 */
class Socket
{

    /**
     * session 存储助手
     */
    use Session;

    /**
     * BindUid 助手
     */
    use BindUId;
    /**
     * 助手方法
     */
    use Helper;

    /**
     * getter 实例
     * 
     * @var Getter
     */
    protected $getter;

    /**
     * 当前 phpsocket.io 实例
     * 
     * @var SocketIo
     */
    protected $io = null;
    /**
     * 当前socket 连接
     * 
     * @var PhpSocket
     */
    protected $socket = null;
    /**
     * 当前 命名空间实例
     * 
     * @var Nsp
     */
    protected $nsp = null;


    public function __construct(Getter $getter, PhpSocket $socket = null, SocketIo $io, Nsp $nsp)
    {
        $this->getter = $getter;
        $this->socket = $socket;
        $this->io = $io;
        $this->nsp = $nsp;
    }



    /**
     * 通过房间获取房间中所有连接的 clientIds
     *
     * @param string $room  房间名称
     * @return void
     */
    public function getClientIdsByRoom($room) {
        // 获取到的数组 键是 id 值是 boolean true
        $clientIds = $this->nsp->adapter->rooms[$room] ?? [];
        // 取出 clientIds
        $clientIds = array_keys($clientIds);
        return $clientIds;
    }



    /**
     * 根据房间获取所有房间中的 authUser
     *
     * @param string $room_id     房间号
     * @param string $is_unique     默认过滤重复的数据
     * @return array
     */
    public function getAuthsByAuth($auth, $is_unique = true)
    {
        // 要接入的客服所在的房间，默认 admin 房间,z这里的客服的状态都是 在线的，如果手动切换为离线，则会被移除该房间
        $room = $this->getRoomName('auth', ['auth' => $auth]);

        $sessions = $this->getSessionsByRoom($room, null, $is_unique);

        $authUsers = [];
        foreach ($sessions as $session) {
            if (isset($session['auth_user']) && $session['auth_user']) {
                $authUsers[$session['session_id']] = $session['auth_user'];
            }
        }

        return $authUsers;
    }



    /**
     * 通过id 获取指定客服，并且还必须在对应的客服房间中
     *
     * @param string $id    客服 id
     * @param string $room_id     客服房间号
     * @return array|null
     */
    public function getCustomerServiceById($room_id, $id)
    {
        // 要接入的客服所在的房间，默认 admin 房间
        $room = $this->getRoomName('customer_service_room', ['room_id' => $room_id]);

        // 房间中的所有客服的 clientids
        $roomClientIds = $this->getClientIdsByRoom($room);

        // 当前客服 uid 绑定的所有客户端 clientIds，手动后台离线的已经被解绑了，这里都是状态为在线的
        $currentClientIds = $this->getClientIdByUId($id, 'customer_service');

        if ($clientIds = array_intersect($currentClientIds, $roomClientIds)) {
            // 客服在线
            return $this->getSession(current($clientIds), 'customer_service');
        }

        return null;
    }



    /**
     * 通过 客服 id 判断客服是否在线，这里不管客服所在房间，一个客服只能属于一个房间
     *
     * @param string $id     客服 id
     * @return boolean
     */
    public function isOnLineCustomerServiceById($id)
    {
        return $this->isUIdOnline($id, 'customer_service');
    }


    /**
     * 根据房间获取所有房间中的客服
     *
     * @param string $room_id     客服房间号
     * @param string $is_unique     默认过滤重复的数据
     * @return array
     */
    public function getCustomerServicesByRoomId($room_id, $is_unique = true)
    {
        // 要接入的客服所在的房间，默认 admin 房间,z这里的客服的状态都是 在线的，如果手动切换为离线，则会被移除该房间
        $room = $this->getRoomName('customer_service_room', ['room_id' => $room_id]);

        $customerServices = $this->getSessionsByRoom($room, 'customer_service', $is_unique);

        return $customerServices;
    }


    /**
     * 获取指定 session_id 在对应房间的所有客户端的服务客服的信息
     *
     * @param [type] $room_id
     * @param [type] $session_id
     * @return void
     */
    public function getCustomerServiceBySessionId($room_id, $session_id)
    {
        $sessions = $this->getSessionsById($session_id, 'customer');

        $customerService = null;
        foreach ($sessions as $session) {
            if (isset($session['room_id']) && $session['room_id'] == $room_id 
            && isset($session['customer_service']) && $session['customer_service']) {
                $currentCustomerService = $session['customer_service'];
                
                if ($this->isOnLineCustomerServiceById($currentCustomerService['id'])) {
                    // 如果客服在线    
                    $customerService = $currentCustomerService;
                    break;
                }
            }
        }
        return $customerService;
    }


    /**
     * 判断当前房间中是否有客服在线
     *
     * @param string $room_id     客服房间号
     * @return boolean
     */
    public function hasCustomerServiceByRoomId($room_id)
    {
        // 获取房间中所有客服的 session,只要有，就说明有客服在线，手动切换状态的会被移除 在线客服房间
        $allCustomerServices = $this->getCustomerServicesByRoomId($room_id, false);

        if ($allCustomerServices) {
            return true;
        }
        return false;
    }


    /**
     * 判断并通过 顾客 获取顾客的客服
     */
    public function getCustomerServiceByCustomerSessionId($session_id)
    {
        $customerServices = $this->getSessionsById($session_id, 'customer', 'customer_service');

        return current($customerServices) ?? null;
    }


    /**
     * (获取客服正在服务的顾客)根据客服获取客服房间中所有的被服务用户
     *
     * @param string $room_id     客服房间号
     * @param integer $customer_service_id     客服 id
     * @param string $is_unique     默认过滤重复的数据
     * @return array
     */
    public function getCustomersIngByCustomerService($room_id, $customer_service_id, $is_unique = true)
    {
        // 要接入的客服所在的房间，默认 admin 房间,z这里的客服的状态都是 在线的，如果手动切换为离线，则会被移除该房间
        $room = $this->getRoomName('customer_service_room_user', ['room_id' => $room_id, 'customer_service_id' => $customer_service_id]);

        $customers = $this->getSessionsByRoom($room, 'chat_user', $is_unique);

        return $customers;
    }


    /**
     * (获取等待被接入的用户)根据房间号获取被服务对象
     *
     * @param string $room_id     客服房间号
     * @param integer $customer_service_id     客服 id
     * @param string $is_unique     默认过滤重复的数据
     * @return array
     */
    public function getCustomersWaiting($room_id, $is_unique = true)
    {
        // 要接入的客服所在的房间，默认 admin 房间,z这里的客服的状态都是 在线的，如果手动切换为离线，则会被移除该房间
        $room = $this->getRoomName('customer_service_room_waiting', ['room_id' => $room_id]);

        $customers = $this->getSessionsByRoom($room, 'chat_user', $is_unique);

        return $customers;
    }


    /**
     * 通过 session_id 判断顾客是否在线
     *
     * @param string $id     客服 id
     * @return boolean
     */
    public function isOnLineCustomerById($session_id)
    {
        return $this->isUIdOnline($session_id, 'customer');
    }


    /**
     * 通过 session_id 判断 auth 是否在线
     *
     * @param string $id     客服 id
     * @return boolean
     */
    public function isOnlineAuthBySessionId($session_id, $auth)
    {
        return $this->isUIdOnline($session_id, $auth);
    }


    /**
     * 通过 id 获取这个人在 type 下的所有客户端 session 数据
     *
     * @param string $id    要获取的用户的 id
     * @param string $type  bind 类型：user,admin,customer_service 等
     * @param string $name  要获取session 中的特定值，默认全部数据
     * @return array
     */
    public function getSessionsById($id, $type, $name = null)
    {
        $currentClientIds = $this->getClientIdByUId($id, $type);

        $sessionDatas = $this->getSessionByClientIds($currentClientIds, $name);

        return $sessionDatas;
    }



    /**
     * 通过 id 更新 id 绑定的所有客户端的 session
     *
     * @param integer $id
     * @param string $type
     * @param array $data
     * @return void
     */
    public function updateSessionsById($id, $type, $data = [])
    {
        // 当前id 在当前类型下绑定的所有客户端
        $currentClientIds = $this->getClientIdByUId($id, $type);

        $this->updateSessionByClientIds($currentClientIds, $data);
    }


    /**
     * 获取房间中的所有客户端的 session
     * 
     * @param string $room     房间真实名称
     * @param string $name     要取用的 session 中的键名，默认全部取出
     * @param string $is_unique     默认根据绑定的Uid 过滤重复的 session
     * @return array
     */
    public function getSessionsByRoom($room, $name = null, $is_unique = true) {
        // 房间中的所有客服的 clientids
        $roomClientIds = $this->getClientIdsByRoom($room);

        $sessionDatas = $this->getSessionByClientIds($roomClientIds);       // 要过滤重复，没办法直接获取指定数据

        // 处理数据
        $newDatas = [];
        foreach ($sessionDatas as $sessionData) {
            if ($is_unique) {
                // 过滤重复
                $newDatas[$sessionData['session_id']] = $name ? $sessionData[$name] : $sessionData;
            } else {
                // 全部数据
                $newDatas[] = $name ? $sessionData[$name] : $sessionData;
            }
        }

        return array_values(array_filter($newDatas));
    }
}
