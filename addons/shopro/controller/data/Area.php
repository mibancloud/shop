<?php

namespace addons\shopro\controller\data;

use addons\shopro\controller\Common;
use app\admin\model\shopro\data\Area as AreaModel;

class Area extends Common
{

    protected $noNeedLogin = ['index'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $list = AreaModel::sheepFilter()->with(['children' => function ($query) {
            return $query->field('id, pid, level, name')->with(['children' => function ($query) {
                return $query->field('id, pid, level, name');
            }]);
        }])->where('pid', 0)->order('id', 'asc')->field('id, pid, level, name')->select();

        $this->success('获取成功', $list);
    }
}
