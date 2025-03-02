<?php

namespace app\admin\controller\shopro;

use think\Db;
use think\exception\HttpResponseException;
use addons\shopro\exception\ShoproException;
use app\admin\model\shopro\Withdraw as WithdrawModel;
use app\admin\model\shopro\WithdrawLog as WithdrawLogModel;
use app\admin\model\shopro\user\User as UserModel;
use app\admin\model\Admin as AdminModel;
use addons\shopro\service\Withdraw as WithdrawLibrary;
use addons\shopro\library\Operator;

/**
 * 提现
 */
class Withdraw extends Common
{

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new WithdrawModel;
        $this->logModel = new WithdrawLogModel;
    }



    /**
     * 提现列表
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $withdraws = $this->model->sheepFilter()->with(['user'])->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $withdraws);
    }


    /**
     * 提现日志
     */
    public function log($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $logs = $this->logModel->where('withdraw_id', $id)->order('id desc')->select();
        $morphs = [
            'user' => UserModel::class,
            'admin' => AdminModel::class,
            'system' => AdminModel::class
        ];
        $logs = morph_to($logs, $morphs, ['oper_type', 'oper_id']);
        $logs = $logs->toArray();

        // 解析操作人信息
        foreach ($logs as &$log) {
            $log['oper'] = Operator::info($log['oper_type'], $log['oper'] ?? null);
        }
        $this->success('获取成功', null, $logs);
    }


    public function handle($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->param();
        $action = $params['action'] ?? null;
        $refuse_msg = $params['refuse_msg'] ?? '';
        if ($action == 'refuse' && !$refuse_msg) {
            $this->error('请输入拒绝原因');
        }

        $ids = is_array($id) ? $id : explode(',', $id);
        foreach ($ids as $key => $id) {
            Db::startTrans();
            try {
                $withdraw = $this->model->lock(true)->where('id', $id)->find();
                if (!$withdraw) {
                    $this->error(__('No Results were found'));
                }
                $withdrawLib = new WithdrawLibrary($withdraw->user_id);

                switch ($action) {
                    case 'agree':
                        $withdraw = $withdrawLib->handleAgree($withdraw);
                        break;
                    case 'agree&withdraw':
                        $withdraw = $withdrawLib->handleAgree($withdraw);
                        $withdraw = $withdrawLib->handleWithdraw($withdraw);
                        break;
                    case 'withdraw':
                        $withdraw = $withdrawLib->handleWithdraw($withdraw);
                        break;
                    case 'refuse':
                        $withdraw = $withdrawLib->handleRefuse($withdraw, $refuse_msg);
                        break;
                }

                Db::commit();
            } catch (ShoproException $e) {
                Db::commit();       // 不回滚，记录错误日志
                $this->error($e->getMessage());
            } catch (HttpResponseException $e) {
                $data = $e->getResponse()->getData();
                $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
                $this->error($message);
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }

        $this->success('处理成功');
    }
}
