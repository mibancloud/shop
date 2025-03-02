<?php

namespace addons\shopro\controller\user;

use think\helper\Str;
use addons\shopro\controller\Common;
use app\admin\model\shopro\user\Coupon as UserCouponModel;

class Coupon extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $user = auth_user();
        $type = $this->request->param('type', 'can_use');     // 优惠券类型：geted=已领取，can_use=可用，cannot_use=暂不可用，used=已使用，expired=已过期,invalid=已失效（包含已使用和已过期）

        $userCoupons = UserCouponModel::with('coupon')->where('user_id', $user->id);

        if (in_array($type, ['geted', 'can_use', 'cannot_use', 'used', 'expired', 'invalid'])) {
            $userCoupons = $userCoupons->{Str::camel($type)}();
        }

        $userCoupons = $userCoupons->order('id', 'desc')->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', $userCoupons);
    }
}
