<?php

namespace app\admin\controller\shopro;

use app\admin\model\shopro\Share as ShareModel;

class Share extends Common
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new ShareModel();
    }

    /**
     * 查看用户分享记录
     */
    public function index()
    {
        $share_id = $this->request->param('id');

        $data = ShareModel::with(['user' => function ($query) {
            return $query->field(['id', 'nickname', 'avatar']);
        }])->where('share_id', $share_id)->sheepFilter()->paginate($this->request->param('list_rows', 8));

        $this->success('', null, $data);
    }
}
