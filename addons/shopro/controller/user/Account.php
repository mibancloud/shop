<?php

namespace addons\shopro\controller\user;

use addons\shopro\controller\Common;
use app\admin\model\shopro\user\Account as AccountModel;

class Account extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $params = $this->request->only([
            'type'
        ]);
        $user = auth_user();
        $where = [
            'user_id' => $user->id
        ];
        if (!empty($params['type'])) {
            $where['type'] = $params['type'];
        }

        $data = AccountModel::where($where)->order('updatetime desc')->find();
        if (!$data) {
            $this->error(__('No Results were found'));
        }

        $this->success('获取成功', $data);
    }

    public function save()
    {
        $user = auth_user();
    
        $params = $this->request->only([
            'type', 'account_name', 'account_header', 'account_no'
        ]);
        if (!in_array($params['type'], ['wechat', 'alipay', 'bank'])) {
            $this->error('请选择正确的账户类型');
        }
        if ($params['type'] === 'alipay') {
            $params['account_header'] = '支付宝账户';
        }
        if ($params['type'] === 'wechat') {
            $params['account_header'] = '微信账户';
            $params['account_no'] = '-';
        }
        $this->svalidate($params, ".{$params['type']}");

        $data = AccountModel::where(['user_id' => $user->id, 'type' => $params['type']])->find();
        if (!$data) {
            $data = AccountModel::create([
                'user_id' => $user->id,
                'type' => $params['type'],
                'account_name' => $params['account_name'],
                'account_header' => $params['account_header'],
                'account_no' => $params['account_no'],
            ]);
        } else {
            $data->save($params);
        }
        $this->success('保存成功', $data);
    }
}
