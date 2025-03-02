<?php

namespace addons\shopro\console;

use think\Db;
use Exception;
use think\console\input\Argument;
use think\console\input\Option;
use app\admin\model\shopro\Admin;
use think\Queue;

class ShoproHelp extends Command
{
    protected $input = null;
    protected $output = null;

    /**
     * 支持的命令列表
     */
    protected $commands = [
        ['code' => "0", 'name' => 'cancel', 'desc' => '取消'],
        ['code' => "1", 'name' => 'all', 'desc' => 'shopro 帮助工具列表'],
        
        // ['code' => "2", 'name' => 'open_debug', 'desc' => '开启 debug'],
        // ['code' => "3", 'name' => 'close_debug', 'desc' => '关闭 debug'],
        ['code' => "4", 'name' => 'admin_reset_password', 'desc' => '重置管理员密码'],
        ['code' => "5", 'name' => 'admin_clear_login_fail', 'desc' => '清除管理员登录锁定状态'],
        // ['code' => "6", 'name' => 'database_notes_md', 'desc' => '生成 markdown 格式的数据库字典'],
        // ['code' => "7", 'name' => 'database_notes_pdf', 'desc' => '生成 pdf 格式的数据库字典'],
        // ['code' => "8", 'name' => 'update_composer', 'desc' => '更新 composer 包'],
        ['code' => "9", 'name' => 'queue', 'desc' => '检查队列状态'],
        // ['code' => "10", 'name' => 'clear_cache', 'desc' => '清空缓存']
    ];

    /**
     * 帮助命令配置
     */
    protected function configure()
    {
        $this->setName('shopro:help')
            ->addArgument('code', Argument::OPTIONAL, "请输入操作编号")
            ->setDescription('shopro 帮助命令');
    }



    /**
     * 全部命令集合
     */
    public function all()
    {
        $this->output->newLine();
        $this->output->writeln("shopro 帮助命令行");
        $this->output->writeln('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
        $this->output->newLine();

        if (!is_dir(RUNTIME_PATH . 'storage')) {
            @mkdir(RUNTIME_PATH . 'storage', 0755, true);
        }

        foreach ($this->commands as $command) {
            $this->output->writeln("[" . $command['code'] . "] " . $command['desc']);
        }

        $this->output->newLine();

        $code = $this->output->ask(
            $this->input,
            '请输入命令代号',
            '0'
        );

        $this->choose($code);
    }


    /**
     * 打开调试模式
     */
    // public function openDebug()
    // {
    //     $this->setEnvFilePath();

    //     $this->setEnvVar('APP_DEBUG', 'true');

    //     $this->output->writeln("debug 开启成功");
    //     return true;
    // }


    // /**
    //  * 关闭调试模式
    //  */
    // public function closeDebug()
    // {
    //     $this->setEnvFilePath();

    //     $this->setEnvVar('APP_DEBUG', 'false');

    //     $this->output->writeln("debug 关闭成功");
    //     return true;
    // }


    /**
     * 重置管理员密码
     */
    public function adminResetPassword()
    {
        $this->output->newLine();
        $this->output->writeln("重置管理员密码");
        $this->output->writeln('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
        $this->output->newLine();

        $username = $this->output->ask(
            $this->input,
            '请输入要重置的管理员账号'
        );

        $admin = null;
        if ($username) {
            $admin = Admin::where('username', $username)->find();
        }

        if (!$admin) {
            $this->output->error("请输入正确的管理员账号");
            return false;
        }

        $password = $this->output->ask(
            $this->input,
            '请输入要设置的密码[6-16]'
        );

        if (empty($password) || strlen($password) < 6 || strlen($password) > 16) {
            $this->output->error("请输入正确的密码");
            return false;
        }

        // 重置密码
        $admin->salt = $admin->salt ?: mt_rand(1000, 9999);
        $admin->password = md5(md5($password) . $admin->salt);
        $admin->save();

        $this->output->writeln("账号 [" . $admin->username . "] 的密码重置成功");
        return true;
    }



    /**
     * 清除管理员登录失败锁定状态
     */
    public function adminClearLoginFail()
    {
        $this->output->newLine();
        $this->output->writeln("清除管理员登录锁定状态");
        $this->output->writeln('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
        $this->output->newLine();

        $username = $this->output->ask(
            $this->input,
            '请输入要清除的管理员账号'
        );

        $admin = null;
        if ($username) {
            $admin = Admin::where('username', $username)->find();
        }

        if (!$admin) {
            $this->output->error("请输入正确的管理员账号");
            return false;
        }

        $admin->loginfailure = 0;
        $admin->save();

        $this->output->writeln("账号 [" . $admin->username . "] 锁定状态清除成功");
        return true;
    }



    /**
     * 检查队列状态
     */
    public function queue() 
    {
        @unlink(RUNTIME_PATH . 'storage/queue/shopro.log');
        @unlink(RUNTIME_PATH . 'storage/queue/shopro-high.log');

        $queue = config('queue');
        $connector = $queue['connector'] ?? 'sync';

        if ($connector == 'sync') {
            $this->output->error("队列驱动不可以使用 sync，请选择 database 或者 redis 配置");
            return false;
        }

        $this->output->writeln('当前队列驱动为：' . $connector);
        $this->output->writeln('正在添加测试队列...');
        $this->output->newLine();

        // 添加验证队列
        Queue::push('\addons\shopro\job\Test@shopro', [], 'shopro');       // 普通队列
        Queue::push('\addons\shopro\job\Test@shoproHigh', [], 'shopro-high');     // 高优先级队列

        $this->output->writeln('测试队列添加成功');
        $this->output->writeln('请检查 ' . str_replace('\\', '/', RUNTIME_PATH . 'storage/queue') . '目录下是否存在如下文件，并且文件内容为当前测试的时间');
        $this->output->newLine();
        $this->output->writeln(str_replace('\\', '/', RUNTIME_PATH . 'storage/queue') . 'shopro.log               // 如果没有该文件，则普通优先级队列未监听');
        $this->output->writeln(str_replace('\\', '/', RUNTIME_PATH . 'storage/queue') . 'shopro-high.log          // 如果没有该文件，则高优先级队列未监听');
        $this->output->newLine();

        return true;
    }
}
