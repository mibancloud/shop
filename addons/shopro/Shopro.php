<?php

namespace addons\shopro;

use think\Addons;
use app\common\library\Menu;
use app\admin\model\AuthRule;
use addons\shopro\library\Hook;

/**
 * Shopro插件 v3.0.0
 */
class Shopro extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        // 创建菜单
        $menu = self::getMenu();
        Menu::create($menu['new']);

        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        // 删除菜单
        Menu::delete('shopro');

        return true;
    }

    /**
     * 插件启用方法
     */
    public function enable()
    {
        // 启用菜单
        Menu::enable('shopro');

        return true;
    }

    /**
     * 插件更新方法
     */
    public function upgrade()
    {
        // 更新菜单
        $menu = self::getMenu();
        Menu::upgrade('shopro', $menu['new']);

        return true;
    }

    /**
     * 插件禁用方法
     */
    public function disable()
    {
        // 禁用菜单
        Menu::disable('shopro');

        return true;
    }


    /**
     * 应用初始化
     */
    public function appInit()
    {
        // 公共方法
        require_once __DIR__ . '/helper/helper.php';

        // 覆盖队列 redis 参数
        $queue = \think\Config::get('queue');
        $redis = \think\Config::get('redis');
        if ($queue && strtolower($queue['connector']) == 'redis' && $redis) {
            $queue = array_merge($redis, $queue);       // queue.php 中的配置，覆盖 redis.php 中的配置
            \think\Config::set('queue', $queue);
        }

        // database 增加断线重连参数
        $database = \think\Config::get('database');
        $database['break_reconnect'] = true;        // 断线重连
        \think\Config::set('database', $database);

        // 全局注册行为事件
        Hook::register();

        if (request()->isCli()) {
            \think\Console::addDefaultCommands([
                'addons\shopro\console\ShoproChat',
                'addons\shopro\console\ShoproHelp'
            ]);
        }

        // 全局共享 暗色类型 变量
        \think\View::share('DARK_TYPE', $this->getDarkType());
    }



    public function configInit(&$config)
    {
        // 全局 js共享 暗色类型 变量
        $config['dark_type'] = $this->getDarkType();
    }


    private static function getMenu()
    {
        $newMenu = [];
        $config_file = ADDON_PATH . "shopro" . DS . 'config' . DS . "menu.php";
        if (is_file($config_file)) {
            $newMenu = include $config_file;
        }
        $oldMenu = AuthRule::where('name', 'like', "shopro%")->select();
        $oldMenu = array_column($oldMenu, null, 'name');
        return ['new' => $newMenu, 'old' => $oldMenu];
    }



    /**
     * 获取暗黑类型
     *
     * @return string
     */
    private function getDarkType()
    {
        $dark_type = 'none';
        if (in_array('darktheme', get_addonnames())) {
            // 有暗黑主题
            $darkthemeConfig = get_addon_config('darktheme');
            $dark_type = $darkthemeConfig['mode'] ?? 'none';

            $thememode = cookie("thememode");
            if ($thememode && in_array($thememode, ['dark', 'light'])) {
                $dark_type = $thememode;
            }
        }

        return $dark_type;
    }
}
