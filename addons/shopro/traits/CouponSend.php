<?php

namespace addons\shopro\traits;

use think\Db;
use think\exception\HttpResponseException;
use app\admin\model\shopro\Coupon;
use app\admin\model\shopro\user\Coupon as UserCouponModel;

/**
 * 发放优惠券
 */
trait CouponSend
{

    /**
     * 用户自己领取优惠券
     *
     * @param [type] $id
     * @return void
     */
    public function getCoupon($id) 
    {
        $user = auth_user();

        $userCoupon = Db::transaction(function () use ($user, $id) {
            $coupon = Coupon::normal()      // 正常的可以展示的优惠券
                ->canGet()      // 在领取时间之内的
                ->lock(true)
                ->where('id', $id)
                ->find();
            if (!$coupon) {
                error_stop('优惠券未找到');
            }

            $userCoupon = $this->send($user, $coupon);

            return $userCoupon;
        });

        return $userCoupon;
    }



    /**
     * 赠送优惠券
     *
     * @param array $user
     * @param array|string $ids
     * @return array
     */
    public function giveCoupons($user, $ids)
    {
        $ids = is_array($ids) ? $ids : explode(',', $ids);

        $result = Db::transaction(function () use ($user, $ids) {
            $errors = [];       // 发送失败的优惠券，包含失败原因
            $success = [];      // 发送成功的优惠券
            $coupons = Coupon::statusHidden()     // 只查询隐藏券（后台发放的券）
                ->canGet()
                ->whereIn('id', $ids)
                ->select();

            $findCouponIds = array_column($coupons, 'id');        // 找到的优惠券 ids
            $nofundIds = array_diff($ids, $findCouponIds);
            foreach ($nofundIds as $nofund_id) {
                $errors[] = ['id' => $nofund_id, 'error' => '优惠券未找到'];
            }
            foreach ($coupons as $coupon) {
                try {
                    $userCoupon = $this->send($user, $coupon);
                    $success[] = $coupon->id;
                } catch (HttpResponseException $e) {
                    $data = $e->getResponse()->getData();
                    $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
                    $errors[] = ['id' => $coupon->id, 'error' => $message];
                } catch (\Exception $e) {
                    $errors[] = ['id' => $coupon->id, 'error' => $e->getMessage()];
                }
            }
            $result['success'] = $success;
            $result['errors'] = $errors;

            return $result;
        });
        return $result;
    }


    public function manualSend($users, $id)
    {
        $coupon = Coupon::canGet()         // 在领取时间之内的
            ->lock(true)
            ->where('id', $id)
            ->find();

        if (!$coupon) {
            // 库存不足
            error_stop('优惠券不在发放时间段');
        }
        if ($coupon->stock < count($users)) {
            // 库存不足
            error_stop('优惠券库存不足');
        }

        // 扣除库存
        $coupon->setDec('stock', count($users));
        
        $sends = [];
        foreach ($users as $user) {
            $current = [
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
                'use_time' => null,
                'createtime' => time(),
                'updatetime' => time(),
            ];

            $sends[] = $current;
        }

        UserCouponModel::insertAll($sends);
    }




    /**
     * 发放优惠券
     *
     * @param array|object $user 发放用户
     * @param array|object $coupon 要发放的优惠券
     * @return array|object
     */
    private function send($user, $coupon) {
        if ($coupon->get_status == 'cannot_get') {
            error_stop('您已经领取过了');
        }

        if ($coupon->stock <= 0) {
            error_stop('优惠券已经被领完了');
        }

        $coupon->setDec('stock');

        $userCoupon = new UserCouponModel();
        $userCoupon->user_id = $user->id;
        $userCoupon->coupon_id = $coupon->id;
        $userCoupon->use_time = null;
        $userCoupon->save();

        return $userCoupon;
    }



    /**
     * 退回用户优惠券
     *
     * @param integer $user_coupon_id
     * @return void
     */
    public function backUserCoupon($user_coupon_id) 
    {
        $userCoupon = UserCouponModel::where('id', $user_coupon_id)->find();

        if ($userCoupon) {
            $userCoupon->use_time = null;
            $userCoupon->save();
        }
    }
}
