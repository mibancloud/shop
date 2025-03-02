<?php

namespace addons\shopro\controller\data;

use addons\shopro\controller\Common;
use app\admin\model\shopro\data\Richtext as RichtextModel;


class Richtext extends Common
{

    protected $noNeedLogin = ['index'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $id = $this->request->param('id');

        $data = RichtextModel::where('id', $id)->find();
        if (!$data) {
            $this->error(__('No Results were found'));
        }

        $this->success('获取成功', $data);
    }
}
