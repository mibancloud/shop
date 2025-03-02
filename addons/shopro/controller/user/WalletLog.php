<?php

namespace addons\shopro\controller\user;

use addons\shopro\controller\Common;
use app\admin\model\shopro\user\WalletLog as UserWalletLogModel;

class WalletLog extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];


    public function index()
    {
        $type = $this->request->param('type', 'money');
        $tab = $this->request->param('tab', 'all');
        $list_rows = $this->request->param('list_rows', 10);
        $date = $this->request->param('date/a');
        $user = auth_user();
        $where['user_id'] = $user->id;

        switch ($tab) {
            case 'income':
                $where['amount'] = ['>', 0];
                break;
            case 'expense':
                $where['amount'] = ['<', 0];
                break;
        }

        $income = UserWalletLogModel::where('user_id', $user->id)->{$type}()->where('amount', '>', 0)->whereTime('createtime', 'between', $date)->sum('amount');
        $expense = UserWalletLogModel::where('user_id', $user->id)->{$type}()->where('amount', '<', 0)->whereTime('createtime', 'between', $date)->sum('amount');
        $logs = UserWalletLogModel::where($where)->{$type}()->whereTime('createtime', 'between', $date)->order('createtime', 'desc')->paginate($list_rows);
        $this->success('获取成功', ['list' => $logs, 'income' => $income, 'expense' => $expense]);
    }
}
