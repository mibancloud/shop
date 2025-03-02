<?php

namespace addons\shopro\library\chat\provider\auth\traits;

/**
 * 顾客事件
 */
trait Customer
{
    public function customerOn()
    {
        // 顾客连接客服事件
        $this->register('customer_login', function ($data, $callback) {
            // 加入的客服房间
            $room_id = $data['room_id'] ?? 'admin';
            $this->session('room_id', $room_id);

            // 存储当前房间
            $this->nspRoomIdAdd($room_id);

            // 顾客上线
            $this->chatService->customerOnline();

            // 通知所有房间中的客服，用户上线了
            $this->sender->customerOnline();

            // 注册顾客相关的事件
            $this->customerEvent();

            // 获取常见问题，提醒给顾客
            $questions = $this->getter('db')->getChatQuestions($room_id);
            // 通知自己连接成功
            $this->sender->successSocket($callback, '顾客初始成功', [
                'questions' => $questions
            ]);

            // 分配客服
            $this->allocatCustomerService();
        });
    }


    public function customerEvent()
    {
        // 检测是否登录过
        if ($this->socket->listeners('message')) {
            // 已经注册过了，避免重复注册 
            return false;
        }

        // 发送消息
        $this->register('message', function ($data, $callback) {
            $message = $data['message'] ?? [];        // 发送的消息
            $question_id = $data['question_id'] ?? 0;      // 猜你想问
            // 过滤 message
            if ($message['message_type'] == 'text') {
                $message['message'] = trim(strip_tags($message['message']));
            }

            $room_id = $this->session('room_id');
            $session_id = $this->session('session_id');
            $customer_service_id = $this->session('customer_service_id');
            
            // 给客服发消息
            $this->sender->messageToCustomerService($message, [
                'sender_identify' => 'customer',
                'session_id' => $session_id,
            ], $customer_service_id);
            
            // 通知自己发送成功
            $this->sender->successSocket($callback, '发送成功');
            
            // 检查并获取 question
            $question = $this->getter('db')->getChatQuestion($room_id, $question_id);
            if ($question) {
                // 是猜你想问，自动回答问题
                $this->sender->messageToBoth($question, $session_id, $customer_service_id);
            }
        });


        // 获取消息列表
        $this->register('messages', function ($data, $callback) {
            // 当前房间
            $room_id = $this->session('room_id');
            $session_id = $this->session('session_id');

            // 获取 聊天记录
            $messages = $this->getter('db')->getCustomerMessagesBySessionId($room_id, $session_id, 'customer_service', $data);

            // 获取消息列表
            $this->sender->successSocket($callback, '获取成功', [
                'messages' => $messages
            ]);
        });

        // 顾客退出
        $this->register('customer_logout', function ($data, $callback) {
            $session_id = $this->session('session_id');
            $room_id = $this->session('room_id');
            $chatUser = $this->session('chat_user');
            
            // 顾客下线
            $this->chatService->customerOffline();

            // 顾客断开连接
            if (!$this->getter('socket')->isOnLineCustomerById($session_id)) {
                // 当前所遇用户端都断开了

                // 这里顾客的所有客户端都断开了，在排队排名中移除
                $this->nspWaitingDel($room_id, $session_id);

                // 排队发生变化，通知房间中所有排队用户
                $this->sender->allWaitingQueue($room_id);

                // 通知所有房间中的客服，顾客下线
                $this->sender->customerOffline();
            }

            // 解绑顾客相关的事件，等下次顾客在登录时再重新绑定 【customerEvent 方法绑定的所有事件】
            $this->socket->removeAllListeners('message');
            $this->socket->removeAllListeners('messages');
            $this->socket->removeAllListeners('customer_logout');

            $this->sender->successSocket($callback, '顾客退出成功');
        });
    }


    /**
     * 分配客服
     *
     * @return void
     */
    private function allocatCustomerService() 
    {
        $room_id = $this->session('room_id');
        $session_id = $this->session('session_id');
        $auth = $this->session('auth');

        // 检测并分配客服
        $customerService = $this->chatService->checkAndAllocatCustomerService();
        
        if ($customerService) {
            // 将用户 session_id 从等待排名中移除（这个用户的所有客户端都会被接入）
            $this->nspWaitingDel($room_id, $session_id);

            // 排队发生变化，通知房间中所有排队用户
            $this->sender->allWaitingQueue($room_id);

            // 顾客被接入，通知所有自己的客户端被接入，通知房间中所有客服用户被接入(等待中移除)，通知新客服，新用户接入
            $this->sender->customerAccessed($room_id, $session_id, $customerService);
        } else {
            if ($this->getter('socket')->hasCustomerServiceByRoomId($room_id)) {
                // 有客服
                $this->sender->waiting();
            } else {
                // 通知用户没有客服在线
                $this->sender->noCustomerService();
            }
        }
    }

}
