<?php

namespace app\admin\controller\shopro\user;

use app\admin\controller\shopro\Common;
use app\admin\model\shopro\user\WalletLog as WalletLogModel;
use addons\shopro\library\Operator;

class WalletLog extends Common
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new WalletLogModel;
    }

    /**
     * 余额记录
     */
    public function money($id)
    {
        $list_rows = $this->request->param('list_rows', 10);
        $walletLogs = WalletLogModel::where('user_id', $id)->money()->order('id', 'desc')->paginate($list_rows);

        // 多态关联 oper
        $morphs = [
            'user' => \app\admin\model\shopro\user\User::class,
            'admin' => \app\admin\model\Admin::class,
            'system' => \app\admin\model\Admin::class,
        ];
        $walletLogs = morph_to($walletLogs, $morphs, ['oper_type', 'oper_id']);
        $walletLogs = $walletLogs->toArray();

        // 解析操作人信息
        foreach ($walletLogs['data'] as &$log) {
            $log['oper'] = Operator::info($log['oper_type'], $log['oper'] ?? null);
        }
        $this->success('', null, $walletLogs);
    }

    /**
     * 积分记录
     */
    public function score($id)
    {
        $list_rows = $this->request->param('list_rows', 10);
        $walletLogs = WalletLogModel::where('user_id', $id)->score()->order('id', 'desc')->paginate($list_rows);

        // 多态关联 oper
        $morphs = [
            'user' => \app\admin\model\shopro\user\User::class,
            'admin' => \app\admin\model\Admin::class,
            'system' => \app\admin\model\Admin::class,
        ];
        $walletLogs = morph_to($walletLogs, $morphs, ['oper_type', 'oper_id']);
        $walletLogs = $walletLogs->toArray();

        // 解析操作人信息
        foreach ($walletLogs['data'] as &$log) {
            $log['oper'] = Operator::info($log['oper_type'], $log['oper'] ?? null);
        }
        $this->success('', null, $walletLogs);
    }

    /**
     * 佣金记录
     */
    public function commission($id)
    {
        $list_rows = $this->request->param('list_rows', 10);
        $walletLogs = WalletLogModel::where('user_id', $id)->commission()->order('id', 'desc')->paginate($list_rows);

        // 多态关联 oper
        $morphs = [
            'user' => \app\admin\model\shopro\user\User::class,
            'admin' => \app\admin\model\Admin::class,
            'system' => \app\admin\model\Admin::class,
        ];
        $walletLogs = morph_to($walletLogs, $morphs, ['oper_type', 'oper_id']);
        $walletLogs = $walletLogs->toArray();

        // 解析操作人信息
        foreach ($walletLogs['data'] as &$log) {
            $log['oper'] = Operator::info($log['oper_type'], $log['oper'] ?? null);
        }
        $this->success('', null, $walletLogs);
    }
}
