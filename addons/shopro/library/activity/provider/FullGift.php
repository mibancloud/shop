<?php

namespace addons\shopro\library\activity\provider;

use addons\shopro\library\activity\traits\GiveGift;
use app\admin\model\shopro\Coupon;
use app\admin\model\shopro\order\Order;

/**
 * 满额赠送
 */
class FullGift extends Base
{
    use GiveGift;

    protected $rules = [
        "limit_num" => "number|egt:0",
        "type" => "require",
        "event" => "require",
        "discounts" => "require|array"
    ];


    protected $message  =   [
        "discounts.require" => '请填写优惠规则',
        "discounts.array" => '请填写优惠规则',
    ];


    protected $default = [
        "limit_num" => 0,               // 每人可参与次数 0：不限制
        "type" => "money",              // money=满足金额|num=满足件数
        "event" => "confirm",              // 赠送时机 paid=支付完成|confirm=确认收货(必须全部确认收货才可以)|finish=订单完成（评价完成）
        "discounts" => []                      //{"full":"100",
        //                                     "types":"coupon=优惠券|score=积分|money=余额|goods=商品",
        //                                     "coupon_ids":"赠优惠券时存在",
        //                                     "total":"赠送优惠券总金额",
        //                                     "score":"积分",
        //                                     "money":"余额",
        //                                     "goods_ids":"商品时存在",
        //                                     "gift_num":"礼品份数"}
    ];

    protected $giftType = [
        'money' => '余额',
        'score' => '积分',
        'coupon' => '优惠券',
        'goods' => '商品',
    ];


    public function check($params, $activity_id = 0)
    {
        // 数据验证
        $params = parent::check($params);

        // 验证赠送规则字段
        $this->checkGiftDiscount($params['rules']['discounts']);

        // 检测活动之间是否存在冲突
        $this->checkActivityConflict($params, $params['goods_list'], $activity_id);

        return $params;
    }


    /**
     * 获取 赠送的优惠券列表
     *
     * @param string $type
     * @param array $rules
     * @return array
     */
    public function rulesInfo($type, $rules) 
    {
        $discounts = $rules['discounts'];
        foreach ($discounts as &$discount) {
            $discount['coupon_list'] = [];
            if (in_array('coupon', $discount['types']) && isset($discount['coupon_ids']) && $discount['coupon_ids']) {
                $discount['coupon_list'] = Coupon::statusHidden()->whereIn('id', $discount['coupon_ids'])->select();
            }
        }
        $rules['discounts'] = $discounts;
        return $rules;
    }


    public function formatTags($rules, $type)
    {
        $tags = [];
        $discounts = $rules['discounts'] ?? [];

        foreach ($discounts as $discount) {
            $tags[] = $this->formatTag([
                'type' => $rules['type'],
                'simple' => $rules['simple'] ?? false,       // 简单信息展示
                'full' => $discount['full'],
                'discount' => $discount
            ]);
        }

        return array_values(array_filter($tags));
    }


    /**
     * 格式化 discount 折扣为具体优惠标签
     *
     * @param string $type
     * @param array $discountData
     * @return string
     */
    public function formatTag($discountData)
    {
        $discount = $discountData['discount'];
        $gift_type_text = '';
        foreach ($discount['types'] as $type) {
            $gift_type_text = $gift_type_text . (isset($this->giftType[$type]) ? ',' . $this->giftType[$type] : '');
        }

        $tag = '满' . $discountData['full'] . ($discountData['type'] == 'money' ? '元' : '件');
        $tag .= '赠送' . ($discountData['simple'] ? '礼品' : trim($gift_type_text, ','));

        return $tag;
    }


    public function formatTexts($rules, $type)
    {
        $texts = [];
        $discounts = $rules['discounts'] ?? [];

        foreach ($discounts as $discount) {
            $text = '消费满' . $discount['full'] . ($rules['type'] == 'money' ? '元' : '件');
            $text .= '';
            foreach ($discount['types'] as $type) {
                $text .= '，';
                if ($type == 'money') {
                    $text .= '赠送' . $discount['money'] . '元余额 ';
                } elseif ($type == 'score') {
                    $text .= '赠送' . $discount['score'] . '积分 ';
                } elseif ($type == 'coupon') {
                    $text .= '赠送价值' . $discount['total'] . '元优惠券 ';
                }
            }

            $text .= ' (条件：活动礼品共 ' . $discount['gift_num'] . ' 份';
            if ($rules['limit_num'] > 0) {
                $text .= '，每人仅限参与 ' . $rules['limit_num'] . ' 次';
            }
            $text .= ')';
            $texts[] = $text;
        }

        return array_values(array_filter($texts));
    }




    public function getPromoInfo($promo, $data = [])
    {
        extract($this->promoGoodsData($promo));
        $rules = $promo['rules'];

        // 是按金额，还是按件数比较
        $compareif = $rules['type'] == 'num' ? 'promo_goods_num' : 'promo_goods_amount';

        // 将规则按照从大到校排列,优先比较是否满足最大规则
        $rulesDiscounts = isset($rules['discounts']) && $rules['discounts'] ? array_reverse($rules['discounts']) : [];    // 数组反转

        // 满减， 满折多个规则从大到小匹配最优惠
        foreach ($rulesDiscounts as $d) {
            unset($d['coupon_list']);           // 移除规则里面的 coupon_list
            if (${$compareif} < $d['full']) {
                // 不满足条件，接着循环下个规则
                continue;
            }

            // 记录该活动的一些统计信息
            $promo_discount_info = [
                'activity_id' => $promo['id'],                           // 活动id
                'activity_title' => $promo['title'],                     // 活动标题
                'activity_type' => $promo['type'],                       // 活动类型
                'activity_type_text' => $promo['type_text'],             // 活动类型中文
                'promo_discount_money' => 0,                            // 优惠金额 （赠送，不优惠）
                'promo_goods_amount' => $promo_goods_amount,            // 当前活动商品总金额
                'rule_type' => $rules['type'],                              // 满多少元|还是满多少件
                'discount_rule' => $d,                                      // 满足的那条规则
                "limit_num" => $rules['limit_num'],                     // 每个人可参与次数
                "event" => $rules['event'],                             // 赠送时机
                'goods_ids' => $goodsIds                         // 这个活动包含的这次购买的商品
            ];
            break;
        }

        return $promo_discount_info ?? null;
    }


    /**
     * 支付成功（货到付款下单），添加礼品记录
     *
     * @param array|object $order
     * @param array|object $user
     * @return void
     */
    public function buyOk($order, $user)
    {
        // 满赠送
        $ext = $order->ext;
        $promoInfos = $ext['promo_infos'];
        
        foreach ($promoInfos as $info) {
            if ($info['activity_type'] == 'full_gift') {
                // 满赠，开始赠送
                $this->addGiftsLog($order, $user, $info);
            }
        }

        $event = $order->status == Order::STATUS_PENDING ? 'pending' : 'paid';      // 货到付款不是真的付款，不能发放礼品 event 改为 pending

        // 检测并赠送礼品
        $this->checkAndGift($order, $user, $promoInfos, $event);
    }



    /**
     * 促销购买失败(退款)
     *
     * @param \think\Model $order
     * @param string $type
     * @return void
     */
    public function buyFail($order, $type) 
    {
        if ($type == 'refund') {
            // 退款,将礼品标记为已退款,如果已经送出去的不扣除
            $this->checkAndFailGift($order, '订单全额退款');
        } else if ($type == 'invalid') {
            if ($order->pay_mode == 'offline') {
                // 只有线下付款取消时才需要标记礼品赠送失败
                $this->checkAndFailGift($order, '货到付款订单取消');
            }
        }
    }
}