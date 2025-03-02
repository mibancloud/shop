<?php

namespace addons\shopro\library\chat\traits;

use addons\shopro\exception\ShoproException;

/**
 * debug 方式注册事件
 */
trait DebugEvent
{

    /**
     * 注册事件
     */
    public function register($event, \Closure $cb)
    {
        $this->socket->on($event, function ($data, $callback = null) use ($cb) {
            try {
                $cb($data, $callback);
            } catch (\Exception $e) {
                $this->errorHandler($e, 'register');

                // 将错误报告给前端
                $this->sender->errorSocket('custom_error', ($this->io->debug || $e instanceof ShoproException) ? $e->getMessage() : 'socket 服务器异常');
            }
        });
    }



    /**
     * 执行代码
     *
     * @param object $httpConnection    当前 socket 连接
     * @param \Closure $callback
     * @return void
     */
    public function exec($httpConnection, \Closure $callback) 
    {
        try {
            $callback();
        } catch (\Exception $e) {
            $this->errorHandler($e, 'exec');

            // 将错误报告给前端
            $httpConnection->send(($this->io->debug || $e instanceof ShoproException) ? $e->getMessage() : 'socket 服务器异常');
        }
    }



    /**
     * 判断处理异常
     *
     * @param \Exception $e
     * @param string $type
     * @return void
     */
    private function errorHandler(\Exception $e, $type)
    {
        $error = [
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'error' => $e->getMessage()
            // 'trace' => $e->getTrace(),
        ];

        if ($this->io->debug) {
            echo 'websocket:' . $type . '：执行失败，错误信息：' . json_encode($error, JSON_UNESCAPED_UNICODE);
        } else {
            format_log_error($e, 'WebSocket', 'WebSocket 执行失败');
        }
    }
}
