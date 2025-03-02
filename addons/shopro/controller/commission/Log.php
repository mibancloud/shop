<?php

namespace addons\shopro\controller\commission;

use app\admin\model\shopro\commission\Log as LogModel;
use addons\shopro\library\Operator;
use app\admin\model\shopro\user\User as UserModel;
use app\admin\model\Admin as AdminModel;

class Log extends Commission
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    // 分销动态
    public function index()
    {
        $agentId = $this->service->user->id;

        $logs = LogModel::where([
            'agent_id' => $agentId
        ])->order('id desc')->paginate(request()->param('list_rows', 10));

        $morphs = [
            'user' => UserModel::class,
            'admin' => AdminModel::class,
            'system' => AdminModel::class
        ];
        $logs = morph_to($logs, $morphs, ['oper_type', 'oper_id']);
        $logs = $logs->toArray();

        // 解析操作人信息
        foreach ($logs['data'] as &$log) {
            $log['oper'] = Operator::info($log['oper_type'], $log['oper'] ?? null);
        }

        $this->success("", $logs);
    }
}
