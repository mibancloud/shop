<?php

namespace app\admin\controller\shopro\user;

use app\admin\controller\shopro\Common;
use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\user\Coupon as UserCouponModel;

class Coupon extends Common
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new UserCouponModel;
    }

    
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }
        $coupon_id = $this->request->param('coupon_id');

        $coupons = $this->model->sheepFilter()->with(['user', 'order'])
            ->where('coupon_id', $coupon_id)
            ->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $coupons);
    }
}
