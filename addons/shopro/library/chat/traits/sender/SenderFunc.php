<?php

namespace addons\shopro\library\chat\traits\sender;

use addons\shopro\library\chat\traits\Helper;
use addons\shopro\library\chat\traits\Session;
use addons\shopro\library\chat\traits\NspData;

/**
 * 绑定 uid
 */
trait SenderFunc
{
    use Session;

    use Helper;

    use NspData;

    /**
     * 用户自己触发：发送给自己，登录成功
     *
     * @param \Closure $callback
     * @return void
     */
    public function authSuccess($callback)
    {
        // 通过回调发送给自己
        $this->successSocket($callback, '连接成功', [
            'session_id' => $this->session('session_id'),
            'chat_user' => $this->session('chat_user') ?: null,
        ]);
    }



    /**
     * 用户自己触发：通知所有房间中的客服，用户上线了
     *
     * @return void
     */
    public function customerOnline()
    {
        $session_id = $this->session('session_id');
        $room_id = $this->session('room_id');
        $chatUser = $this->session('chat_user');
        $chatUser = $this->getter()->chatUsersFormat($room_id, [$chatUser], ['first' => true]);

        $this->successRoom('customer_online', '顾客上线', [
            'session_id' => $session_id,
            'chat_user' => $chatUser,
        ], $this->getRoomName('customer_service_room', ['room_id' => $room_id]));
    }


    /**
     * 用户自己触发：通知所有房间中的客服，用户下线了
     *
     * @return void
     */
    public function customerOffline()
    {
        $session_id = $this->session('session_id');
        $room_id = $this->session('room_id');
        $chatUser = $this->session('chat_user');
        $chatUser = $this->getter()->chatUsersFormat($room_id, [$chatUser], ['first' => true]);

        $this->successRoom('customer_offline', '顾客下线', [
            'session_id' => $session_id,
            'chat_user' => $chatUser,
        ], $this->getRoomName('customer_service_room', ['room_id' => $room_id]));
    }


    /**
     * 用户自己触发：通知用户的客户端，排队中，通知客服，有新的等待中用户
     *
     * @param array $waiting    等待中的顾客
     * @return void
     */
    public function waiting()
    {
        $session_id = $this->session('session_id');
        $room_id = $this->session('room_id');

        // 通知客服更新 等待中列表,返回整个等待的列表
        $this->successRoom('customer_waiting', '顾客等待', [
            'waitings' => $this->getter()->getCustomersFormatWaiting($room_id),
        ], $this->getRoomName('customer_service_room', ['room_id' => $room_id]));

        // 通知用户前面排队人数
        $this->waitingQueue($room_id, $session_id);
    }


    /**
     * 用户自己触发，服务器定时触发：通知用户的客户端，当前有客服，但是需要排队 （传参是因为别的地方也需要调用）
     *
     * @param string $room_id
     * @param string $session_id
     * @return void
     */
    public function waitingQueue($room_id, $session_id)
    {
        $rank = $this->nspWaitingRank($room_id, $session_id);

        $title = $rank > 0 ? '当前还有 ' . $rank . ' 位顾客，请耐心等待' : '客服马上为您服务，请稍等';

        // 通知用户等待接入
        $this->successUId('waiting_queue', '排队等待', [
            'title' => $title,
        ], ['id' => $session_id, 'type' => 'customer']);
    }



    /**
     * 当有变动，批量通知排队等待排名
     *
     * @param string|null $room_id
     * @return void
     */
    public function allWaitingQueue($room_id = null) 
    {
        $waitings = $this->nspGetWaitings($room_id);

        if ($room_id) {
            // 只处理该房间的
            if ($this->getter->driver('socket')->hasCustomerServiceByRoomId($room_id)) {
                foreach ($waitings as $key => $session_id) {
                    $this->waitingQueue($room_id, $session_id);
                }
            }
        } else {
            // 定时器调用， 处理所有房间的
            foreach ($waitings as $current_room_id => $roomWaitings) {
                // 判断是否有客服在线
                if ($this->getter->driver('socket')->hasCustomerServiceByRoomId($current_room_id)) {
                    foreach ($roomWaitings as $key => $session_id) {
                        $this->waitingQueue($current_room_id, $session_id);
                    }
                }
            }
        }
    }


    /**
     * 用户自己触发：通知用户的客户端，当前没有客服在线
     *
     * @return void
     */
    public function noCustomerService()
    {
        $session_id = $this->session('session_id');

        $this->successUId('no_customer_service', '暂无客服在线', [
            'message' => [
                'message_type' => 'system',
                'message' => '当前客服不在线',
                'createtime' => time()
            ],
        ], ['id' => $session_id, 'type' => 'customer']);
    }


    /**
     * 给客服发消息
     *
     * @param array $message         消息原始内容
     * @param array $sender          发送者信息
     * @param integer $customer_service_id  客服 id
     * @return void
     */
    public function messageToCustomerService($message, $sender, $customer_service_id) 
    {
        // 给客服发送消息
        $this->messageUId('message', '收到消息', [
            'message' => $message,
            'sender' => $sender
        ], ['id' => $customer_service_id, 'type' => 'customer_service']);
    }


    /**
     * 给顾客发消息
     *
     * @param array $message         消息原始内容
     * @param array $sender          发送者信息
     * @param integer $session_id    顾客 session_id
     * @return void
     */
    public function messageToCustomer($message, $sender, $session_id) 
    {
        // 给用户发送消息
        $this->messageUId('message', '收到消息', [
            'message' => $message,
            'sender' => $sender
        ], ['id' => $session_id, 'type' => 'customer']);
    }



    /**
     * 同时通知客服和顾客
     *
     * @param array $question 要回答的问题
     * @param string $session_id 用户表示
     * @param string $customer_service_id 客服
     * @return void
     */
    public function messageToBoth($question, $session_id, $customer_service_id)
    {
        $customerService = $this->session('customer_service');
        $chatUser = $this->session('chat_user');

        $message = [
            'message_type' => 'text',
            'message' => $question['content'],
        ];
        $sender = [
            'sender_identify' => 'customer_service',
            'customer_service' => $customerService,
        ];
        // 发给用户
        $this->messageUId('message', '收到消息', [
            'message' => $message,
            'sender' => $sender
        ], ['id' => $session_id, 'type' => 'customer']);

        // 发给客服
        if ($customer_service_id) {
            // 有客服，发给客服
            $message['sender_identify'] = 'customer_service';
            $message['sender_id'] = $customerService['id'];
            $message['sender'] = $customerService;
            $message['createtime'] = time();
            $sender['session_id'] = $session_id;
            $this->successUId('message', '收到消息', [
                'message' => $message,
                'sender' => $sender
            ], ['id' => $customer_service_id, 'type' => 'customer_service']);
        }
    }


    /**
     * 通知连接的用户(在当前客服服务的房间里面的用户)，客服上线了
     *
     * @return void
     */
    public function customerServiceOnline() 
    {
        $room_id = $this->session('room_id');
        $customerService = $this->session('customer_service');

        // 给客服发送消息
        $this->successRoom('customer_service_online', '客服 ' . $customerService['name'] . ' 上线', [
            'customer_service' => $customerService,
        ], $this->getRoomName('customer_service_room_user', ['room_id' => $room_id, 'customer_service_id' => $customerService['id']]));
    }


    /**
     * 通知连接的用户(在当前客服服务的房间里面的用户)，客服忙碌
     *
     * @return void
     */
    public function customerServiceBusy() 
    {
        $room_id = $this->session('room_id');
        $customerService = $this->session('customer_service');

        // 给客服发送消息
        $this->successRoom('customer_service_busy', '客服 ' . $customerService['name'] . ' 忙碌', [
            'customer_service' => $customerService,
        ], $this->getRoomName('customer_service_room_user', ['room_id' => $room_id, 'customer_service_id' => $customerService['id']]));
    }

    /**
     * 通知连接的用户(在当前客服服务的房间里面的用户)，客服下线了
     *
     * @return void
     */
    public function customerServiceOffline() 
    {
        $room_id = $this->session('room_id');
        $customerService = $this->session('customer_service');

        $this->successRoom('customer_service_offline', '客服 ' . $customerService['name'] . ' 离线', [
            'customer_service' => $customerService,
        ], $this->getRoomName('customer_service_room_user', ['room_id' => $room_id, 'customer_service_id' => $customerService['id']]));
    }


    /**
     * 通知当前房间的在线客服，更新当前在线客服列表
     *
     * @return void
     */
    public function customerServiceUpdate() 
    {
        $room_id = $this->session('room_id');

        // 给客服发送消息
        $customerServices = $this->getter('socket')->getCustomerServicesByRoomId($room_id);
        $this->successRoom('customer_service_update', '更新客服列表', [
            'customer_services' => $customerServices,
        ], $this->getRoomName('customer_service_room', ['room_id' => $room_id]));
    }


    /**
     * 服务结束，通知顾客客服断开连接
     *
     * @param string $session_id
     * @return void
     */
    public function customerServiceBreak($session_id) 
    {
        $this->successUId('customer_service_break', '客服断开', [
            'message' => [
                'message_type' => 'system',
                'message' => '服务已结束',
                'createtime' => time()
            ],
        ], ['id' => $session_id, 'type' => 'customer']);
    }



    /**
     * 客服转接 
     *
     * @param string $room_id       房间号
     * @param string $session_id    用户 UId
     * @param array $customerService    老客服
     * @param array $newCustomerService  新客服
     * @return void
     */
    public function customerTransfer($room_id, $session_id, $customerService, $newCustomerService) 
    {
        // 通知用户客服被转接
        $this->customerAccessedToCustomer($session_id, $customerService, $newCustomerService);

        // 通知所有客服用户被接入
        $this->customerAccessedToCustomerServices($room_id, $session_id, $newCustomerService);

        // 通知新的客服，新用户接入
        $this->customerAccessedToCustomerService($room_id, $session_id, $newCustomerService);
    }



    /**
     * 客服接入 
     *
     * @param string $room_id       房间号
     * @param string $session_id    用户 UId
     * @param array $customerService    老客服
     * @return void
     */
    public function customerAccessed($room_id, $session_id, $customerService)
    {
        // 通知用户客服接入
        $this->customerAccessedToCustomer($session_id, $customerService);

        // 通知所有客服用户被接入
        $this->customerAccessedToCustomerServices($room_id, $session_id, $customerService);

        // 通知新的客服，新用户接入
        $this->customerAccessedToCustomerService($room_id, $session_id, $customerService);
    }



    /**
     * 通知顾客 客服接入
     *
     * @param string $session_id    用户 UId
     * @param array $customerService    老客服
     * @param array $newCustomerService  新客服
     * @return void
     */
    private function customerAccessedToCustomer($session_id, $customerService, $newCustomerService = null)
    {
        // 通知当前用户的所有客户端，客服接入
        $message = '您好，客服 ' . $customerService['name'] . " 为您服务";
        if ($newCustomerService) {
            $message = '您好，您的客服已由 ' . $customerService['name'] . " 切换为 " . $newCustomerService['name'];
        }

        $this->successUId('customer_service_access', '客服接入', [
            'message' => [
                'message_type' => 'system',
                'message' => $message,
                'createtime' => time()
            ],
            'customer_service' => $newCustomerService ? : $customerService
        ], ['id' => $session_id, 'type' => 'customer']);
    }



    /**
     * 通知所有客服，用户被接入
     *
     * @param string $room_id       房间号
     * @param string $session_id    用户 UId
     * @param array $customerService    老客服
     * @return void
     */
    private function customerAccessedToCustomerServices($room_id, $session_id, $customerService) 
    {
        // 通知所有客服，用户被接入
        $this->successRoom('customer_accessed', '顾客被接入', [
            'session_id' => $session_id,
            'chat_user' => $this->getter('db')->getChatUserBySessionId($session_id),
            'customer_service' => $customerService,
        ], $this->getRoomName('customer_service_room', ['room_id' => $room_id]));
    }


    /**
     * 通知被接入客服，新用户接入
     *
     * @param [type] $session_id
     * @param [type] $customerService
     * @return void
     */
    private function customerAccessedToCustomerService($room_id, $session_id, $customerService)
    {
        // 获取chatUser
        $chatUser = $this->getter('db')->getChatUserBySessionId($session_id);
        // 格式化chatUser
        $chatUser = $this->getter()->chatUsersFormat($room_id, [$chatUser], ['first' => true]);

        // 通知新的客服，新用户接入
        $this->successUId('customer_access', '新顾客接入', [
            'session_id' => $session_id,
            'chat_user' => $chatUser,
        ], ['id' => $customerService['id'], 'type' => 'customer_service']);
    }
}
