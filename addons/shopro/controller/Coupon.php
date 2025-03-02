<?php

namespace addons\shopro\controller;

use app\admin\model\shopro\Coupon as CouponModel;
use addons\shopro\traits\CouponSend;
use app\admin\model\shopro\goods\Goods as GoodsModel;

class Coupon extends Common
{
    use CouponSend;

    protected $noNeedLogin = ['index', 'listByGoods', 'detail'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $ids = $this->request->param('ids', '');

        $coupons = CouponModel::with(['user_coupons'])
            ->normal()      // 正常的可以展示的优惠券
            ->canGet()      // 在领取时间之内的
            ->order('id', 'desc');

        if ($ids) {
            $coupons = $coupons->whereIn('id', $ids);
        }

        $coupons = $coupons->paginate($this->request->param('list_rows', 10))->each(function ($coupon) {
            $coupon->get_status = $coupon->get_status;
            $coupon->get_status_text = $coupon->get_status_text;
        });

        $this->success('获取成功', $coupons);
    }


    /**
     * 商品相关的优惠券列表，前端商品详情使用
     *
     * @param Request $request
     * @param int $goods_id
     * @return void
     */
    public function listByGoods()
    {
        $user = auth_user();
        $goods_id = $this->request->param('goods_id');
        $goods = GoodsModel::field('id,category_ids')->where('id', $goods_id)->find();
        if (!$goods) {
            $this->error(__('No Results were found'));
        }

        $coupons = CouponModel::with(['user_coupons'])
            ->normal()      // 正常的可以展示的优惠券
            ->canGet()      // 在领取时间之内的
            ->goods($goods)       // 符合指定商品，并且检测商品所属分类
            ->order('id', 'desc');

        if ($user) {
            // 关联用户优惠券
            $coupons = $coupons->with(['userCoupons']);
        }
        $coupons = $coupons->select();
        
        $coupons = collection($coupons)->each(function ($coupon) {
            $coupon->get_status = $coupon->get_status;
            $coupon->get_status_text = $coupon->get_status_text;
        });

        $this->success('获取成功', $coupons);
    }



    public function get() 
    {
        $id = $this->request->param('id');

        $this->repeatFilter(null, 2);
        $userCoupon = $this->getCoupon($id);

        $this->success('领取成功', $userCoupon);
    }


    
    public function detail()
    {
        $id = $this->request->param('id');

        $coupon = CouponModel::where('id', $id)->find();
        if (!$coupon) {
            $this->error(__('No Results were found'));
        }

        $coupon->get_status = $coupon->get_status;
        $coupon->get_status_text = $coupon->get_status_text;
        $coupon->items_value = $coupon->items_value;

        $this->success('优惠券详情', $coupon);
    }
}
