<?php

namespace addons\shopro\library\chat\provider\auth\traits;

use addons\shopro\exception\ShoproException;
use addons\shopro\library\chat\traits\DebugEvent;
use addons\shopro\library\chat\traits\Helper;
use addons\shopro\library\chat\traits\Session;

/**
 * 客服事件
 */
trait CustomerService
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


    public function customerServiceOn()
    {

        // 客服登录
        $this->register('customer_service_login', function ($data, $callback) {
            // 加入的客服房间
            $room_id = $data['room_id'] ?? 'admin';
            $this->session('room_id', $room_id);

            // 存储当前房间
            $this->nspRoomIdAdd($room_id);

            // 当前管理员信息
            $auth = $this->session('auth');
            $admin = $this->session('auth_user');

            if (!$this->chatService->customerServiceLogin($room_id, $admin['id'], $auth)) {
                throw new ShoproException('客服登录失败');
            }

            // 注册链接上客服事件
            $this->customerServiceEvent();
            // 客服上线
            $this->customerServiceOnline();

            // 获取客服常用语
            $commonWords = $this->getter('db')->getChatCommonWords($room_id);
            // 通知自己登录成功
            $this->sender->successSocket($callback, '客服登录成功', [
                'customer_service' => $this->session('customer_service'),
                'common_words' => $commonWords
            ]);
        });
    }


    /**
     * 客服的所有事件
     *
     * @return void
     */
    public function customerServiceEvent()
    {
        // 检测是否登录过
        if ($this->socket->listeners('customer_service_init')) {
            // 已经注册过了，避免重复注册 
            return false;
        }
        
        // 客服登录之后的所有事件
        // 客服初始化
        $this->register('customer_service_init', function ($data, $callback) {
            $room_id = $this->session('room_id');
            $customerService = $this->session('customer_service');

            // 获取当前客服正在服务的顾客
            $chatUsers = $this->getter()->getCustomersIngFormatByCustomerService($room_id, $customerService['id']);
            $ingChatUserIds = array_column($chatUsers, 'id');

            // 获取等待中的顾客
            $waitings = $this->getter()->getCustomersFormatWaiting($room_id);

            // 获取客服历史服务过的顾客
            $histories = $this->getter()->getCustomersHistoryFormatByCustomerService($room_id, $customerService['id'], $ingChatUserIds);

            $this->sender->successSocket($callback, '初始化成功', [
                'onlines' => $chatUsers,
                'histories' => $histories,
                'waitings' => $waitings,
            ]);
        });


        // 发送消息
        $this->register('message', function ($data, $callback) {
            $session_id = $data['session_id'] ?? '';        // 接收者
            $message = $data['message'] ?? [];        // 发送的消息
            $customerService = $this->session('customer_service');

            // 给用户发送消息
            $this->sender->messageToCustomer($message, [
                'sender_identify' => 'customer_service',
                'customer_service' => $customerService
            ], $session_id);
            
            // 通知自己发送成功
            $this->sender->successSocket($callback, '发送成功');
        });

        // 获取消息列表
        $this->register('messages', function ($data, $callback) {
            // 当前房间
            $room_id = $this->session('room_id');

            $session_id = $data['session_id'];        // 要获取的顾客

            // 获取 顾客 聊天记录
            $messages = $this->getter('db')->getCustomerMessagesBySessionId($room_id, $session_id, 'customer', $data);

            // 获取消息列表
            $this->sender->successSocket($callback, '获取成功', [
                'messages' => $messages
            ]);
        });


        // 接入用户
        $this->register('access', function ($data, $callback) {
            $session_id = $data['session_id'];        // 要接入的顾客

            $room_id = $this->session('room_id');
            $customerService = $this->session('customer_service');

            // 将当前 用户与 客服绑定
            $this->chatService->bindCustomerServiceBySessionId($room_id, $session_id, $customerService);

            // 将用户 session_id 从等待排名中移除（这个用户的所有客户端都会被接入）
            $this->nspWaitingDel($room_id, $session_id);

            // 排队发生变化，通知房间中所有排队用户
            $this->sender->allWaitingQueue($room_id);

            // 顾客被接入，通知所有自己的客户端被接入，通知房间中所有客服用户被接入(等待中移除)，通知新客服，新用户接入
            $this->sender->customerAccessed($room_id, $session_id, $customerService);

            // 获取消息列表
            $this->sender->successSocket($callback, '接入成功');
        });


        // 转接
        $this->register('transfer', function ($data, $callback) {
            // 要转接的顾客
            $session_id = $data['session_id'] ?? 0;
            // 要转接的客服 id
            $new_customer_service_id = $data['customer_service_id'] ?? 0;
            // 当前客服信息
            $room_id = $this->session('room_id');
            $customerService = $this->session('customer_service');

            if (!$new_customer_service_id) {
                // 没有传入转接客服 id
                throw new ShoproException('请选择要转接的客服');
            }

            // 不能转接给自己
            if ($new_customer_service_id == $customerService['id']) {
                // 不能转接给自己
                throw new ShoproException('您不能转接给自己');
            }
            
            // 获取被转接入的客服, 自动只取客服信息，过滤重复
            $newCustomerService = $this->getter('socket')->getCustomerServiceById($room_id, $new_customer_service_id);

            if (!$newCustomerService) {
                throw new ShoproException('转接的客服不在线');
            }

            // 转接客户,加入新客服，移除老客服
            $this->chatService->transferCustomerServiceBySessionId($room_id, $session_id, $customerService, $newCustomerService);

            // 将用户 session_id 从等待排名中移除（这个用户的所有客户端都会被接入）
            $this->nspWaitingDel($room_id, $session_id);

            // 排队发生变化，通知房间中所有排队用户
            $this->sender->allWaitingQueue($room_id);

            // 顾客被接入，通知所有自己的客户端被接入，通知房间中所有客服用户被接入(等待中移除)，通知新客服，新用户接入
            $this->sender->customerTransfer($room_id, $session_id, $customerService, $newCustomerService);

            // 通知老客服，转接成功
            $this->sender->successSocket($callback, '转接成功');
        });


        // 断开连接中的顾客
        $this->register('break_customer', function ($data, $callback) {
            // 当前客服信息
            $room_id = $this->session('room_id');
            $customerService = $this->session('customer_service');
            // 要断开的顾客
            $session_id = $data['session_id'];

            // 结束并断开客服
            $this->chatService->breakCustomerServiceBySessionId($room_id, $session_id, $customerService);

            // 服务结束，通知顾客客服断开连接
            $this->sender->customerServiceBreak($session_id);

            $this->sender->successSocket($callback, '服务结束成功');
        });


        // 删除历史中的顾客
        $this->register('del_customer', function ($data, $callback) {
            // 当前客服信息
            $room_id = $this->session('room_id');
            $customerService = $this->session('customer_service');
            // 要删除的顾客
            $session_id = $data['session_id'];
            $is_del_record = $data['is_del_record'] ?? false;       // 是否删除聊天记录
            $chatUser = $this->getter('db')->getChatUserBySessionId($session_id);

            if (!$chatUser) {
                throw new ShoproException('删除失败');
            }

            $this->getter('db')->delCustomerByCustomerService($room_id, $chatUser['id'], $customerService, $is_del_record);

            $this->sender->successSocket($callback, '删除成功');
        });


        $this->register('del_customer_all', function ($data, $callback) {
            // 当前客服信息
            $room_id = $this->session('room_id');
            $customerService = $this->session('customer_service');

            $is_del_record = $data['is_del_record'] ?? false;       // 是否删除聊天记录

            $this->getter('db')->delCustomerAllByCustomerService($room_id, $customerService, $is_del_record);

            $this->sender->successSocket($callback, '删除成功');
        });


        // 客服上线
        $this->register('customer_service_online', function ($data, $callback) {
            // 客服操作自己在线状态触发

            // 客服上线
            $this->customerServiceOnline();
            
            // 通知自己上线成功
            $this->sender->successSocket($callback, '当前状态已切换为在线', [
                'customer_service' => $this->session('customer_service')
            ]);
        });


        // 客服离线
        $this->register('customer_service_offline', function ($data, $callback) {
            // 客服操作自己为离线状态触发
            
            // 客服离线
            $this->customerServiceOffline();

            // 通知自己离线成功
            $this->sender->successSocket($callback, '当前状态已切换为离线', [
                'customer_service' => $this->session('customer_service')
            ]);
        });

        // 客服忙碌
        $this->register('customer_service_busy', function ($data, $callback) {
            // 客服操作自己在线状态触发

            // 客服忙碌
            $this->customerServiceBusy();

            // 通知自己离线成功
            $this->sender->successSocket($callback, '当前状态已切换为忙碌', [
                'customer_service' => $this->session('customer_service')
            ]);
        });


        // 客服登录
        $this->register('customer_service_logout', function ($data, $callback) {
            $this->customerServiceLogout();

            // 解绑客服相关的事件，等下次客服再登录时再重新绑定
            $this->socket->removeAllListeners('customer_service_init');
            $this->socket->removeAllListeners('message');
            $this->socket->removeAllListeners('messages');
            $this->socket->removeAllListeners('access');
            $this->socket->removeAllListeners('transfer');
            $this->socket->removeAllListeners('break_customer');
            $this->socket->removeAllListeners('del_customer');
            $this->socket->removeAllListeners('del_customer_all');
            $this->socket->removeAllListeners('customer_service_online');
            $this->socket->removeAllListeners('customer_service_offline');
            $this->socket->removeAllListeners('customer_service_busy');
            $this->socket->removeAllListeners('customer_service_logout');

            $this->sender->successSocket($callback, '退出成功');
        });
    }



    /**
     * 客服上线，并通知连接的用户，和房间中的其他客服
     *
     * @return void
     */
    private function customerServiceOnline() 
    {
        // 房间号
        $room_id = $this->session('room_id');
        // 客服信息
        $customerService = $this->session('customer_service');

        // 记录客服切换之前的在线状态
        $isOnLineCustomerService = $this->getter('socket')->isOnLineCustomerServiceById($customerService['id']);

        // 客服上线，更新客服状态，加入客服组
        $this->chatService->customerServiceOnline($room_id, $customerService['id']);

        // if (!$isOnLineCustomerService) {
            // (这里不判断，都通知，重复通知不影响)如果之前是离线状态

            // 通知连接的用户(在当前客服服务的房间里面的用户)，客服上线了
            $this->sender->customerServiceOnline();

            // 通知当前房间的在线客服，更新当前在线客服列表
            $this->sender->customerServiceUpdate();
            
        // }
    }


    /**
     * 客服离线，并通知连接的用户，和房间中的其他客服
     *
     * @return void
     */
    private function customerServiceOffline()
    {
        // 房间号
        $room_id = $this->session('room_id');
        // 客服信息
        $customerService = $this->session('customer_service');
        // 客服下线，更新客服状态，解绑 client_id，退出客服组
        $this->chatService->customerServiceOffline($room_id, $customerService['id']);

        if (!$this->getter('socket')->isOnLineCustomerServiceById($customerService['id'])) {
            // 当前客服的所有客户端都下线了

            // 通知连接的用户(在当前客服服务的房间里面的用户)，客服下线了
            $this->sender->customerServiceOffline();

            // 通知当前房间的在线客服，更新当前在线客服列表
            $this->sender->customerServiceUpdate();
        }
    }


    /**
     * 客服忙碌，如果之前客服是离线 通知连接的用户，和房间中的其他客服，上线了
     *
     * @return void
     */
    private function customerServiceBusy()
    {
        // 房间号
        $room_id = $this->session('room_id');
        // 客服信息
        $customerService = $this->session('customer_service');

        // 记录客服切换之前的在线状态
        $isOnLineCustomerService = $this->getter('socket')->isOnLineCustomerServiceById($customerService['id']);

        // 客服忙碌，更新客服状态，判断并加入客服组
        $this->chatService->customerServiceBusy($room_id, $customerService['id']);

        // if (!$isOnLineCustomerService) {
            // (这里不判断，都通知，重复通知不影响)如果之前是离线状态

            // 通知连接的用户(在当前客服服务的房间里面的用户)，客服上线了
            $this->sender->customerServiceBusy();

            // 通知当前房间的在线客服，更新当前在线客服列表
            $this->sender->customerServiceUpdate();
        // }
    }


    /**
     * 客服退出登录
     *
     * @return void
     */
    public function customerServiceLogout()
    {
        // 房间号
        $room_id = $this->session('room_id');
        // 客服信息
        $customerService = $this->session('customer_service');
        $customer_service_id = $this->session('customer_service_id');

        // 客服先离线
        $this->chatService->customerServiceOffline($room_id, $customer_service_id);

        if (!$this->getter('socket')->isOnLineCustomerServiceById($customerService['id'])) {
            // 当前客服的所有客户端都下线了
            
            // 通知连接的用户(在当前客服服务的房间里面的用户)，客服下线了
            $this->sender->customerServiceOffline();
            // 通知当前房间的在线客服，更新当前在线客服列表
            $this->sender->customerServiceUpdate();
        }

        // 客服退出房间，清除客服 session 信息
        $this->chatService->customerServiceLogout($room_id, $customer_service_id);

    }
}
