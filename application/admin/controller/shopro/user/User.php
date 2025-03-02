<?php

namespace app\admin\controller\shopro\user;

use think\Db;
use app\admin\controller\shopro\Common;
use addons\shopro\service\Wallet as WalletService;
use app\admin\model\shopro\user\User as UserModel;
use app\admin\model\shopro\user\Coupon as UserCouponModel;

class User extends Common
{
    protected $model = null;

    protected $noNeedRight = ['select'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new UserModel;
    }

    /**
     * 用户列表
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $data = $this->model->sheepFilter()->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $data);
    }

    /**
     * 用户详情
     *
     * @param  $id
     */
    public function detail($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $user = $this->model->with(['third_oauth', 'parent_user'])->where('id', $id)->find();
        if (!$user) {
            $this->error(__('No Results were found'));
        }

        $this->success('获取成功', null, $user);
    }

    /**
     * 更新用户
     *
     * @param  $id
     * @return \think\Response
     */
    public function edit($id = null)
    {
        $params = $this->request->only(['username', 'nickname', 'mobile', 'password', 'avatar', 'gender', 'email', 'status']);

        if (empty($params['password'])) unset($params['password']);
        if (empty($params['username'])) unset($params['username']);

        $params['id'] = $id;
        $this->svalidate($params, '.edit');
        unset($params['id']);

        $user = $this->model->where('id', $id)->find();
        $user->save($params);

        $this->success('更新成功', null, $user);
    }

    /**
     * 删除用户(支持批量)
     *
     * @param  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

        $id = explode(',', $id);
        $list = $this->model->where('id', 'in', $id)->select();
        $result = Db::transaction(function () use ($list) {
            $count = 0;
            foreach ($list as $item) {
                $count += $item->delete();
            }

            return $count;
        });

        if ($result) {
            $this->success('删除成功', null, $result);
        } else {
            $this->error(__('No rows were deleted'));
        }
    }

    public function recharge()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only(['id', 'type', 'amount', 'memo']);
        if (!in_array($params['type'], ['money', 'score'])) {
            error_stop('参数错误');
        }

        $result = Db::transaction(function () use ($params) {
            return WalletService::change($params['id'], $params['type'], $params['amount'], 'admin_recharge', [], $params['memo']);
        });
        if ($result) {
            $this->success('充值成功');
        }
        $this->error('充值失败');
    }


    /**
     * 用户优惠券列表
     */
    public function coupon($id)
    {
        $userCoupons = UserCouponModel::sheepFilter()->with('coupon')->where('user_id', $id)
            ->order('id', 'desc')->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $userCoupons);
    }


    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $data = $this->model->sheepFilter()->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $data);
    }
}
