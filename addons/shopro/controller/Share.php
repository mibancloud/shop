<?php

namespace addons\shopro\controller;

use think\Db;
use app\admin\model\shopro\Share as ShareModel;

class Share extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function add()
    {
        $params = $this->request->only(['shareId', 'spm', 'page', 'query', 'from', 'platform']);

        $user = auth_user();

        $shareInfo = ShareModel::log($user, $params);

        $this->success("");
    }

    /**
     * 查看分享记录
     */
    public function index()
    {
        $user = auth_user();
        $logs = ShareModel::with(['user' => function ($query) {
            return $query->field(['id', 'nickname', 'avatar']);
        }])->where('share_id', $user->id)->paginate($this->request->param('list_rows', 8));

        $this->success('获取成功', $logs);
    }
}
