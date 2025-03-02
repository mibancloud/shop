<?php

namespace addons\shopro\library\chat\traits;

/**
 * 客服 session 工具类
 */
trait Session
{

    /**
     * 获取存储的数据，当前 socket
     * 
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function session($name = null, $value = '')
    {
        $data = $this->socket->session ?? [];

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
            $this->socket->session = $data;
        }

        // 存数据
        $data[$name] = $value;
        $this->socket->session = $data;
    }


    /**
     * 通过 client_id 获取指定 client_id 的 session 数据
     *
     * @param string $client_id 要获取 session 的 client_id
     * @param string $name 要获取 session 中的特定 name
     * @return string|array
     */
    public function getSession($client_id, $name = null)
    {
        $client = $this->nsp->sockets[$client_id] ?? null;

        $session = is_null($client) ? null : (isset($client->session) && $client->session ? $client->session : []);

        return $name ? ($session[$name] ?? null) : $session;
    }


    /**
     * 调用 getSession，别名
     *
     * @param string $client_id 要获取 session 的 client_id
     * @param string $name 要获取 session 中的特定 name
     * @return string|array
     */
    public function getSessionByClientId($client_id, $name = null)
    {
        return $this->getSession($client_id, $name);
    }


    /**
     * 通过 client_ids 获取指定 client_ids 的 session 数据集合
     *
     * @param string $client_id 要获取 session 的 client_id
     * @param string $name 要获取 session 中的特定 name
     * @return array
     */
    public function getSessionByClientIds($clientIds, $name = null)
    {
        $customerServices = [];
        foreach ($clientIds as $client_id) {
            $currentCustomerService = $this->getSessionByClientId($client_id, $name);
            if ($currentCustomerService) {
                $customerServices[] = $currentCustomerService;
            }
        }

        return $customerServices;
    }



    /**
     * 通过 client_id 更新指定 client_id 的 session 数据
     *
     * @param string $client_id 要获取 session 的 client_id
     * @param array $data 要更新 session 中的特定 name
     * @return boolean
     */
    public function updateSession($client_id, $data = [])
    {
        $client = $this->nsp->sockets[$client_id] ?? null;

        if (!$client) {
            // client 没有，可能下线了,直接返回
            return false;
        }

        // session 不存在，初始化
        if (!isset($client->session)) {
            $this->nsp->sockets[$client_id]->session = [];
        }

        // 更新对应的键
        foreach ($data as $key => $value) {
            $current = $this->nsp->sockets[$client_id]->session[$key] ?? null;
            if (is_array($value) && $current) {
                // 合并数组，比如修改 session 中 customer_service 客服信息 中的 status 在线状态(注意，如果value 为空，则覆盖，如果有值，则合并)
                $this->nsp->sockets[$client_id]->session[$key] = $value ? array_merge($current, $value) : $value;
            } else {
                $this->nsp->sockets[$client_id]->session[$key] = $value;
            }
        }

        return true;
    }


    /**
     * 调用 updateSession，别名
     *
     * @param string $client_id 要获取 session 的 client_id
     * @param array $data 要更新 session 中的特定 name
     * @return boolean
     */
    public function updateSessionByClientId($client_id, $data = [])
    {
        return $this->updateSession($client_id, $data);
    }


    /**
     * 通过 client_id 更新指定 client_id 的 session 数据集合
     *
     * @param string $client_id 要获取 session 的 client_id
     * @param string $name 要获取 session 中的特定 name
     * @return array
     */
    public function updateSessionByClientIds($clientIds, $data = [])
    {
        foreach ($clientIds as $client_id) {
            $this->updateSessionByClientId($client_id, $data);
        }

        return true;
    }
}
