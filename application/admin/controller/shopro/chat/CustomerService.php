<?php

namespace app\admin\controller\shopro\chat;

use think\Db;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\chat\CustomerService as ChatCustomerService;
use app\admin\model\shopro\chat\CustomerServiceUser;
use app\admin\model\Admin;

class CustomerService extends Common
{

    protected $noNeedRight = ['select'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new ChatCustomerService;
        $this->adminModel = new Admin;
    }

    /**
     * 客服列表
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $customerService = $this->model->sheepFilter()->with('customer_service_user')->paginate($this->request->param('list_rows', 10));
        $this->success('获取成功', null, $customerService);
    }


    /**
     * 客服添加
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only(['name', 'avatar', 'room_id', 'max_num', 'auth', 'auth_id']);
        $this->svalidate($params, ".add");

        if ($this->checkHasAuthId($params['auth'], $params['auth_id'], $params['room_id'])) {
            error_stop('该身份已绑定其他客服');
        }

        $data = Db::transaction(function () use ($params) {
            $this->model->allowField(true)->save($params);

            $customerServiceUser = CustomerServiceUser::create([
                'customer_service_id' => $this->model->id,
                'auth' => $params['auth'],
                'auth_id' => $params['auth_id'],
            ]);

            return $customerServiceUser;
        });
        $this->success('保存成功', null, $data);
    }



    /**
     * 客服详情
     *
     * @param  $id
     */
    public function detail($id)
    {
        $customerService = $this->model->with(['customer_service_user'])->where('id', $id)->find();
        if (!$customerService) {
            $this->error(__('No Results were found'));
        }

        $this->success('获取成功', null, $customerService);
    }



    /**
     * 客服编辑
     *
     * @param  $id
     */
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->only(['name', 'avatar', 'room_id', 'max_num', 'auth', 'auth_id']);
        $this->svalidate($params);

        $id = explode(',', $id);
        $list = $this->model->where('id', 'in', $id)->with('customer_service_user')->select();
        Db::transaction(function () use ($list, $params) {
            foreach ($list as $customerService) {
                $customerService->allowField(true)->save($params);

                $customerServiceUser = $customerService['customer_service_user'];

                // 编辑了客服身份所有者
                if ($params['auth'] != $customerServiceUser['auth'] || $params['auth_id'] != $customerServiceUser['auth_id']) {
                    // 验证新的身份是否已经被绑定别的客服
                    if ($this->checkHasAuthId($params['auth'], $params['auth_id'], $params['room_id'])) {
                        error_stop('该身份已绑定其他客服');
                    }

                    // 删除老的身份
                    CustomerServiceUser::{'auth' . ucfirst($customerServiceUser['auth'])}($customerServiceUser['auth_id'])
                        ->where('customer_service_id', $customerService['id'])->delete();

                    // 添加新的身份
                    $customerServiceUser = CustomerServiceUser::create([
                        'customer_service_id' => $customerService->id,
                        'auth' => $params['auth'],
                        'auth_id' => $params['auth_id'],
                    ]);
                }
            }
        });

        $this->success('更新成功');
    }


    /**
     * 客服用语
     *
     * @param  $id
     */
    public function delete($id)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }
        $id = explode(',', $id);
        $list = $this->model->where('id', 'in', $id)->select();
        Db::transaction(function () use ($list) {
            foreach ($list as $customerService) {
                // 删除客服的身份
                CustomerServiceUser::where('customer_service_id', $customerService['id'])->delete();

                $customerService->delete();
            }
        });

        $this->success('删除成功');
    }


    /**
     * 检验是否已经被绑定了客服（一个管理员或者用户只能是一种客服）
     *
     * @param string $auth
     * @param integer $auth_id
     * @return void
     */
    private function checkHasAuthId($auth, $auth_id, $room_id)
    {
        $customerServiceUser = CustomerServiceUser::{'auth' . ucfirst($auth)}($auth_id)->with('customer_service')->find();

        if ($customerServiceUser) {
            $customerService = $customerServiceUser['customer_service'];
            if ($customerService && $customerService['room_id'] == $room_id) {
                return true;
            }
        }

        return false;
    }


    /**
     * 获取管理员列表
     *
     * @return void
     */
    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $id = $this->request->param('id', 0);
        $room_id = $this->request->param('room_id', 'admin');
        
        // 已经被设置为客服的管理员
        $adminIds = CustomerServiceUser::whereExists(function ($query) use ($room_id) {
            $table_name = $this->model->getQuery()->getTable();
            $query->table($table_name)->where('room_id', $room_id)->where('customer_service_id=id');
        })->where('auth', 'admin')->where('customer_service_id', '<>', $id)->column('auth_id');

        // 正常的，并且排除了已经设置为客服的管理员
        $admins = $this->adminModel->where('status', 'normal')->whereNotIn('id', $adminIds)->select();

        $this->success('获取成功', null, $admins);
    }
}
