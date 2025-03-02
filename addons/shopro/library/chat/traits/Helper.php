<?php

namespace addons\shopro\library\chat\traits;

use addons\shopro\exception\ShoproException;
use addons\shopro\library\chat\Getter;

/**
 * 助手方法，需要全局地方可用
 */
trait Helper
{

    /**
     * 客服配置
     *
     * @var array
     */
    protected $chatConfig;


    /**
     * 获取客服配置
     *
     * @param string $name      要获取的配置项，不能获取第二级的配置项
     * @return void
     */
    public function getConfig($name = null)
    {
        // 初始化 workerman 的时候不能读取数据库，会导致数据库连接异常
        if (!$this->chatConfig) {
            $config_path = ROOT_PATH . 'application' . DS . 'extra' . DS . 'chat.php';
            if (!file_exists($config_path)) {
                throw new ShoproException('客服配置文件不存在，请在后台->商城配置->客服配置，修改并保存客服配置');
            }

            $this->chatConfig = require($config_path);
        }

        return $name ? ($this->chatConfig[$name] ?? null) : $this->chatConfig;
    }


    /**
     * 根据类型获取房间完整名字，主要为了记录当前系统总共有多少房间
     *
     * @param string $type
     * @param array $data
     * @return string
     */ 
    public function getRoomName($type, $data = [])
    {
        switch ($type) {
            case 'online':                        // 当前在线客户端，包含所有连接着
                $group_name = 'online';
                break;
            case 'auth':                        // 当前用户认证分组，包含 $this->auth 的所有身份分组
                $group_name = 'auth:' . $data['auth'];
                break;
            case 'identify':                        // 当前身份分组，customer 顾客，customer_service 客服
                $group_name = 'identify:' . $data['identify'];
                break;
            case 'customer_service_room':                        // 当前在线客服数组， 这里的客服的状态都是 在线的，如果手动切换为离线，则会被移除该房间
                $group_name = 'customer_service_room:' . ($data['room_id'] ?? 'admin');
                break;
            case 'customer_service_room_waiting':               // 当前在线用户所在的客服分组的等待房间种
                $group_name = 'customer_service_room_waiting:' . ($data['room_id'] ?? 'admin');
                break;
            case 'customer_service_room_user':               // 当前在线用户所在的客服分组
                $group_name = 'customer_service_room_user:' . ($data['room_id'] ?? 'admin') . ':' . ($data['customer_service_id'] ?? 0);
                break;
            default:
                $group_name = $type;
                break;
        }

        return $group_name;
    }


    /**
     * 获取 getter 实例，类中有 getter 属性才可以用
     *
     * @param string $driver
     * @return Getter
     */
    protected function getter($driver = null)
    {
        if ($driver) {
            return $this->getter->driver($driver);
        }
        return $this->getter;
    }
}
