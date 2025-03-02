<?php

namespace addons\shopro\controller\activity;

use addons\shopro\controller\Common;
use addons\shopro\service\activity\Signin as SigninLibrary;

class Signin extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $params = $this->request->param();
        $month = (isset($params['month']) && $params['month']) ? date('Y-m', strtotime($params['month'])) : date('Y-m');     // 前端可能传来 2023-1,这里再统一格式化一下 month

        $signin = new SigninLibrary();
        $days = $signin->getList($month);

        $is_current = ($month == date('Y-m')) ? true : false;
        // 当前月，获取连续签到天数
        $continue_days = $signin->getContinueDays();

        $rules = $signin->getRules();

        $this->success('获取成功', compact('days', 'continue_days', 'rules'));
    }




    // 签到
    public function signin()
    {
        $signin = new SigninLibrary();
        $signin = $signin->signin();

        $this->success('签到成功', $signin);
    }



    // 补签
    public function replenish()
    {
        $params = $this->request->param();
        $this->svalidate($params, ".replenish");

        $signin = new SigninLibrary();
        $signin = $signin->replenish($params);

        $this->success('补签成功', $signin);
    }
}
