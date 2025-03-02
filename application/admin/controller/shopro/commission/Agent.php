<?php

namespace app\admin\controller\shopro\commission;

use app\admin\controller\shopro\Common;
use app\admin\model\shopro\commission\Agent as AgentModel;
use app\admin\model\shopro\user\User as UserModel;
use app\admin\model\shopro\commission\Log as LogModel;
use app\admin\model\shopro\commission\Level as LevelModel;
use addons\shopro\service\commission\Agent as AgentService;
use think\Db;

class Agent extends Common
{
    protected $noNeedRight = ['select'];

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new AgentModel();
    }

    /**
     * 查看
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $list = $this->model->sheepFilter()->with(['user.parent_user', 'level_info', 'level_status_info', 'upgrade_level'])->paginate($this->request->param('list_rows', 10));

        $this->success('分销商列表', null, $list);
    }

    /**
     * 详情
     *
     * @param  $id
     */
    public function detail($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $detail = $this->model->with(['user.parent_user', 'level_info', 'level_status_info', 'upgrade_level'])->where('user_id', $id)->find();
        if (!$detail) {
            $this->error(__('No Results were found'));
        }

        $this->success('分销商详情', null, $detail);
    }


    /**
     * 团队
     *
     * @param  $id
     */
    public function team($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $detail = $this->model->with(['user.parent_user', 'level_info'])->where('user_id', $id)->find();
        if (!$detail) {
            $this->error(__('No Results were found'));
        }
        
        $detail->agent_team = AgentModel::hasWhere('user', function ($query) use ($detail) {
            return $query->where('parent_user_id', $detail->user_id);
        })->with(['user', 'level_info'])->select();
        $this->success('分销商详情', null, $detail);
    }

    // 选择分销商
    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $data = $this->model->sheepFilter()->with(['user', 'level_info', 'level_status_info', 'upgrade_level'])
            ->paginate($this->request->param('list_rows', 10));
        
        $this->success('选择分销商', null, $data);
    }

    /**
     * 编辑
     *
     * @param  $id
     */
    public function edit($id = null)
    {
        $params = $this->request->only(['status', 'upgrade_lock', 'level_status', 'level', 'apply_info']);

        $result = Db::transaction(function () use ($id, $params) {
            $row = $this->model->with(['user', 'level_info', 'level_status_info', 'upgrade_level'])->where('user_id', $id)->find();
            if (!$row) {
                $this->error('未找到该分销商');
            }

            foreach ($params as $field => $value) {
                switch ($field) {
                    case 'status':  // 修改状态
                        return $this->changeStatus($row, $value);
                        break;
                    case 'level_status':    // 审核等级
                        return $this->changeLevelStatus($row, $value);
                        break;
                    case 'level':           // 修改等级
                        return $this->changeLevel($row, $value);
                        break;
                    default:
                        return $row->save([$field => $value]);
                }
            }
        });
        if ($result) {
            $this->success('更新成功', null, $result);
        } else {
            $this->error('更新失败');
        }
    }


    // 修改状态
    private function changeStatus($row, $value)
    {
        $result = $row->save(['status' => $value]);
        if ($result) {
            LogModel::add($row->user_id, 'agent', ['type' => 'status', 'value' => $value]);
            (new AgentService($row->user_id))->createAsyncAgentUpgrade();
        }
        return $result;
    }

    // 审核等级
    private function changeLevelStatus($row, $value)
    {
        if ($row->level_status == 0 && $value > 0) {
            $this->error('非法操作');
        }

        if ($value == 0) {  // 拒绝操作
            return $row->save(['level_status' => 0]);
        } else {            // 同意操作
            if ($row->upgrade_level) {
                $result = $row->save(['level_status' => 0, 'level' => $row->upgrade_level->level]);
                if ($result) {
                    LogModel::add($row->user_id, 'agent', ['type' => 'level', 'level' => $row->upgrade_level]);
                    (new AgentService($row->user_id))->createAsyncAgentUpgrade();
                }
                return $result;
            }
        }
        return false;
    }

    // 修改等级
    private function changeLevel($row, $value)
    {
        $level = LevelModel::find($value);
        if ($level) {
            $result = $row->save(['level' => $level->level]);
            if ($result) {
                LogModel::add($row->user_id, 'agent', ['type' => 'level', 'level' => $level]);
                (new AgentService($row->user_id))->createAsyncAgentUpgrade();
            }
            return $result;
        } else {
            $this->error('未找到该等级');
        }
    }

    // 更换推荐人
    public function changeParentUser($id)
    {
        $userAgent = new AgentService($id);

        if (!$userAgent->user) {
            $this->error('未找到该用户');
        }

        $parentUserId = $this->request->param('parent_user_id', 0);

        // 更换推荐人检查
        if ($parentUserId != 0) {
            $parentAgent = new AgentService($parentUserId);
            if (!$parentAgent->isAgentAvaliable()) {
                $this->error('选中用户暂未成为分销商,不能成为推荐人');
            }
            if (!$this->checkChangeParentAgent($id, $parentUserId)) {
                $this->error('不能绑定该上级');
            }
            LogModel::add($parentUserId, 'share', ['user' => $userAgent->user]);

            if ($userAgent->isAgentAvaliable()) {
                LogModel::add($id, 'bind', ['user' => $parentAgent->user ?? NULL]);
            }
        }

        $lastParentUserId = $userAgent->user->parent_user_id;

        $userAgent->user->parent_user_id = $parentUserId;
        $userAgent->user->save();

        if ($lastParentUserId > 0) {
            $userAgent->createAsyncAgentUpgrade($lastParentUserId);
        }

        if ($parentUserId > 0) {
            $userAgent->createAsyncAgentUpgrade($parentUserId);
        }
        $this->success('绑定成功');
    }

    // 递归往上找推荐人，防止出现推荐循环
    private function checkChangeParentAgent($userId, $parentUserId)
    {
        if ($userId == $parentUserId) {

            $this->error('推荐人不能是本人');
        }
        if ($parentUserId == 0) {
            return true;
        }

        $parentAgent = UserModel::find($parentUserId);

        if ($parentAgent) {
            if ($parentAgent->parent_user_id == $userId) {
                $this->error("已选中分销商的上级团队中已存在该用户");
            }
            if ($parentAgent->parent_user_id == 0) {
                return true;
            } else {
                return $this->checkChangeParentAgent($userId, $parentAgent->parent_user_id);
            }
        }

        return false;
    }
}
