<?php

namespace addons\shopro\library\activity\traits;

use think\Db;
use addons\shopro\traits\CouponSend;
use app\admin\model\shopro\activity\GiftLog;
use addons\shopro\service\Wallet as WalletService;

/**
 * 赠送赠品 （full_gift, groupon_lucky 幸运拼团未拼中）
 */
trait GiveGift
{
    use CouponSend;

    /**
     * 按照规则添加赠送日志
     * @param array|object $order
     * @param array|object $user
     * @param array $info   {"full":"100","types":"coupon=优惠券|score=积分|money=余额|goods=商品","coupon_ids":"赠优惠券时存在","total":"赠送优惠券总金额","score":"积分","money":"余额","goods_ids":"商品时存在",gift_num:"礼品份数"}
     * @return void
     */
    public function addGiftsLog($order, $user, $info)
    {
        $rules = $info['discount_rule'];

        Db::transaction(function () use ($order, $user, $info, $rules) {
            $types = $rules['types'];

            foreach ($types as $type) {
                extract($this->getTypeGift($rules, $type));

                $giftLog = new GiftLog();
                $giftLog->activity_id = $info['activity_id'];
                $giftLog->order_id = $order->id;
                $giftLog->user_id = $user->id;
                $giftLog->type = $type;
                $giftLog->gift = $gift;
                $giftLog->value = $value;
                $giftLog->rules = $rules;
                $giftLog->status = 'waiting';
                $giftLog->save();
            }
        });
    }


    /**
     * 标记礼品为赠送失败
     */
    public function checkAndFailGift($order, $fail_msg, $errors = null)
    {
        // 找到所有没有赠送的礼品，设置为 fail,fail_msg 订单退款
        $giftLogs = GiftLog::waiting()->where('order_id', $order->id)->lock(true)->select();
        foreach ($giftLogs as $giftLog) {
            $giftLog->status = 'fail';
            $giftLog->fail_msg = $fail_msg;
            $giftLog->errors = $errors;
            $giftLog->save();
        }
    }



    /**
     * 检查并赠送礼品
     *
     * @param array|object $order
     * @param array|object $user
     * @param array $promoInfos
     * @param string $event
     * @return void
     */
    public function checkAndGift($order, $user, $promoInfos, $event)
    {
        foreach ($promoInfos as $info) {
            if ($info['activity_type'] == 'full_gift') {
                $this->checkPromoAndGift($order, $user, $info, $event);
            }
        }
    }


    /**
     * 检查并赠送礼品
     *
     * @param array|object $order
     * @param array|object $user
     * @param array $infos
     * @param string $event
     * @return void
     */
    public function checkPromoAndGift($order, $user, $info, $event)
    {
        if ($info['event'] == $event) {
            // 判断领取次数
            $rules = $info['discount_rule'];
            $gift_num = $rules['gift_num'];     // 礼品数量

            // 查询已发放数量
            $send_num = GiftLog::where('activity_id', $info['activity_id'])->opered()->group('order_id')->count();

            $giftLogs = GiftLog::waiting()
                ->where('activity_id', $info['activity_id'])
                ->where('order_id', $order->id)
                ->select();

            if ($send_num >= $gift_num) {
                // 礼品已经发放完毕
                foreach ($giftLogs as $log) {
                    $log->status = 'fail';
                    $log->fail_msg = '礼品已经发完了';
                    $log->save();
                }

                return false;
            }

            // 查询当前用户已领取数量 (只算赠送成功的)
            $user_send_num = GiftLog::where('user_id', $order->user_id)->where('activity_id', $info['activity_id'])->finish()->group('order_id')->count();
            if ($info['limit_num'] > 0 && $user_send_num >= $info['limit_num']) {
                // 已经领取过了
                foreach ($giftLogs as $log) {
                    $log->status = 'fail';
                    $log->fail_msg = '已经领取过了，每人最多领取 ' . $info['limit_num'] . ' 份';
                    $log->save();
                }

                return false;
            }

            // 赠送礼品
            foreach ($giftLogs as $giftLog) {
                $this->{'gift' . $giftLog->type}($user, $giftLog);
            }
        }
    }



    /**
     * 赠送优惠券
     *
     * @param array|object $user
     * @param array|object $giftLog
     * @return void
     */
    public function giftCoupon($user, $giftLog)
    {
        $couponIds = explode(',', $giftLog->gift);

        $result = $this->giveCoupons($user, $couponIds);

        $giftLog->status = 'finish';
        if ($result['errors']) {
            $giftLog->status = 'fail';
            $giftLog->fail_msg = $result['success'] ? '优惠券部分发放成功' : '优惠券发放失败';
            $giftLog->errors = $result['errors'];
        }

        $giftLog->save();
    }



    /**
     * 赠送积分
     *
     * @param array|object $user
     * @param array|object $giftLog
     * @return void
     */
    public function giftScore($user, $giftLog)
    {
        $score = $giftLog->gift;

        // 增加用户积分
        WalletService::change($user, 'score', $score, 'activity_gift', [
            'activity_id' => $giftLog->activity_id,
            'order_id' => $giftLog->order_id,
            'user_id' => $giftLog->user_id,
            'type' => $giftLog->type,
            'gift' => $giftLog->gift,
            'value' => $giftLog->value,
        ]);

        $giftLog->status = 'finish';
        $giftLog->save();
    }


    /**
     * 赠送余额
     *
     * @param array|object $user
     * @param array|object $giftLog
     * @return void
     */
    public function giftMoney($user, $giftLog)
    {
        $money = $giftLog->gift;

        // 增加用户余额
        WalletService::change($user, 'money', $money, 'activity_gift', [
            'activity_id' => $giftLog->activity_id,
            'order_id' => $giftLog->order_id,
            'user_id' => $giftLog->user_id,
            'type' => $giftLog->type,
            'gift' => $giftLog->gift,
            'value' => $giftLog->value,
        ]);

        $giftLog->status = 'finish';
        $giftLog->save();
    }


    /**
     * 赠送商品（暂不开发）
     *
     * @param array|object $user
     * @param array|object $giftLog
     * @return void
     */
    public function giftGoods($user, $giftLog)
    {
        $goodsIds = explode(',', $giftLog->gift);

        // 赠送商品，暂不开发
        $giftLog->status = 'finish';
        $giftLog->save();
    }




    /**
     * 获取赠送的 gift 和价值
     *
     * @param array $rules
     * @param string $type
     * @return array
     */
    private function getTypeGift($rules, $type)
    {
        $gift = null;
        switch ($type) {
            case 'coupon':
                $gift = $rules['coupon_ids'];
                $value = $rules['total'];
                break;
            case 'score':
                $gift = $rules['score'];
                $value = $rules['score'];
                break;
            case 'money':
                $gift = $rules['money'];
                $value = $rules['money'];
                break;
            case 'goods':
                $gift = $rules['goods_ids'];
                $value = $rules['goods_ids'];
                break;
        }

        return compact('gift', 'value');
    }
}
