<?php

namespace addons\shopro\library\chat\traits;

/**
 * 绑定 uid
 */
trait BindUId
{

    /**
     * 绑定类型
     */
    protected $bindType = [
        'user',                 // 用户
        'admin',                // 管理员
        'customer',             // 顾客 (用户或者管理员的身份提升)
        'customer_service'      // 客服 (用户或者管理员的身份提升)
    ];


    /**
     * 根据类型获取 uid
     *
     * @param string $id
     * @param string $type 目前有三种， user,admin,customer_service
     * @return string|array
     */
    public function getUId($id, $type)
    {
        $ids = is_array($id) ? $id : [$id];
        foreach ($ids as &$i) {
            $i = $type . '-' . $i;
        }

        return is_array($id) ? $ids : $ids[0];
    }


    /**
     * 绑定 uid
     * 
     * @param string $id
     * @param string $type
     * @return bool
     */
    public function bindUId($id, $type)
    {
        $uId = $this->getUId($id, $type);

        // 当前客户端标示
        $client_id = $this->socket->id;

        $this->nsp->bind[$uId][$client_id] = $client_id;

        return true;
    }


    /**
     * 通过 uid 获取当前 uid 绑定的所有clientid
     *
     * @param string $id
     * @param string $type
     * @return array
     */
    public function getClientIdByUId($id, $type) {
        $uId = $this->getUId($id, $type);

        return $this->nsp->bind[$uId] ?? [];
    }


    /**
     * 解绑 uid，将当前客户端与当前 uid 解绑，如果 uid 下没有客户端了，则将该 uid 删除
     *
     * @param string $id
     * @param string $type
     * @return void
     */
    public function unbindUId($id, $type)
    {
        $uId = $this->getUId($id, $type);

        // 当前客户端标示
        $client_id = $this->socket->id;

        if (isset($this->nsp->bind[$uId][$client_id])) {
            unset($this->nsp->bind[$uId][$client_id]);

            if (!$this->nsp->bind[$uId]) {
                unset($this->nsp->bind[$uId]);
            }
        }

        return true;
    }


    /**
     * UId 是否在线
     *
     * @param string $id
     * @param string $type
     * @return boolean
     */
    public function isUIdOnline($id, $type)
    {
        $uId = $this->getUId($id, $type);

        if (isset($this->nsp->bind[$uId]) && $this->nsp->bind[$uId]) {
            return true;
        }

        return false;
    }
}
