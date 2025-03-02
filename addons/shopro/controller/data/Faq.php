<?php

namespace addons\shopro\controller\data;

use addons\shopro\controller\Common;
use app\admin\model\shopro\data\Faq as FaqModel;


class Faq extends Common
{

    protected $noNeedLogin = ['index'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $list = FaqModel::where('status', 'normal')->order('id', 'asc')->select();

        $this->success('获取成功', $list);
    }
}
