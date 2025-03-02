<?php

namespace addons\shopro\library\activity\provider;

/**
 * 满额包邮
 */
class FreeShipping extends Base
{
    protected $rules = [
        "type" => "require",
        "full_num" => "require|float"
    ];



    protected $message  =   [
    ];


    protected $default = [
        "type" => "money",              // money=满足金额|num=满足件数
        "province_except" => '',        // 不包邮的省份
        "city_except" => '',        // 不包邮的城市
        "district_except" => '',        // 不包邮的地区
        "district_text" => [],        // 中文
        "full_num" => 0
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
        $full_num = $rules['full_num'] ?? ($rules['discounts'][0]['full_num'] ?? 0);       // activity_order 存的格式不一样是在 discount 里面包裹着
        $tags[] = $this->formatTag([
            'type' => $rules['type'],
            'full_num' => $full_num,
        ]);

        return array_values(array_filter($tags));
    }


    /**
     * 格式化 discount 折扣为具体优惠标签
     *
     * @param array $discountData
     * @return string
     */
    public function formatTag($discountData)
    {
        $tag = '满' . $discountData['full_num'] . ($discountData['type'] == 'money' ? '元' : '件') . '包邮';

        return $tag;
    }


    /**
     * 格式化 discount 折扣为具体优惠详情
     */
    public function formatTexts($rules, $type)
    {
        $text = '满' . $rules['full_num'] . ($rules['type'] == 'money' ? '元' : '件') . '即可包邮';
        if (isset($rules['district_text']) && $rules['district_text']) {
            $district = '';
            if (isset($rules['district_text']['province']) && $rules['district_text']['province']) {
                $district .= join('，', $rules['district_text']['province']) . '，';
            }
            if (isset($rules['district_text']['city']) && $rules['district_text']['city']) {
                $district .= join('，', $rules['district_text']['city']) . '，';
            }
            if (isset($rules['district_text']['district']) && $rules['district_text']['district']) {
                $district .= join('，', $rules['district_text']['district']) . '，';
            }

            if ($district) {
                $text .= " (不支持包邮地区：" . rtrim($district, '，') . ")";
            }
        }

        $texts[] = $text;

        return array_values(array_filter($texts));
    }



    public function getPromoInfo($promo, $data = [])
    {
        extract($this->promoGoodsData($promo));
        $rules = $promo['rules'];
        $userAddress = $data['userAddress'] ?? null;

        // 是按金额，还是按件数比较
        $compareif = $rules['type'] == 'num' ? 'promo_goods_num' : 'promo_goods_amount';

        // 判断除外的地区
        $district_except = isset($rules['district_except']) && $rules['district_except'] ? explode(',', $rules['district_except']) : [];
        $city_except = isset($rules['city_except']) && $rules['city_except'] ? explode(',', $rules['city_except']) : [];
        $province_except = isset($rules['province_except']) && $rules['province_except'] ? explode(',', $rules['province_except']) : [];
        if ($userAddress) {
            if (
                in_array($userAddress['district_id'], $district_except)
                || in_array($userAddress['city_id'], $city_except)
                || in_array($userAddress['province_id'], $province_except)
            ) {
                // 收货地址在非包邮地区，则继续循环下个活动
                return null;
            }
        } else if ($district_except || $city_except || $province_except) {
            // 没有选择收货地址，并且活动中包含地区限制,不计算活动
            return null;
        }

        if (${$compareif} < $rules['full_num']) {
            // 不满足条件，接着循环下个规则
            return null;
        }

        // 记录活动信息
        $promo_discount_info = [
            'activity_id' => $promo['id'],                                   // 活动id
            'activity_title' => $promo['title'],                             // 活动标题
            'activity_type' => $promo['type'],                               // 活动类型
            'activity_type_text' => $promo['type_text'],                     // 活动类型中文
            'promo_discount_money' => 0,                            // 这里无法知道真实运费减免，会在 orderCreate 后续计算完包邮优惠之后，改为真实减免的运费
            'promo_goods_amount' => $promo_goods_amount,            // 当前活动商品总金额
            'rule_type' => $rules['type'],                                      // 满多少元|还是满多少件
            'discount_rule' => [
                'full_num' => $rules['full_num']
            ],                                                                  // 满足的那条规则
            'goods_ids' => $goodsIds                                 // 这个活动包含的这次购买的商品
        ];

        return $promo_discount_info;
    }
}