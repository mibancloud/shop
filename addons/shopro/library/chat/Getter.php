<?php

namespace addons\shopro\library\chat;

use addons\shopro\exception\ShoproException;
use addons\shopro\library\chat\traits\Helper;
use addons\shopro\library\chat\traits\Session;
use addons\shopro\library\chat\traits\NspData;
use addons\shopro\library\chat\provider\getter\Db;
use addons\shopro\library\chat\provider\getter\Socket;
use PHPSocketIO\SocketIO;
use PHPSocketIO\Socket as PhpSocket;
use PHPSocketIO\Nsp;

/**
 * 客服 getter 获取各种用户类
 */
class Getter
{

    /**
     * session 存储助手
     */
    use Session;
    /**
     * helper 助手
     */
    use Helper;
    /**
     * 在 nsp 上存储数据
     */
    use NspData;

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

    /**
     * 数据驱动
     *
     * @var string
     */
    protected $driver = null;

    protected $dbProvider;
    protected $socketProvider;

    public function __construct(PhpSocket $socket = null, SocketIo $io, Nsp $nsp)
    {
        $this->socket = $socket;
        $this->io = $io;
        $this->nsp = $nsp;
    }


    /**
     * 设置 getter 驱动
     *
     * @param string $driver
     * @return self
     */
    public function driver($driver) {
        $this->driver = $driver;
        return $this;
    }



    /**
     * 通过auth id 获取 session_id
     *
     * @param string $auth_id
     * @param string $auth
     * @return string|null
     */
    public function getSessionIdByAuth($auth_id, $auth)
    {
        $chatUser = $this->driver('db')->getChatUserByAuth($auth_id, $auth);
        return $chatUser->session_id ?? null;
    }




    /**
     * 更新客服信息，数据库和session 都更新
     *
     * @param integer $id   要更新的 客服id
     * @param array $data   要更新的数据
     * @return void
     */
    public function updateCustomerServiceInfo($id, $data) 
    {
        // 更新当前客服的所有连接，比如 last_time
        $this->driver('socket')->updateSessionsById($id, 'customer_service', ['customer_service' => $data]);
        $this->driver('db')->updateCustomerService($id, $data);
    }


    /**
     * 更新客服信息，数据库和session 都更新
     *
     * @param integer $id   要更新的 客服id
     * @param string $status   状态 online|offline|busy
     * @return void
     */
    public function updateCustomerServiceStatus($id, $status) 
    {
        $data = ['status' => $status];
        // 只更新当前连接，别的当前客服的连接不更新,比如 status
        $this->updateSessionByClientId($this->socket->id, ['customer_service' => $data]);

        // 不是 (改为离线，并且还有当前客服的其他链接在线)，则修改数据库状态
        if (!($status == 'offline' && $this->driver('socket')->isOnLineCustomerServiceById($id))) {
            $this->driver('db')->updateCustomerService($id, $data);
        }
    }



    /**
     * 更新客服忙碌杜
     *
     * @return void
     */
    public function updateCustomerServiceBusyPercent() 
    {
        // 所有客服的房间
        $roomIds = $this->nspData('room_ids');
        $roomIds = $roomIds ? : [];

        foreach ($roomIds as $room_id) {
            // 当前房间在线客服组的所有客户端
            $customer_service_room = $this->getRoomName('customer_service_room', ['room_id' => $room_id]);
            $clientIds = $this->driver('socket')->getClientIdsByRoom($customer_service_room);

            foreach ($clientIds as $client_id) {
                $customerService = $this->getSession($client_id, 'customer_service');

                if ($customerService) {
                    // 客服服务的对应用户的房间名
                    $customer_service_room_user = $this->getRoomName('customer_service_room_user', ['room_id' => $room_id, 'customer_service_id' => $customerService['id']]);
                    $customerService['current_num'] = count($this->driver('socket')->getSessionsByRoom($customer_service_room_user, 'session_id'));       // 真实用户数量，不是客户端数量
                    $max_num = $customerService['max_num'] > 0 ? $customerService['max_num'] : 1;       // 避免除数为 0 
                    $customerService['busy_percent'] = $customerService['current_num'] / $max_num;

                    // 只更新当前连接，别的当前客服的连接不更新,比如 status
                    $this->updateSessionByClientId($client_id, ['customer_service' => $customerService]);
                }
            }
        }
    }
    


    /**
     * 获取客服服务中的用户
     *
     * @param integer $room_id  房间号
     * @param integer $customer_service_id  客服 id
     * @param array $exceptIds  要排除的ids （正在服务的用户）
     * @return array
     */
    public function getCustomersIngFormatByCustomerService($room_id, $customer_service_id)
    {
        // 获取数据库历史
        $ings = $this->driver('socket')->getCustomersIngByCustomerService($room_id, $customer_service_id);

        // 格式化数据
        $ings = $this->chatUsersFormat($room_id, $ings);

        return $ings;
    }


    /**
     * 获取等待中的用户
     *
     * @param integer $room_id  房间号
     * @param integer $customer_service_id  客服 id
     * @param array $exceptIds  要排除的ids （正在服务的用户）
     * @return array
     */
    public function getCustomersFormatWaiting($room_id)
    {
        // 获取数据库历史
        $waitings = $this->driver('socket')->getCustomersWaiting($room_id);

        // 格式化数据
        $waitings = $this->chatUsersFormat($room_id, $waitings);

        return $waitings;
    }


    /**
     * 获取客服服务的历史用户
     *
     * @param integer $room_id  房间号
     * @param integer $customer_service_id  客服 id
     * @param array $exceptIds  要排除的ids （正在服务的用户）
     * @return array
     */
    public function getCustomersHistoryFormatByCustomerService($room_id, $customer_service_id, $exceptIds = [])
    {
        // 获取数据库历史
        $histories = $this->driver('db')->getCustomersHistoryByCustomerService($room_id, $customer_service_id, $exceptIds = []);

        // 格式化数据
        $histories = $this->chatUsersFormat($room_id, $histories, ['select_identify' => 'customer', 'is_customer_service' => true]);

        return $histories;
    }



    /**
     * 设置最后一条消息和未读消息数
     *
     * @param string $room_id
     * @param array $chatUsers
     * @param string $select_identify 查询对象，默认客服查用户的，用户查客服的
     * @return array
     */
    public function chatUsersFormat($room_id, $chatUsers, $params = [])
    {
        $select_identify = $params['select_identify'] ?? 'customer';
        $is_customer_service = $params['is_customer_service'] ?? false;
        $first = $params['first'] ?? false;

        foreach ($chatUsers as &$chatUser) {
            // 获取在线状态
            $status = $this->driver('socket')->isUIdOnline($chatUser['session_id'], 'customer');
            $chatUser['status'] = $status;           // 在线状态

            // 最后一条消息，未读消息条数
            $chatUser['last_message'] = $this->driver('db')->getMessageLastByChatUser($room_id, $chatUser['id']);
            $chatUser['unread_num'] = $this->driver('db')->getMessageUnReadNumByChatUserAndIndentify($room_id, $chatUser['id'], $select_identify);

            // 当前的客服
            $chatUser['customer_service'] = null;
            if ($is_customer_service) {     // 需要客户的客服信息，【历史用户中】
                $chatUser['customer_service'] = $this->driver('socket')->getCustomerServiceByCustomerSessionId($chatUser['session_id']);    // 如果在线，并且已经接入客服，当前客服信息
            }
        }

        return $first ? current($chatUsers) : $chatUsers;
    }

    /**
     * auth 实例方法注入
     *
     * @return Db|Socket
     */
    public function provider()
    {
        $classProt = $this->driver . 'Provider';

        if ($this->$classProt) {
            return $this->$classProt;
        }

        $class = "\\addons\\shopro\\library\\chat\\provider\\getter\\" . ucfirst($this->driver);

        if (class_exists($class)) {
            $this->$classProt = new $class($this, $this->socket, $this->io, $this->nsp);

            return $this->$classProt;
        }

        throw new ShoproException('getter 驱动不支持');
    }


    /**
     * 静态调用
     *
     * @param string $funcname
     * @param array $arguments
     * @return mixed
     */
    public function __call($funcname, $arguments)
    {
        return $this->provider()->{$funcname}(...$arguments);
    }
}
