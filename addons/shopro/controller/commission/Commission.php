<?php

namespace addons\shopro\controller\commission;

use addons\shopro\controller\Common;
use addons\shopro\service\commission\Agent as AgentService;
use app\admin\model\shopro\commission\Agent as AgentModel;

class Commission extends Common
{
    protected AgentService $service;

    public function _initialize()
    {
        parent::_initialize();

        $on = sheep_config('shop.commission.level');
        if (!$on) {
            $this->error('分销中心已关闭,该功能暂不可用', null, 101);
        }
        
        $user = auth_user();

        // 检查分销商状态
        $this->service = new AgentService($user);
        if ($this->service->agent && $this->service->agent->status === AgentModel::AGENT_STATUS_FORBIDDEN) {
            $this->error('账户已被禁用,该功能暂不可用', null, 102);
        }
    }
}
