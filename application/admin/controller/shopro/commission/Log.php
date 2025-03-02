<?php

namespace app\admin\controller\shopro\commission;

use app\admin\controller\shopro\Common;
use app\admin\model\shopro\commission\Log as LogModel;
use app\admin\model\shopro\user\User as UserModel;
use app\admin\model\Admin as AdminModel;
use addons\shopro\library\Operator;

class Log extends Common
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new LogModel();
    }


    /**
     * 查看
     *
     * @return Response
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $logs = $this->model->sheepFilter()->with(['agent'])->paginate($this->request->param('list_rows', 10));
        
        $morphs = [
            'user' => UserModel::class,
            'admin' => AdminModel::class,
            'system' => AdminModel::class
        ];
        $logs = morph_to($logs, $morphs, ['oper_type', 'oper_id']);
        $logs = $logs->toArray();

        // 格式化操作人信息
        foreach ($logs['data'] as &$log) {
            $log['oper'] = Operator::info($log['oper_type'], $log['oper'] ?? null);
        }

        $this->success('获取成功', null, $logs);
    }
}
