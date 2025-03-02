<?php

namespace app\admin\controller\shopro\chat;

use think\Db;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\chat\Record as ChatRecord;

class Record extends Common
{
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new ChatRecord;
    }

    /**
     * 聊天列表
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $records = $this->model->sheepFilter()->order('id desc')->paginate($this->request->param('list_rows', 10));

        $morphs = [
            'customer' => \app\admin\model\shopro\chat\User::class,
            'customer_service' => \app\admin\model\shopro\chat\CustomerService::class,
        ];
        $records = morph_to($records, $morphs, ['sender_identify', 'sender_id']);
        
        $this->success('获取成功', null, $records);
    }

}
