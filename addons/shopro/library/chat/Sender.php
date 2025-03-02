<?php

namespace addons\shopro\library\chat;

use addons\shopro\library\chat\traits\Helper;
use addons\shopro\library\chat\traits\Session;
use addons\shopro\library\chat\traits\BindUId;
use addons\shopro\library\chat\traits\sender\SenderFunc;
use PHPSocketIO\SocketIO;
use PHPSocketIO\Socket;
use PHPSocketIO\Nsp;

/**
 * 客服 Sender 发送类
 */
class Sender
{

    /**
     * 绑定 UID 助手
     */
    use BindUId;

    /**
     * 特定的发送方法
     */
    use SenderFunc;
    /**
     * session
     */
    use Session;
    /**
     * 帮助方法
     */
    use Helper;

    /**
     * 当前 socket 实例
     * 
     * @var Socket
     */
    protected $socket = null;
    /**
     * 当前 phpsocket.io 实例
     * 
     * @var SocketIO
     */
    protected $io = null;

    /**
     * 当前 phpsocket.io 的 nsp 实例
     * 
     * @var Nsp
     */
    protected $nsp = null;
    /**
     * 当前 获取 实例
     * 
     * @var Getter
     */
    protected $getter = null;


    public function __construct(Socket $socket = null, SocketIo $io, Nsp $nsp, Getter $getter = null)
    {
        $this->socket = $socket;
        $this->io = $io;
        $this->nsp = $nsp;
        $this->getter = $getter;
    }



    /**
     * 返回成功方法
     *
     * @param string $event
     * @param string $msg
     * @param array $data
     * @param Socket|Nsp $data
     * @return void
     */
    public function success($event, $msg = '', $data = null, $sender = null)
    {
        $result = [
            'code' => 1,
            'msg' => $msg,
            'data' => $data
        ];

        if ($event instanceof \Closure) {
            $event($result);
        } else {
            $sender && $sender->emit($event, $result);
        }
    }


    /**
     * 返回成功方法
     *
     * @param string $event
     * @param string $msg
     * @param Socket|Nsp $data
     * @return void
     */
    public function error($event, $msg = '', $data = null, $sender = null)
    {
        $result = [
            'code' => 0,
            'msg' => $msg,
            'data' => $data
        ];

        if ($event instanceof \Closure) {
            $event($result);
        } else {
            $sender && $sender->emit($event, $result);
        }
    }


    /**
     * 成功发给当前渠道
     * 
     * @param string $event
     * @param string $msg
     * @param Socket|Nsp $data
     * @return void
     */
    public function successSocket($event, $msg = '', $data = null) 
    {
        $this->success($event, $msg, $data, $this->socket);
    }


    /**
     * 失败发给当前渠道
     * 
     * @param string $event
     * @param string $msg
     * @param Socket|Nsp $data
     * @return void
     */
    public function errorSocket($event, $msg = '', $data = null) 
    {
        $this->error($event, $msg, $data, $this->socket);
    }



    /**
     * 通过 clients 发送成功事件
     *
     * @param string $event
     * @param string $msg
     * @param array $data
     * @param array $clientIds
     * @return void
     */
    public function successClients($event, $msg = '', $data = null, $clientIds = [])
    {
        $clientIds = $clientIds ? (is_array($clientIds) ? $clientIds : [$clientIds]) : [];

        foreach ($clientIds as $client_id) {
            if (isset($this->nsp->connected[$client_id])) {
                $sender = $this->nsp->connected[$client_id];
                $this->success($event, $msg, $data, $sender);
            }
        }
    }



    /**
     * 通过 UId 发送成功事件
     *
     * @param string $event
     * @param string $msg
     * @param array $data
     * @param array $uIdData
     * @return void
     */
    public function successUId($event, $msg = '', $data = null, $uIdData = [])
    {
        $uIdData = $uIdData ? $uIdData : [];

        if ($uIdData) {
            $clientIds = $this->getClientIdByUId($uIdData['id'], $uIdData['type']);

            $this->successClients($event, $msg, $data, $clientIds);
        }
    }


    /**
     * 通过 UId 发送成功事件
     *
     * @param string $event
     * @param string $msg
     * @param array $data
     * @param string $room
     * @return void
     */
    public function successRoom($event, $msg = '', $data = null, $room = '')
    {
        $room = $room ? $room : '';

        if ($room) {
            $sender = $this->nsp->to($room);
            $this->success($event, $msg, $data, $sender);
        }
    }


    /**
     * 转发， message 开头保存数据库
     */
    public function __call($name, $arguments)
    {
        $currentName = $name;

        // 需要存储数据库的消息，先存储数据库，再发送
        if (strpos($name, 'message') === 0) {
            $room_id = $this->session('room_id');
            // 存库
            $chatRecord = $this->getter('db')->addMessage($room_id, $name, $arguments);

            // 将 message 追加到 content 里面
            $content = $arguments[2] ?? [];
            $content['message'] = $chatRecord->toArray();
            $arguments[2] = $content;

            // 重载方法名
            $currentName = str_replace('message', 'success', $name);
        }

        
        $sender_status = true;
        switch($currentName) {
            case 'successUId':
                if (!isset($arguments[3]) || !isset($arguments[3]['id']) || !$arguments[3]['id']) {
                    // 缺少参数 id
                    $sender_status = false;
                }
                break;
            case 'successClients':
                if (!isset($arguments[3]) || !$arguments[3]) {
                    // 缺少接收的 clientIds
                    $sender_status = false;
                }
        }

        if ($sender_status) {
            // 接收者正常
            return self::$currentName(...$arguments);
        }
    }
}
