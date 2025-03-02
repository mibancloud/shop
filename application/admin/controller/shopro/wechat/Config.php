<?php

namespace app\admin\controller\shopro\wechat;

use app\admin\model\shopro\Config as ShoproConfig;
use app\admin\controller\shopro\Common;

class Config extends Common
{
    /**
     * 公众号配置
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        if ('GET' === $this->request->method()) {

            $configs = ShoproConfig::getConfigs('wechat.officialAccount', false);
            $configs['server_url'] = request()->domain() . '/addons/shopro/wechat.serve';
        } elseif ('POST' === $this->request->method()) {

            $configs = ShoproConfig::setConfigs('wechat.officialAccount', $this->request->param());
        }

        $this->success('操作成功', null, $configs);
    }
}
