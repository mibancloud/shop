<?php

namespace app\admin\controller\shopro\chat;

use think\Db;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\chat\User as ChatUser;
use app\admin\model\shopro\chat\ServiceLog;
use app\admin\model\shopro\chat\Record;

class User extends Common
{
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new ChatUser;
    }

    /**
     * 会话列表
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $user = $this->model->sheepFilter()->with(['user', 'customer_service'])->where('auth', 'user')->order('id desc')->paginate(request()->param('list_rows', 10));
        $this->success('获取成功', null, $user);
    }


    /**·
     * 删除会话
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
        Db::transaction(function () use ($list) {
            foreach ($list as $user) {
                // 删除这个会话的所有服务记录
                ServiceLog::where('chat_user_id', $user->id)->delete();

                // 删除这个会话的所有聊天记录
                Record::where('chat_user_id', $user->id)->delete();
                
                // 删除这个会话
                $user->delete();
            }
        });

        $this->success('删除成功');
    }
}
