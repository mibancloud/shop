<?php

namespace addons\shopro\controller\chat;

use addons\shopro\controller\Common;
use app\admin\model\shopro\chat\Record as RecordModel;
use app\admin\model\shopro\chat\User as ChatUserModel;

class Record extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function noRead() 
    {
        $user = auth_user();
        $params = $this->request->param();
        $room_id = $params['room_id'] ?? 'admin';
        $session_id = $params['session_id'] ?? '';

        if (!$user && !$session_id) {
            $this->success('获取成功', null, 0);
        }

        // 查询客服用户
        $chatUser = ChatUserModel::where(function($query) use ($user, $session_id) {
            $query->where('auth', 'user')->where(function ($query) use ($user, $session_id) {
                if ($user) {
                    $query->where('auth_id', $user->id);
                }
                if ($session_id) {
                    $query->whereOr('session_id', $session_id);
                }
            });
        })->find();

        $no_read_num = 0;
        if($chatUser){
            // 查询未读消息数量
            $no_read_num = RecordModel::customer()->noRead()->where('room_id', $room_id)->where('sender_id', $chatUser->id)->count();
        }

        $this->success('获取成功', $no_read_num);
    }
}
