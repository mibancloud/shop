<?php

namespace addons\shopro\library\activity\provider;

use addons\shopro\library\activity\Activity as ActivityManager;
use addons\shopro\library\activity\contract\ActivityInterface;
use addons\shopro\library\activity\traits\CheckActivity;
use app\admin\model\shopro\activity\SkuPrice as ActivitySkuPriceModel;

abstract class Base implements ActivityInterface
{
    use CheckActivity;

    /**
     * ActivityManager
     *
     * @var ActivityManager
     */
    protected $manager = null;

    protected $rules = [];


    protected $message = [];


    protected $default = [];


    public function __construct(ActivityManager $activityManager) 
    {
        $this->manager = $activityManager;    
    }


    public function validate($data)
    {
        $data = array_merge($this->default, $data);
        
        $validate = (new \think\Validate)->message($this->message)->rule($this->rules);
        if (!$validate->check($data)) {
            error_stop($validate->getError());
        }

        return $data;
    }


    public function check($params)
    {
        if ((isset($params['start_time']) && $params['start_time'] > $params['end_time']) || $params['end_time'] < date('Y-m-d H:i:s')) {
            error_stop('请设置正确的活动时间');
        }
        if (isset($params['prehead_time']) && $params['prehead_time'] > $params['start_time']) {
            error_stop('预热时间必须小于活动开始时间');
        }

        $rules = $this->validate($params['rules']);

        // 存在折扣，将折扣按照从小到大排序
        if (isset($rules['discounts']) && $rules['discounts']) {
            // 处理展示优惠，full 从小到大
            $discounts = $rules['discounts'];

            $discountsKeys = array_column($discounts, null, 'full');
            ksort($discountsKeys);
            $rules['discounts'] = array_values($discountsKeys);        // 优惠按照 full 从小到大排序
        }

        $params['rules'] = $rules;
        return $params;
    }


    /**
     * 附加活动信息
     *
     * @param string $type
     * @param array $rules
     * @return array
     */
    public function rulesInfo($type, $rules) 
    {
        return $rules;
    }

    public function save($activity, $params = [])
    {

    }

    public function showSkuPrice($skuPrice)
    {
        return $skuPrice;
    }


    public function formatTags($rules, $type)
    {

    }


    public function formatTag($discountData)
    {
        
    }


    public function formatTexts($rules, $type)
    {
        
    }

    public function recoverSkuPrices($goods, $activity)
    {
        return $goods['sku_prices'];
    }


    public function buyCheck($buyInfo, $activity)
    {
        return $buyInfo;
    }


    public function buy($buyInfo, $activity)
    {
        return $buyInfo;
    }
    
    
    public function buyOk($order, $user)
    {
        return $order;
    }


    public function buyFail($order, $type)
    {
        return $order;
    }


    public function getPromoInfo($promo, array $data = [])
    {
    }


    protected function promoGoodsData($promo) 
    {
        $promo_goods_amount = '0';          // 该活动中商品的总价
        $promo_goods_num = '0';            // 该活动商品总件数
        $goodsIds = [];                     // 该活动中所有的商品 id
        $promo_dispatch_amount = '0';       // 该活动中总运费

        // 活动中的商品总金额，总件数，所有商品 id
        foreach ($promo['goods'] as $buyInfo) {
            $promo_goods_amount = bcadd($promo_goods_amount, (string)$buyInfo['goods_amount'], 2);
            $promo_goods_num = bcadd($promo_goods_num, (string)$buyInfo['goods_num'], 2);
            $goodsIds[] = $buyInfo['goods_id'];

            $promo_dispatch_amount = bcadd($promo_dispatch_amount, (string)$buyInfo['dispatch_amount'], 2);
        }

        return compact(
            "promo_goods_amount",
            "promo_goods_num",
            "promo_dispatch_amount",
            "goodsIds"
        );
    }


    /**
     * 添加，编辑活动规格，type = stock 只编辑库存
     *
     * @param array $goodsList  商品列表
     * @param int $activity_id  活动 id
     * @param string $type  type = all 全部编辑，type = stock 只编辑库存
     * @return void
     */
    protected function saveSkuPrice($goodsList, $activity, \Closure $extCb = null)
    {
        //如果是编辑 先下架所有的规格产品,防止丢失历史销量数据;

        $type = 'all';
        if (request()->isPut() && $activity->status == 'ing') {
            // 修改并且是进行中的活动，只能改库存
            $type = 'stock';
        }

        ActivitySkuPriceModel::where('activity_id', $activity->id)->update(['status' => 'down']);

        foreach ($goodsList as $key => $goods) {
            $actSkuPrice[$key] = $goods['activity_sku_prices'];

            foreach ($actSkuPrice[$key] as $ke => $skuPrice) {
                if ($type == 'all') {
                    $current = $skuPrice;
                    // 处理 ext 回调
                    if ($extCb) {
                        $current = $extCb($current);
                    }
                } else {
                    $current = [
                        'id' => $skuPrice['id'],
                        'stock' => $skuPrice['stock'],
                        'status' => $skuPrice['status']     // 这个要去掉，不能改参与状态
                    ];
                }

                if ($current['id'] == 0) {
                    unset($current['id']);
                }
                unset($current['sales']);
                $current['activity_id'] = $activity->id;
                $current['goods_id'] = $goods['id'];

                $actSkuPriceModel = new ActivitySkuPriceModel();
                if (isset($current['id'])) {
                    // type == 'edit' 
                    $actSkuPriceModel = $actSkuPriceModel->find($current['id']);
                }

                if ($actSkuPriceModel) {
                    unset($current['createtime'], $current['updatetime']);
                    $actSkuPriceModel->allowField(true)->save($current);
                }
            }
        }

    }

}