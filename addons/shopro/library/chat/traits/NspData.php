<?php

namespace addons\shopro\library\chat\traits;

/**
 * 在 nsp 连接实例绑定数据
 */
trait NspData
{
    // data 的格式
    // 'data' => [
    //     'room_ids' => ['admin']      // 客服房间数组，目前只有 admin
    //     'session_ids' => ['user' => [1,2,3], 'admin' => [1,2,3]]          // 登录的身份的 ids
    //     'connections' => [
    //          room_id => [
    //              'session_id' => customer_service_id
    //          ]
    //      ],
    //     'waitings' => [
    //         'room_id' => [排队的用户]
    //     ]
    // ]


    /**
     * 获取存储的数据，当前 Nsp
     * 
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function nspData($name = null, $value = '')
    {
        $data = $this->nsp->nspData ?? [];

        if (is_null($name)) {
            // 获取全部数据
            return $data;
        }

        if ('' === $value) {
            // 获取缓存
            return 0 === strpos($name, '?') ? isset($data[$name]) : ($data[$name] ?? null);
        } elseif (is_null($value)) {
            // 删除缓存
            unset($data[$name]);
            $this->nsp->nspData = $data;
        }

        // 存数据
        $data[$name] = $value;
        $this->nsp->nspData = $data;
    }



    /**
     * 获取等待中的排队，如果有房间，返回当前房间的
     *
     * @param string $room_id
     * @return array
     */
    // public function nspGetSessionIds($auth)
    // {
    //     $sessionIds = $this->nspData('session_ids');
    //     $sessionIds = $sessionIds ?: [];

    //     return $auth ? $sessionIds[$auth] ?? [] : $sessionIds;
    // }



    /**
     * 保存当前在线的 auth id
     *
     * @param integer $session_id
     * @return void
     */
    // public function nspSessionIdAdd($session_id, $auth)
    // {
    //     $sessionIds = $this->nspData('session_ids');
    //     $sessionIds = $sessionIds ?: [];

    //     // 当前 auth 的 ids
    //     $currentsessionIds = $sessionIds[$auth] ?? [];

    //     // 已经存在直接忽略
    //     if (!in_array($session_id, $currentsessionIds)) {
    //         // 追加auth
    //         $currentsessionIds[] = $session_id;

    //         $sessionIds[$auth] = $currentsessionIds;
    //         $this->nspData('session_ids', $sessionIds);
    //     }
    // }



    /**
     * 删除一个 auth id (离线了)
     *
     * @param integer $session_id
     * @return void
     */
    // public function nspSessionIdDel($session_id, $auth)
    // {
    //     $sessionIds = $this->nspData('session_ids');
    //     $sessionIds = $sessionIds ?: [];

    //     // 当前 auth 的 ids
    //     $currentsessionIds = $sessionIds[$auth] ?? [];

    //     $key = array_search($session_id, $currentsessionIds);
    //     if ($key !== false) {
    //         // 移除
    //         unset($currentsessionIds[$key]);

    //         // 重新赋值
    //         $sessionIds[$auth] = array_values($currentsessionIds);

    //         $this->nspData('session_ids', $sessionIds);
    //     }
    // }



    /**
     * 获取顾客的连接客服（这个信息只会存 10s，防止用户刷新，重新分配客服问题）
     *
     * @param string $room_id
     * @return array
     */
    public function nspGetConnectionCustomerServiceId($room_id, $session_id)
    {
        $connections = $this->nspData('connections');
        $connections = $connections ?: [];

        // 当前房间的 waitings
        $roomConnections = $connections[$room_id] ?? [];

        $data_str = $roomConnections[$session_id] ?? '';
        if ($data_str) {
            // 删除 connections 
            unset($roomConnections[$session_id]);
            $connections[$room_id] = $roomConnections;
            $this->nspData('connections', $connections);

            // 获取 客服 id
            $data = explode('-', $data_str);
            if ($data[0] >= (time() - 300)) {
                return $data[1] ?? null;
            }
        }

        return null;
    }



    /**
     * 记录用户的服务客服
     *
     * @param string $room_id
     * @param string $session_id
     * @param string $customer_service_id
     * @return void
     */
    public function nspConnectionAdd($room_id, $session_id, $customer_service_id)
    {
        // 所有 waitings
        $connections = $this->nspData('connections');
        $connections = $connections ?: [];

        // 当前房间的 waitings
        $roomConnections = $connections[$room_id] ?? [];

        if (!in_array($session_id, $roomConnections)) {
            // 将 session_id 和对应的 客服 id 关联
            $roomConnections[$session_id] = time() . '-' . $customer_service_id;
            // 重新赋值
            $connections[$room_id] = $roomConnections;
            // 保存
            $this->nspData('connections', $connections);
        }
    }


    /**
     * 记录要创建的 客服 room 房间
     *
     * @param string $room_id
     * @return void
     */
    public function nspRoomIdAdd($room_id)
    {
        $roomIds = $this->nspData('room_ids');
        $roomIds = $roomIds ?: [];

        // 已经存在直接忽略
        if (!in_array($room_id, $roomIds)) {
            // 追加房间
            $roomIds[] = $room_id;
            $this->nspData('room_ids', $roomIds);
        }
    }


    /**
     * 获取等待中的排队，如果有房间，返回当前房间的
     *
     * @param string $room_id
     * @return array
     */
    public function nspGetWaitings($room_id = null) 
    {
        $waitings = $this->nspData('waitings');
        $waitings = $waitings ?: [];

        return $room_id ? $waitings[$room_id] ?? [] : $waitings;
    }



    /**
     * 给对应房间的排队中追加顾客，如果已经存在，忽略
     *
     * @param string $room_id
     * @param string $session_id
     * @return void
     */
    public function nspWaitingAdd($room_id, $session_id) 
    {
        // 所有 waitings
        $waitings = $this->nspData('waitings');
        $waitings = $waitings ? : [];

        // 当前房间的 waitings
        $roomWaitings = $waitings[$room_id] ?? [];

        if (!in_array($session_id, $roomWaitings)) {
            // 将 session_id 加入 房间 waitings，如果存在，忽略
            $roomWaitings[] = $session_id;
            // 重新赋值
            $waitings[$room_id] = $roomWaitings; 
            // 保存
            $this->nspData('waitings', $waitings);
        }
    }



    /**
     * 删除对应房间中排队的顾客，如果不存在忽略
     *
     * @param string $room_id
     * @param string $session_id
     * @return void
     */
    public function nspWaitingDel($room_id, $session_id)
    {
        // 所有 waitings
        $waitings = $this->nspData('waitings');
        $waitings = $waitings ?: [];

        // 当前房间的 waitings
        $roomWaitings = $waitings[$room_id] ?? [];

        $key = array_search($session_id, $roomWaitings);
        if ($key !== false) {
            // 移除
            unset($roomWaitings[$key]);

            // 重新赋值
            $waitings[$room_id] = array_values($roomWaitings);

            $this->nspData('waitings', $waitings);
        }
    }


    /**
     * 获取在房间中的排名
     *
     * @param string $room_id
     * @param string $session_id
     * @return integer
     */
    public function nspWaitingRank($room_id, $session_id)
    {
        // 所有 waitings
        $waitings = $this->nspData('waitings');
        $waitings = $waitings ?: [];

        // 当前房间的 waitings
        $roomWaitings = $waitings[$room_id] ?? [];

        // 获取 session_id 的下标，就是当前顾客前面还有多少位 顾客
        $key = array_search($session_id, $roomWaitings);

        return $key !== false ? $key : 0;
    }
}
