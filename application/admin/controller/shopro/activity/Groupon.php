<?php

declare(strict_types=1);

namespace app\admin\controller\shopro\activity;

use think\Db;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\activity\Activity as ActivityModel;
use app\admin\model\shopro\activity\Groupon as GrouponModel;
use app\admin\model\shopro\Admin;
use addons\shopro\library\activity\traits\Groupon as GrouponTrait;

/**
 * 团管理
 */
class Groupon extends Common
{
    use GrouponTrait;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new GrouponModel;
    }



    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $groupons = $this->model->sheepFilter()->with(['goods', 'user', 'grouponLogs'])
            ->paginate(request()->param('list_rows', 10));

        $this->success('获取成功', null, $groupons);
    }



    /**
     * 团详情
     *
     * @param  $id
     * @return \think\Response
     */
    public function detail($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $groupon = $this->model->with(['goods', 'user', 'grouponLogs'])->where('id', $id)->find();
        if (!$groupon) {
            $this->error(__('No Results were found'));
        }

        $this->success('获取成功', null, $groupon);
    }


    /**
     * 添加虚拟用户（人满自动成团）
     *
     * @param Request $request
     * @param integer $id
     */
    public function addUser($id)
    {
        $groupon = $this->model->where('id', $id)->find();
        if (!$groupon) {
            $this->error(__('No Results were found'));
        }

        $activity = ActivityModel::where('id', $groupon['activity_id'])->find();
        if (!$activity) {
            $this->error('活动不存在');
        }
        if ($groupon['status'] != 'ing' || $groupon['current_num'] > $groupon['num']) {
            $this->error('团已完成或已失效');
        }

        $avatar = $this->request->param('avatar', '');
        $nickname = $this->request->param('nickname', '');
        $user = ['avatar' => $avatar, 'nickname' => $nickname];

        Db::transaction(function () use ($activity, $groupon, $user) {
            // 增加人数
            $this->finishFictitiousGroupon($activity, $groupon, false, 1, [$user]);
        });

        $this->success('操作成功');
    }



    /**
     * 解散团，自动退款
     *
     * @param Request $request
     * @param integer $id
     */
    public function invalid($id)
    {
        $admin = $this->auth->getUserInfo();
        $admin = Admin::find($admin['id']);
        $groupon = $this->model->where('id', $id)->find();

        if ($groupon['status'] != 'ing') {
            $this->error('团已完成或已失效');
        }

        Db::transaction(function () use ($groupon, $admin) {
            // 解散团，并退款
            $this->invalidRefundGroupon($groupon, $admin);
        });

        $this->success('操作成功');
    }
}
