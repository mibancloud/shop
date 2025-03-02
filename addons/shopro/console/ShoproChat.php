<?php

namespace addons\shopro\console;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use think\exception\HttpResponseException;
use Workerman\Worker;
use Workerman\Timer;
use PHPSocketIO\SocketIO;
use Workerman\Protocols\Http\Request;
use Workerman\Connection\TcpConnection;
use addons\shopro\library\chat\Chat;
use addons\shopro\library\chat\Getter;
use addons\shopro\library\chat\Sender;
use addons\shopro\library\chat\traits\Helper;
use think\Db;

class ShoproChat extends Command
{
    use Helper;

    protected $input = null;
    protected $output = null;

    /**
     * 帮助命令配置
     */
    protected function configure()
    {
        $this->setName('shopro:chat')
            ->addArgument('action', Argument::OPTIONAL, "action start|stop|restart|status", 'start')
            ->addArgument('type', Argument::OPTIONAL, "d -d")
            ->addOption('debug', null, Option::VALUE_OPTIONAL, '开启调试模式', false)
            ->setHelp('此命令是用来启动 Shopro商城 的客服服务端进程')
            ->setDescription('Shopro商城 客服');
    }


    /**
     * 执行帮助命令
     */
    protected function execute(Input $input, Output $output)
    {
        $this->input = $input;
        $this->output = $output;

        global $argv;

        $action = $input->getArgument('action');
        $type   = $input->getArgument('type') ? '-d' : '';
        $debug = $input->hasOption('debug');

        if (strpos(strtolower(PHP_OS), 'win') === false) {
            // windows 不需要设置参数

            $argv = [];
            $argv[0] = 'think shopro:chat';
            $argv[1] = $action;
            $argv[2] = $type ? '-d' : '';
        }

        $this->start($input, $output, $debug);
    }


    private function start($input, $output, $debug)
    {
        $chatSystem = $this->getConfig('system');
        $ssl = $chatSystem['ssl'] ?? 'none';
        $ssl_cert = $chatSystem['ssl_cert'] ?? '';
        $ssl_key = $chatSystem['ssl_key'] ?? '';
        $worker_num = $chatSystem['worker_num'] ?? 1;
        $port = $chatSystem['port'] ?? '';
        $port = $port ? intval($port) : 2121;
        $inside_host = $chatSystem['inside_host'] ?? '';
        $inside_host = '0.0.0.0';           // 这里默认都只绑定 0.0.0.0
        $inside_port = $chatSystem['inside_port'] ?? '';
        $inside_port = $inside_port ? intval($inside_port) : 9191;

        // 创建socket.io服务端
        $context = [
            // 'pingInterval' => '10',      // 参数不可用
            // 'pingTimeout' => '50'        // 参数不对，不可用
        ];
        if ($ssl == 'cert') {
            // 证书模式
            $context['ssl'] = [
                'local_cert' => $ssl_cert,
                'local_pk' => $ssl_key,
                'verify_peer' => false
            ];
        }

        $io = new SocketIO($port, $context);
        $io->worker->name = 'ShoproChatWorker';
        // $io->worker->count = $worker_num;     // 启动 worker 的进程数量，经测试 linux 上不支持设置多个进程， 再启动 web-msg-sender 时候 会导致多次启动同一个端口，端口被占用的情况
        $io->debug = $debug;        // 自定义 debug

        // 定义命名空间
        $nsp = $io->of('/chat');
        $io->on('workerStart', function () use ($io, $nsp, $inside_host, $inside_port) {
            $inner_http_worker = new Worker('http://' . $inside_host . ':' . $inside_port);
            $inner_http_worker->onMessage = function (TcpConnection $httpConnection, Request $request) use ($io, $nsp) {
                // 请求地址
                $uri = $request->uri();
                // 请求参数
                $data = $request->post();

                $chat = new Chat($io, $nsp);
                $chat->innerWorker($httpConnection, $uri, $data);
            };
            $inner_http_worker->listen();


            // 添加排队等待定时器 【30 秒 通知一次等待中的用户，有等待中用户被接入时也会主动通知等待中用户】
            $getter = new Getter(null, $io, $nsp);
            $sender = new Sender(null, $io, $nsp, $getter);
            Timer::add(30, function () use ($getter, $sender) {
                // 定时通知所有房间中排队用户排名变化
                $sender->allWaitingQueue();
            });


            Timer::add(15, function () use ($getter) {
                // 更新客服忙碌度
                $getter->updateCustomerServiceBusyPercent();
            });
        });

        // 当有客户端连接时打印一行文字
        $nsp->on('connection', function($socket) use ($io) {
            $nsp = $io->of('/chat');
            // 连接时候只走一次，后续发消息，这个方法就不走了

            // 绑定客服连接事件
            try {
                $chat = new Chat($io, $nsp, $socket);
    
                $chat->on();
            } catch (HttpResponseException $e) {
                $data = $e->getResponse()->getData();
                $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
                echo $message;
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        });

        // 定义第二个命名空间
        // $nsp = $io->of('/server');
        // $nsp->on('connection', function($socket) use ($io) {

        //     // $chat = new Chat($io, $socket);

        //     // $chat->on();
        //     echo "new connection server\n";
        // });

        // 断开 mysql 连接，防止 2006 MySQL server has gone away 错误
        Db::close();

        // 日志文件
        if (!is_dir(RUNTIME_PATH . 'log/chat')) {
            @mkdir(RUNTIME_PATH . 'log/chat', 0755, true);
        }
        Worker::$logFile = RUNTIME_PATH . 'log/chat/shopro_chat.log';
        Worker::$stdoutFile = RUNTIME_PATH . 'log/chat/std_out.log';        // 如果部署的时候部署错误（比如未删除php禁用函数），会产生大量日志，先关掉

        Worker::runAll();
    }
}
