<?php

namespace addons\shopro\library\activity\provider;

/**
 * 满额折扣
 */
class FullDiscount extends Base
{
    protected $rules = [
        "type" => "require",
        "discounts" => "require|array"
    ];


    protected $message  =   [
        "discounts.require" => '请填写优惠规则',
        "discounts.array" => '请填写优惠规则',
    ];


    protected $default = [
        "type" => "money",              // money=满足金额|num=满足件数
        "discounts" => []
    ];


    public function check($params, $activity_id = 0)
    {
        // 数据验证
        $params = parent::check($params);

        // 检测活动之间是否存在冲突
        $this->checkActivityConflict($params, $params['goods_list'], $activity_id);

        return $params;
    }


    public function formatTags($rules, $type)
    {
        $tags = [];
        $discounts = $rules['discounts'] ?? [];

        foreach ($discounts as $discount) {
            $tags[] = $this->formatTag([
                'type' => $rules['type'],
                'full' => $discount['full'],
                'discount' => $discount['discount']
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
        $tag = '满' . $discountData['full'] . ($discountData['type'] == 'money' ? '元' : '件');
        $tag .= $discountData['discount'] . '折';

        return $tag;
    }



    public function formatTexts($rules, $type)
    {
        $texts = [];
        $discounts = $rules['discounts'] ?? [];

        foreach ($discounts as $discount) {
            $text = '满' . $discount['full'] . ($rules['type'] == 'money' ? '元' : '件');
            $text .= '，商品总价打' . $discount['discount'] . '折';

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
            if (${$compareif} < $d['full']) {
                // 不满足条件，接着循环下个规则
                continue;
            }

            $dis = bcdiv($d['discount'], '10', 3);        // 保留三位小数，转化折扣
            $dis = $dis > 1 ? 1 : ($dis < 0 ? 0 : $dis);    // 定义边界 0 - 1
            $promo_dis = 1 - $dis;

            $current_promo_discount_money = bcmul($promo_goods_amount, (string)$promo_dis, 3);
            $current_promo_discount_money = number_format((float)$current_promo_discount_money, 2, '.', '');              // 计算折扣金额,四舍五入

            // 记录该活动的一些统计信息
            $promo_discount_info = [
                'activity_id' => $promo['id'],                           // 活动id
                'activity_title' => $promo['title'],                     // 活动标题
                'activity_type' => $promo['type'],                       // 活动类型
                'activity_type_text' => $promo['type_text'],             // 活动类型中文
                'promo_discount_money' => $current_promo_discount_money,      // 优惠金额
                'promo_goods_amount' => $promo_goods_amount,            // 当前活动商品总金额
                'rule_type' => $rules['type'],                              // 满多少元|还是满多少件
                'discount_rule' => $d,                                      // 满足的那条规则
                'goods_ids' => $goodsIds                         // 这个活动包含的这次购买的商品
            ];
            break;
        }

        return $promo_discount_info ?? null;
    }
}