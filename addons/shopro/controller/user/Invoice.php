<?php

namespace addons\shopro\controller\user;

use addons\shopro\controller\Common;
use app\admin\model\shopro\user\Invoice as UserInvoiceModel;
use think\Db;

class Invoice extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $user = auth_user();

        $userInvoices = UserInvoiceModel::where('user_id', $user->id)
            ->order('id', 'asc')
            ->select();

        $this->success('获取成功', $userInvoices);
    }


    /**
     * 添加发票抬头
     *
     * @return \think\Response
     */
    public function add()
    {
        $user = auth_user();

        $params = $this->request->only([
            'type', 'name', 'tax_no', 'address', 'mobile', 'bank_name', 'bank_no'
        ]);
        $params['user_id'] = $user->id;
        $this->svalidate($params, ".add");

        Db::transaction(function () use ($user, $params) {
            $userInvoice = new UserInvoiceModel();
            $userInvoice->save($params);
        });

        $this->success('保存成功');
    }



    /**
     * 发票详情
     *
     * @param  $id
     * @return \think\Response
     */
    public function detail()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $userInvoice = UserInvoiceModel::where('user_id', $user->id)->where('id', $id)->find();
        if (!$userInvoice) {
            $this->error(__('No Results were found'));
        }

        $this->success('获取成功', $userInvoice);
    }



    /**
     * 编辑发票
     *
     * @return \think\Response
     */
    public function edit()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $params = $this->request->only([
            'type', 'name', 'tax_no', 'address', 'mobile', 'bank_name', 'bank_no'
        ]);
        $this->svalidate($params, ".edit");

        $userInvoice = UserInvoiceModel::where('user_id', $user->id)->where('id', $id)->find();
        if (!$userInvoice) {
            $this->error(__('No Results were found'));
        }

        Db::transaction(function () use ($params, $userInvoice) {
            $userInvoice->save($params);
        });

        $this->success('保存成功');
    }


    /**
     * 删除发票
     *
     * @param string $id 要删除的发票
     * @return void
     */
    public function delete()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $userInvoice = UserInvoiceModel::where('user_id', $user->id)->where('id', $id)->find();
        if (!$userInvoice) {
            $this->error(__('No Results were found'));
        }

        $userInvoice->delete();

        $this->success('删除成功');
    }
}
