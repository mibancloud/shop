<?php

namespace addons\shopro\controller;

use app\admin\model\shopro\Withdraw as WithdrawModel;
use addons\shopro\service\Withdraw as WithdrawLibrary;

class Withdraw extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index() 
    {
        $user = auth_user();
        $withdraws = WithdrawModel::where(['user_id' => $user->id])->order('id desc')->paginate($this->request->param('list_rows', 10))->each(function ($withdraw) {
            $withdraw->hidden(['withdraw_info']);
        });

        $this->success('获取成功', $withdraws);
    }


    // 提现规则
    public function rules()
    {
        $user = auth_user();
        $config = (new WithdrawLibrary($user))->config;

        $this->success('提现规则', $config);
    }


    // 发起提现请求
    public function apply()
    {
        $user = auth_user();
        $params = $this->request->param();

        $this->svalidate($params, ".apply");

        $withdrawLib = new WithdrawLibrary($user);
        $withdraw = $withdrawLib->apply($params);

        $this->success('申请成功', $withdraw);
    }
}
