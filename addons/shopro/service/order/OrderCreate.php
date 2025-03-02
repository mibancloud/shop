<?php

namespace addons\shopro\service\order;

use think\Db;
use addons\shopro\service\goods\GoodsService;
use addons\shopro\service\pay\PayOper;
use addons\shopro\exception\ShoproException;
use addons\shopro\facade\Activity as ActivityFacade;
use app\admin\model\shopro\goods\Goods;
use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\order\Address as OrderAddress;
use app\admin\model\shopro\order\Invoice as OrderInvoice;
use app\admin\model\shopro\dispatch\Dispatch;
use app\admin\model\shopro\user\Coupon;
use app\admin\model\shopro\user\Address as UserAddress;
use app\admin\model\shopro\user\Invoice as UserInvoice;
use addons\shopro\service\StockSale;

class OrderCreate
{

    protected $user = null;

    /**
     * 订单类型
     */
    protected $order_type = 'goods';

    /**
     * 是否需要收货地址，自动发货商品不需要选
     */
    protected $need_address = 1;

    /**
     * 用户选择的收货地址 id
     */
    protected $address_id = 0;

    /**
     * 选择的收货地址信息
     */
    protected $userAddress = null;

    /**
     * 货到付款的状态
     */
    protected $offline_status = 'none';

    /**
     * 当前参与的活动
     */
    protected $activity_id = 0;

    /**
     * 用户选择的开票信息
     */
    protected $invoice_id = 0;

    /**
     * 用户选择的优惠券 id
     */
    protected $coupon_id = 0;

    /**
     * 用户备注
     */
    protected $remark = 0;

    /**
     * 余额抵扣（余额和 微信|支付宝，混合支付时使用了）
     */
    protected $money = 0;

    /**
     * 发票配置
     */
    protected $invoiceConfig = [];

    /**
     * 类型，create:创建订单，calc:计算费用
     */
    protected $calc_type = 'create';

    protected $goodsList = [];

    protected $msg = null;

    protected $activity = [
        'activity' => null,             // 当前订单参与的活动
        'promos' => [],                 // 当前订单可能参与的促销（每个商品所涉及的促销的集合）
        'promo_infos' => [],             // 当前订单实际参与的促销
    ];
    
    
    /**
     * 订单相关费用
     */
    protected $orderData = [
        'goods_original_amount' => '0',       // 商品原始总价
        'goods_old_amount' => '0',                // 商品不参与活动时的总价
        'goods_amount' => '0',                // 商品总价
        'coupon_discount_fee' => '0',                // 优惠券优惠金额
        'promo_discount_fee' => 0,          // 当前促销优惠总金额 （包含满包邮的邮费）
        'total_discount_fee' => 0,              // 当前订单，总优惠金额（优惠券 + 活动优惠）
        'coupon' => null,                // 所使用的优惠券
        'coupon_goods_ids' => [],
        'dispatch_amount' => '0',             // 运费总价
        'real_dispatch_amount' => '0',             // 免邮减免之后的运费总价，不做计算使用
        'score_amount' => 0,                 // 订单总积分
        'free_shipping_goods_ids' => [],        // 满额包邮商品 ids 
        'dispatch_infos' => [],                 // 当前订单商品按照配送方式分组
        'order_amount' => 0,                    // 订单总费用
        'pay_fee' => 0                     // 应支付总金额 （减去优惠之后的）
    ];


    /**
     * 活动（拼团，秒杀） 和 促销(满减，满折),是否可以同时存在
     */
    protected $activity_promos = false;

    public function __construct($params)
    {

        $this->user = auth_user();

        $this->calcParams($params);
    }



    /**
     * 获取请求参数，初始化，并设置默认值
     *
     * @param array $params
     * @return array
     */
    public function calcParams($params)
    {
        $this->order_type = $params['order_type'] ?? 'goods';

        $this->activity_id = $params['activity_id'] ?? 0;
        // $groupon_id = $groupon_id ?? 0;        // 拼团的 团 id
        // $buy_type = $buy_type ?? 'alone';        // 拼团的 购买方式: alone=单独购买,groupon=开团
        
        $this->goodsList = $params['goods_list'] ?? [];
        
        $this->address_id = $params['address_id'] ?? 0;
        $this->invoice_id = $params['invoice_id'] ?? 0;

        $this->coupon_id = $params['coupon_id'] ?? 0;
        $this->remark = $params['remark'] ?? '';

        $this->money = (isset($params['money']) && $params['money'] > 0) ? $params['money'] : 0;

        // 获取商品信息
        $this->goodsListInit();
    }


    public function goodsListInit() 
    {
        foreach ($this->goodsList as $key => &$buyInfo) {
            $goods_sku_price_id = $buyInfo['goods_sku_price_id'];

            if ($this->order_type == 'score') {
                $goods = $this->getGoodsService()->score()->show()->where('id', $buyInfo['goods_id'])->findOrFail();
            } else {
                // 暂时保存当前商品要获取的活动的 id
                session('goods-activity_id:' . $buyInfo['goods_id'], $this->activity_id);
                $goods = $this->getGoodsService()->activity($this->activity_id)->show()->where('id', $buyInfo['goods_id'])->findOrFail();
                if ($this->activity_id) {
                    if (!$this->activity['activity'] = $goods->activity) {
                        $this->exception('活动不存在', true);
                    }
                    if ($this->activity['activity']['status'] != 'ing') {
                        $this->exception('活动' . $this->activity['activity']['status_text']);
                    }
                }

                if ($this->isCalcPromos()&& isset($goods['promos']) && $goods['promos']) {
                    $this->activity['promos'] = array_merge($this->activity['promos'], $goods['promos']);
                }
            }

            $skuPrices = $goods['new_sku_prices'];
            foreach ($skuPrices as $key => $skuPrice) {
                if ($skuPrice['id'] == $goods_sku_price_id && $skuPrice['status'] == 'up') {
                    $buyInfo['current_sku_price'] = $skuPrice;      // 当前购买规格
                    break;
                }
            }

            if (!isset($buyInfo['current_sku_price']) || !$buyInfo['current_sku_price']) {
                $this->exception('商品规格不存在', true);
            }

            $buyInfo['dispatch_type'] = $goods['dispatch_type'] ?: 'express';
            $buyInfo['dispatch_type_text'] = $goods['dispatch_type_text'] ?? '';
            $buyInfo['goods'] = $goods;
        }
    }


    /**
     * 下单前检测，商品状态，秒杀，拼团活动状态，必要的选择项（比如下单收货地址），收货地址等
     *
     * @param array $params，请求参数
     * @return array
     */
    public function calcCheck()
    {
        $offline_num = 0;       // 货到付款商品数量
        foreach ($this->goodsList as $key => &$buyInfo) {
            $goods = $buyInfo['goods'];
            $goods_sku_price_id = $buyInfo['goods_sku_price_id'];
            $goods_num = $buyInfo['goods_num'] ?? 1;

            // 最少购买一件
            $buyInfo['goods_num'] = intval($goods_num) < 1 ? 1 : intval($goods_num);

            if ($buyInfo['current_sku_price']['stock'] < $buyInfo['goods_num']) {
                $this->exception('商品 ' . $goods['title'] . ' 库存不足');
            } 

            // 校验活动规则
            if ($this->activity['activity']) {
                try {
                    $buyInfo = ActivityFacade::buyCheck($buyInfo, $this->activity['activity']);
                } catch (ShoproException $e) {
                    $this->exception($e->getMessage());
                }
            }

            if ($buyInfo['goods']['is_offline']) {
                $offline_num ++;
            }

            $buyInfo['activity_type'] = $this->activity['activity']['type'] ?? null;
        }

        if (!count($this->goodsList)) {
            $this->exception('请选择要购买的商品');
        }

        if ($this->activity_id && count($this->goodsList) > 1) {
            $this->exception($this->activity['activity']['type_text'] . '必须单独购买');
        }

        if ($this->order_type == 'score' && count($this->goodsList) > 1) {
            $this->exception('积分商品必须单独购买');
        }

        // 判断是否支持货到付款
        if ($offline_num < count($this->goodsList)) {
            $this->offline_status = $offline_num == 0 ? 'none' : 'disabled';
        } else {
            $this->offline_status = 'enable';
        }

        // 判断是否有需要收货地址的商品，新版每个商品需要选择配送方式
        $dispatchTypes = array_column($this->goodsList, 'dispatch_type');
        // 配送方式为 快递 必须填写收货地址
        if (in_array('express', $dispatchTypes)) {
            // 用户收货地址
            if ($this->address_id) {
                $this->userAddress = UserAddress::where("user_id", $this->user->id)->find($this->address_id);
            }

            if (is_null($this->userAddress) && $this->calc_type == 'create') {
                $this->exception("请选择正确的收货地址");
            }
        } else {
            // 不需要收货地址
            $this->need_address = 0;
        }
    }



    /**
     * 计算订单各种费用
     *
     * @return array
     */
    public function calcAmount()
    {
        // 计算商品金额
        foreach ($this->goodsList as $key => &$buyInfo) {
            $goods = $buyInfo['goods'];

            // 当前商品原始总价
            $current_goods_original_amount = bcmul($goods->original_price, $buyInfo['goods_num'], 2);
            $this->orderData['goods_original_amount'] = bcadd($this->orderData['goods_original_amount'], $current_goods_original_amount, 2);

            // 当前商品现在总价
            $current_goods_amount = bcmul($buyInfo['current_sku_price']->price, $buyInfo['goods_num'], 2);
            $this->orderData['goods_amount'] = bcadd($this->orderData['goods_amount'], $current_goods_amount, 2);

            // 当有活动时，计算作为普通商品时的商品总金额
            $current_goods_old_amount = $current_goods_amount;
            if ($this->activity['activity']) {
                $current_goods_old_amount = bcmul($buyInfo['current_sku_price']->old_price, $buyInfo['goods_num'], 2);
            }
            $this->orderData['goods_old_amount'] = bcadd($this->orderData['goods_old_amount'], $current_goods_old_amount, 2);

            // 当前商品所需积分
            $current_score_amount = 0;
            if ($this->order_type == 'score') {       // 积分商城规格
                $current_score_amount = $buyInfo['current_sku_price']->score * $buyInfo['goods_num'];
                $this->orderData['score_amount'] = $this->orderData['score_amount'] + $current_score_amount;
            }

            // 当前商品总重量
            $current_weight = bcmul($buyInfo['current_sku_price']->weight, $buyInfo['goods_num'], 2);

            // 将计算好的属性记录下来，插入订单 item 表使用
            $buyInfo['goods_original_amount'] = $current_goods_original_amount;         // 当前商品原始总金额（原价 * 数量）
            $buyInfo['goods_amount'] = $current_goods_amount;                           // 当前商品总金额（价格 * 数量）
            $buyInfo['score_amount'] = $current_score_amount;       // 商品所需积分（积分商城）
            $buyInfo['weight'] = $current_weight;       // 当前商品总重量
            $buyInfo['original_dispatch_amount'] = 0;        // 当前商品运费（未判断活动的，并且也未合并相同运费模板商品的原始运费）
            $buyInfo['dispatch_amount'] = 0;        // 当前商品运费，按照运费模板合并相同商品的真实运费）
            $buyInfo['dispatch_id'] = 0;        // 商品运费模板,如果有活动，并且活动包邮，这里是 0
            $buyInfo['promo_types'] = [];           // 商品参与的营销类型
            $buyInfo['dispatch_discount_fee'] = 0;           // 初始化每个商品运费优惠金额
            $buyInfo['promo_discount_fee'] = 0;           // 初始化每个商品的促销优惠金额（这里不包含运费优惠，实际 order_item 上是包含的）
            $buyInfo['coupon_discount_fee'] = 0;           // 初始化每个商品的优惠券优惠金额

            // 商品的配送模板 id
            $current_dispatch_id = $goods['dispatch_id'];

            // 获取快递配送数据
            if ($buyInfo['dispatch_type'] == 'express'
                && (!$this->activity['activity']            // 没有活动
                    || (
                        $this->activity['activity']             // 有活动，并且（没有是否包邮字段，或者是不包邮）
                        && (!isset($this->activity['activity']['rules']['is_free_shipping']) || !$this->activity['activity']['rules']['is_free_shipping'])
                    )
                )
            ) {
                if ($this->userAddress) {
                    if (!isset($this->orderData['dispatch_infos'][$goods['dispatch_id']]['finalExpress'])) {
                        $finalExpress = $this->getDispatchExpress($buyInfo['dispatch_type'], $goods['dispatch_id']);
                    } else {
                        $finalExpress = $this->orderData['dispatch_infos'][$goods['dispatch_id']]['finalExpress'];
                    }

                    $current_original_dispatch_amount = $this->calcDispatch($finalExpress, [
                        'goods_num' => $buyInfo['goods_num'],
                        'weight' => $current_weight
                    ]);

                    $this->orderData['dispatch_infos'][$goods['dispatch_id']]['finalExpress'] = $finalExpress;
                    $this->orderData['dispatch_infos'][$goods['dispatch_id']]['buyInfos'][] = $buyInfo;
                }
                
                $buyInfo['original_dispatch_amount'] = $current_original_dispatch_amount ?? 0;        // 没选收货地址时默认为 0
                $buyInfo['dispatch_id'] = $current_dispatch_id;
            } else if ($buyInfo['dispatch_type'] == 'autosend') {
                $this->getDispatchAutosend($buyInfo['dispatch_type'], $goods['dispatch_id']);

                $buyInfo['dispatch_id'] = $current_dispatch_id;
            }
        }

        // 计算应付总运费，商品中同一种运费模板聚合进行计算运费，然后再按照运费模板规则，将运费加权平分到每个商品
        foreach ($this->orderData['dispatch_infos'] as $key => &$info) {
            $finalExpress = $info['finalExpress'];
            $buy_num = 0;
            $weight = 0;
            foreach ($info['buyInfos'] as $k => $infoInfo) {
                $buy_num += $infoInfo['goods_num'];
                $weight = bcadd($weight, $infoInfo['weight'], 2);
            }
            // 聚合原始运费
            $current_dispatch_amount = $this->calcDispatch($finalExpress, [
                'goods_num' => $buy_num,
                'weight' => $weight
            ]);

            $info['current_dispatch_amount'] = $current_dispatch_amount;        // 记录当前运费模板下商品的运费

            // 将运费加权平分到当前运费模板中的每个商品
            if ($current_dispatch_amount) {
                $this->equalDispatch($current_dispatch_amount, $info['buyInfos'], [
                    'goods_num' => $buy_num,
                    'weight' => $weight,
                    'final_express' => $finalExpress
                ]);
            }

            $this->orderData['dispatch_amount'] = bcadd($this->orderData['dispatch_amount'], $current_dispatch_amount, 2);
        }
    }



    /**
     * 计算商品的优惠促销
     *
     * @return void
     */
    public function calcDiscount() {
        // 过滤重复促销
        $promos = [];
        foreach ($this->activity['promos'] as $promo) {
            if (!isset($promos[$promo['id']])) {
                $promos[$promo['id']] = $promo;
            }
        }

        // 将购买的商品，按照促销分类
        foreach ($this->goodsList as $buyInfo) {
            $goods = $buyInfo['goods'];
            unset($buyInfo['goods']);
            if (isset($goods['promos']) && $goods['promos']) {
                foreach ($goods['promos'] as $promo) {
                    $promos[$promo['id']]['goods'][] = $buyInfo;
                }
            }
        }

        // 计算各个促销是否满足
        foreach ($promos as $key => $promo) {
            if (!isset($promo['goods'])) {
                // 促销没有商品，直接 next
                continue;
            }

            $promoInfo = ActivityFacade::getPromoInfo($promo, [
                'userAddress' => $this->userAddress
            ]);

            if ($promoInfo) {
                if (in_array($promo['type'], ['full_reduce', 'full_discount'])) {
                    // 这里目前只计算，满减，满折
                    $this->orderData['promo_discount_fee'] = bcadd($this->orderData['promo_discount_fee'], $promoInfo['promo_discount_money'], 2);
                    
                    // 将当前优惠平分到参与该促销的商品上
                    foreach ($this->goodsList as &$buyInfo) {
                        if (in_array($buyInfo['goods_id'], $promoInfo['goods_ids'])) {
                            $scale = bcdiv($buyInfo['goods_amount'], $promoInfo['promo_goods_amount'], 6);
        
                            // 当前商品，当前促销分配的优惠金额
                            $current_promo_discount_fee = round(bcmul($promoInfo['promo_discount_money'], $scale, 3), 2);
                            $buyInfo['promo_discount_fee'] = bcadd($buyInfo['promo_discount_fee'], $current_promo_discount_fee, 2);           // 当前商品参与所有活动累计分配的优惠金额
                        }
                    }
                }

                if ($promo['type'] == 'free_shipping') {
                    // 满包邮涉及到的商品
                    $this->orderData['free_shipping_goods_ids'] = array_merge($this->orderData['free_shipping_goods_ids'], $promoInfo['goods_ids']);
                }

                $this->activity['promo_infos'][] = $promoInfo;
            }
        }

        // 计算实际应付总运费
        if ($this->orderData['free_shipping_goods_ids']) {
            // first:简单计算方式，谁包邮，就把 buyinfo 上的 dispatch_amount 计算为运费优惠
            $promo_dispatch_discount_fee = 0;
            foreach ($this->goodsList as $key => &$buyInfo) {
                if (in_array($buyInfo['goods_id'], $this->orderData['free_shipping_goods_ids'])) {
                    // 商品运费优惠金额就是 当前商品的运费                    
                    $buyInfo['dispatch_discount_fee'] = $buyInfo['dispatch_amount'];
                    $promo_dispatch_discount_fee = bcadd($promo_dispatch_discount_fee, $buyInfo['dispatch_amount'], 2);
                }
            }

            // 真实运费 = 总运费，减去总包邮优惠
            $this->orderData['real_dispatch_amount'] = bcsub($this->orderData['dispatch_amount'], $promo_dispatch_discount_fee, 2);
            $this->orderData['promo_discount_fee'] = bcadd($this->orderData['promo_discount_fee'], $promo_dispatch_discount_fee, 2);

            // second:复杂计算方式，一个运费模板中多个商品只有一个包邮时，包邮优惠重新计算，而不是直接使用 dispatch_amount
            // foreach ($this->orderData['dispatch_infos'] as $key => $info) {
            //     $finalExpress = $info['finalExpress'];
            //     $total_buy_num = 0;     // 总购买数量
            //     $total_weight = 0;      // 总购买重量
            //     $buy_num = 0;           // 不包邮购买数量
            //     $weight = 0;            // 不包邮购买重量

            //     $currentGoodsIds = array_column($info['buyInfos'], 'goods_id');
            //     $currentFreeGoodsIds = array_intersect($this->orderData['free_shipping_goods_ids'], $currentGoodsIds);  // 当前配送模板中的包邮商品
            //     $noFreeGoodsIds = array_diff($currentGoodsIds, $currentFreeGoodsIds);                                   // 当前配送模板中的不包邮商品
            //     foreach ($info['buyInfos'] as $k => $dispatchBuyInfo) {
            //         $total_buy_num += $dispatchBuyInfo['goods_num'];
            //         $total_weight = bcadd($total_weight, $dispatchBuyInfo['weight'], 2);

            //         if (!in_array($dispatchBuyInfo['goods_id'], $this->orderData['free_shipping_goods_ids'])) {
            //             // 不是包邮商品 累加
            //             $buy_num += $dispatchBuyInfo['goods_num'];
            //             $weight = bcadd($weight, $dispatchBuyInfo['weight'], 2);
            //         }
            //     }

            //     if ($currentFreeGoodsIds && $noFreeGoodsIds) {
            //         // 有免邮商品，也有非免邮商品，重新计算除包邮优惠后的运费
            //         $current_dispatch_amount = $this->calcDispatch($finalExpress, [
            //             'goods_num' => $buy_num,
            //             'weight' => $weight
            //         ]);

            //         // 模板总运费优惠金额
            //         $current_discount_dispatch_amount = bcsub($info['current_dispatch_amount'], $current_dispatch_amount, 2);
            //         if ($current_discount_dispatch_amount > 0) {
            //             // 将优惠金额按照运费模板平均分配
            //             $this->equalDispatchDiscount($currentFreeGoodsIds, $current_discount_dispatch_amount, [
            //                 'goods_num' => bcsub($total_buy_num, $buy_num),       // 包邮购买数量
            //                 'weight' => bcsub($total_weight, $weight, 2),             // 包邮购买重量
            //                 'final_express' => $finalExpress
            //             ]);

            //             // 将新的运费加权平均到每个 item 上
            //             $this->equalDispatch($current_dispatch_amount, $noFreeGoodsIds, [
            //                 'goods_num' => $buy_num,
            //                 'weight' => $weight,
            //                 'final_express' => $finalExpress
            //             ]);
            //         }
            //     } elseif ($currentFreeGoodsIds) {
            //         // 全部包邮了,将邮费优惠按件数或者重量分配了
            //         $current_discount_dispatch_amount = $info['current_dispatch_amount'];
            //         if ($current_discount_dispatch_amount > 0) {
            //             // 每个商品运费优惠，就是 dispatch_amount
            //             $this->allDispatchDiscount($currentFreeGoodsIds);
            //         }

            //         $current_dispatch_amount = 0;
            //     } else {
            //         // 全部不包邮，每个商品的 dispatch_discount_fee 为初始值，不需要处理每个商品分配到的运费优惠
            //         $current_dispatch_amount = $info['current_dispatch_amount'];
            //     }

            //     $this->orderData['real_dispatch_amount'] = bcadd($this->orderData['real_dispatch_amount'], $current_dispatch_amount, 2);
            // }
            // // 包邮活动优惠的总费用
            // $promo_dispatch_discount_fee = bcsub($this->orderData['dispatch_amount'], $this->orderData['real_dispatch_amount'], 2); 
            // $this->orderData['promo_discount_fee'] = bcadd($this->orderData['promo_discount_fee'], $promo_dispatch_discount_fee, 2);
            // second:复杂计算方式结束
        }

        // 将每个商品对应的 activity_type 放入 goods_list 中的 promo_types 中，重新计算真实的包邮优惠
        foreach ($this->activity['promo_infos'] as &$info) {
            // 寻找商品id 等于 $goods_id 的所有商品，存在同一个商品，购买多个规格的情况
            foreach ($this->goodsList as &$buyInfo) {
                if (in_array($buyInfo['goods_id'], $info['goods_ids'])) {
                    if (!in_array($info['activity_type'], $buyInfo['promo_types'])) {
                        $buyInfo['promo_types'][] = $info['activity_type'];
                    }

                    if ($info['activity_type'] == 'free_shipping') {
                        // 重新计算运费真实优惠
                        $info['promo_discount_money'] = bcadd($info['promo_discount_money'], $buyInfo['dispatch_discount_fee'], 2);
                    }
                }
            }
        }
    }



    /**
     * 计算优惠券的优惠
     *
     * @return void
     */
    public function calcCoupon() 
    {
        // 获取优惠券
        $this->orderData['coupon'] = $this->getCoupon($this->coupon_id);

        if ($this->orderData['coupon']) {
            $this->orderData['coupon_discount_fee'] = $this->orderData['coupon']['coupon_money'];     // 获取优惠券时计算的金额
            $this->orderData['coupon_goods_ids'] = $this->orderData['coupon']['goods_ids'];     // 获取优惠券时参与的商品

            // 将当前优惠券优惠金额平分到使用优惠券的商品上
            foreach ($this->goodsList as &$buyInfo) {
                if (in_array($buyInfo['goods_id'], $this->orderData['coupon_goods_ids'])) {
                    $scale = bcdiv($buyInfo['goods_amount'], $this->orderData['coupon']['goods_amount'], 6);

                    // 当前商品，当前活动分配的优惠金额
                    $current_coupon_discount_fee = round(bcmul($this->orderData['coupon_discount_fee'], $scale, 3), 2);
                    $buyInfo['coupon_discount_fee'] = bcadd($buyInfo['coupon_discount_fee'], $current_coupon_discount_fee, 2);           // 当前商品可用优惠券的累计优惠金额
                }
            }
        } else {
            $this->exception('优惠券不可用');
        }
    }


    /**
     * 获取用户的优惠券列表，可用和不可用的做区分
     *
     * @return array
     */
    public function getCoupons($calc_type = 'coupons') 
    {
        $this->calc_type = $calc_type;
        // 检查是否可下单
        $this->calcCheck();

        // 计算订单各种费用
        $this->calcAmount();

        // 用户可用优惠券列表
        $coupons = Coupon::with('coupon')->where('user_id', $this->user->id)->canUse()->select();

        $cannot_use = [];
        $can_use = [];
        foreach ($coupons as $key => $coupon) {
            $result = $this->checkCoupon($coupon);
            if (isset($result['can_use']) && $result['can_use']) {
                $can_use[] = $coupon;
            } else {
                $coupon->cannot_use_msg = $result['cannot_use_msg'];
                $cannot_use[] = $coupon;
            }
        }

        return compact('can_use', 'cannot_use');
    }


    public function getCoupon($coupon_id)
    {
        // 选用的优惠券
        $coupon = Coupon::with('coupon')->where('user_id', $this->user->id)->canUse()->find($coupon_id);

        if ($coupon) {
            $result = $this->checkCoupon($coupon);
            return (isset($result['can_use']) && $result['can_use']) ? $coupon : null;
        }

        return null;
    }



    /**
     * 检测用户优惠券是否可用
     *
     * @param array|object $coupon
     * @param string $type
     * @return array|object
     */
    private function checkCoupon($coupon)
    {
        $can_use = true;
        $cannot_use_msg = '';
        // （积分或者活动 并且 优惠券不支持优惠叠加）
        if (($this->order_type == 'score' || $this->activity_id) && !$coupon->is_double_discount) {
            $can_use = false;
            $cannot_use_msg = '优惠券不可与活动叠加';

            return compact('can_use', 'cannot_use_msg');
        }

        $goods_amount = 0;
        $goodsIds = [];     // 符合优惠券规则的 商品 ids
        if ($coupon->use_scope == 'all_use') {
            // 计算商品总金额
            foreach ($this->goodsList as $buyInfo) {
                $goods_amount = bcadd($goods_amount, $buyInfo['goods_amount'], 2);
                $goodsIds[] = $buyInfo['goods_id'];
            }
        } elseif ($coupon->use_scope == 'goods') {
            // 计算指定商品的总金额是否满足
            foreach ($this->goodsList as $buyInfo) {
                if (in_array($buyInfo['goods_id'], explode(',', $coupon->items))) {
                    $goods_amount = bcadd($goods_amount, $buyInfo['goods_amount'], 2);
                    $goodsIds[] = $buyInfo['goods_id'];
                }
            }
        } elseif ($coupon->use_scope == 'disabled_goods') {
            // 计算非指定的商品的总金额是否满足
            foreach ($this->goodsList as $buyInfo) {
                if (!in_array($buyInfo['goods_id'], explode(',', $coupon->items))) {
                    $goods_amount = bcadd($goods_amount, $buyInfo['goods_amount'], 2);
                    $goodsIds[] = $buyInfo['goods_id'];
                }
            }
        } elseif ($coupon->use_scope == 'category') {
            // 计算指定分类的商品的总金额
            foreach ($this->goodsList as $buyInfo) {
                // 取分类交集
                if (array_intersect(explode(',', $buyInfo['goods']['category_ids']), explode(',', $coupon->items))) {
                    $goods_amount = bcadd($goods_amount, $buyInfo['goods_amount'], 2);
                    $goodsIds[] = $buyInfo['goods_id'];
                }
            }
        }

        if ($coupon->enough <= $goods_amount) {
            $coupon['coupon_money'] = $coupon->amount;
            $coupon['goods_amount'] = $goods_amount;     // 参与优惠券的商品的总金额
            $coupon['goods_ids'] = $goodsIds;
            if ($coupon->type == 'discount') {
                $scale = bcmul($coupon->amount, 0.1, 3);
                $scale = $scale > 1 ? 1 : ($scale < 0 ? 0 : $scale);
                $coupon_money = bcmul(bcsub(1, $scale, 3), $goods_amount, 2);

                // 优惠金额最大为 coupon->max_amount;
                $coupon['coupon_money'] = $coupon->max_amount && $coupon_money > $coupon->max_amount ? $coupon->max_amount : $coupon_money;
            }
            $coupons[] = $coupon;
        } else {
            $can_use = false;
            if ($goods_amount > 0) {
                $cannot_use_msg = '商品金额不满足优惠券门槛';
            } else {
                $cannot_use_msg = '优惠券不支持该商品';
            }
        }

        return compact('can_use', 'cannot_use_msg');
    }



    /**
     * 获取匹配的配送规则
     *
     * @param string $dispatch_type
     * @param integer $dispatch_id
     * @return array
     */
    public function getDispatchExpress($dispatch_type, $dispatch_id)
    {
        // 物流快递
        $dispatch = Dispatch::with('express')->show()->where('type', $dispatch_type)->where('id', $dispatch_id)->find();
        if (!$dispatch) {
            $this->exception('配送方式不存在', true);
        }

        $finalExpress = null;
        foreach ($dispatch->express as $key => $express) {
            if (strpos($express->district_ids, strval($this->userAddress->district_id)) !== false) {
                $finalExpress = $express;
                break;
            }

            if (strpos($express->city_ids, strval($this->userAddress->city_id)) !== false) {
                $finalExpress = $express;
                break;
            }

            if (strpos($express->province_ids, strval($this->userAddress->province_id)) !== false) {
                $finalExpress = $express;
                break;
            }
        }

        if (empty($finalExpress)) {
            $this->exception('当前地区不在配送范围');
        }

        return $finalExpress;
    }


    /**
     * 获取匹配的配送规则
     *
     * @param string $dispatch_type
     * @param integer $dispatch_id
     * @return array
     */
    public function getDispatchAutosend($dispatch_type, $dispatch_id)
    {
        // 物流快递
        $dispatch = Dispatch::with(['autosend'])->show()->where('type', $dispatch_type)->where('id', $dispatch_id)->find();
        if (!$dispatch) {
            $this->exception('配送方式不存在', true);
        }

        return $dispatch;
    }


    public function calcDispatch($finalExpress, $data)
    {
        if (empty($finalExpress)) {
            // 没有找到 finalExpress，比如商品不在配送范围，直接返回 0
            return 0;
        }
        
        // 初始费用
        $dispatch_amount = $finalExpress->first_price;

        if ($finalExpress['type'] == 'number') {
            // 按件计算
            if ($finalExpress->additional_num && $finalExpress->additional_price) {
                // 首件之后剩余件数
                $surplus_num = $data['goods_num'] - $finalExpress->first_num;

                // 多出的计量
                $additional_mul = ceil(($surplus_num / $finalExpress->additional_num));
                if ($additional_mul > 0) {
                    $additional_dispatch_amount = bcmul($additional_mul, $finalExpress->additional_price, 2);
                    $dispatch_amount = bcadd($dispatch_amount, $additional_dispatch_amount, 2);
                }
            }
        } else {
            // 按重量计算
            if ($finalExpress->additional_num && $finalExpress->additional_price) {
                // 首重之后剩余重量
                $surplus_num = $data['weight'] - $finalExpress->first_num;

                // 多出的计量
                $additional_mul = ceil(($surplus_num / $finalExpress->additional_num));
                if ($additional_mul > 0) {
                    $additional_dispatch_amount = bcmul($additional_mul, $finalExpress->additional_price, 2);
                    $dispatch_amount = bcadd($dispatch_amount, $additional_dispatch_amount, 2);
                }
            }
        }
        
        return $dispatch_amount;
    }



    /**
     * 加权平均每个包邮商品，应该分配的优惠(一个运费模板中的商品，部分包邮，部分不包邮，要重新计算)
     *
     * @param [type] $currentFreeGoodsIds
     * @param [type] $current_dispatch_amount
     * @param array $data
     * @return void
     */
    public function equalDispatchDiscount($currentFreeGoodsIds, $dispatch_discount_amount, $data = [])
    {
        $goods_num = $data['goods_num'];
        $weight = $data['weight'];
        $finalExpress = $data['final_express'];     // 这里肯定有，否则走不到这个方法

        foreach ($this->goodsList as $key => &$buyInfo) {
            if (in_array($buyInfo['goods_id'], $currentFreeGoodsIds)) {
                $scale = 0;                             // 重量或者数量计算比例
                if ($finalExpress['type'] == 'number') {
                    // 按件
                    if (floatval($goods_num)) {          // 字符串 0.00 是 true, 这里转下类型在判断
                        $scale = bcdiv($buyInfo['goods_num'], $goods_num, 6);
                    }

                    $current_dispatch_discount_fee = round(bcmul($dispatch_discount_amount, $scale, 3), 2);
                } else {
                    // 按重量
                    if (floatval($weight)) {
                        $current_weight = bcmul($buyInfo['current_sku_price']->weight, $buyInfo['goods_num'], 2);
                        $scale = bcdiv($current_weight, $weight, 6);
                    }

                    $current_dispatch_discount_fee = round(bcmul($dispatch_discount_amount, $scale, 3), 2);
                }

                // 每个商品分配到的包邮优惠金额， 和订单 item 上的dispatch_fee 不一样，因为 剩余运费又重新计算了，这个优惠没有 dispatch_fee 大
                $buyInfo['dispatch_discount_fee'] = $current_dispatch_discount_fee;
            }
        }
    }


    /**
     * 同一运费模板额所有商品都包邮了，不需要重新计算，优惠金额就是 dispatch_amount
     *
     * @param [type] $currentFreeGoodsIds
     * @param [type] $current_dispatch_amount
     * @param array $data
     * @return void
     */
    public function allDispatchDiscount($currentFreeGoodsIds)
    {
        foreach ($this->goodsList as $key => &$buyInfo) {
            if (in_array($buyInfo['goods_id'], $currentFreeGoodsIds)) {
                // 每个商品分配到的包邮优惠金额， 和订单 item 上的dispatch_fee 不一样，因为 剩余运费又重新计算了，这个优惠没有 dispatch_fee 大
                $buyInfo['dispatch_discount_fee'] = $buyInfo['dispatch_amount'];
            }
        }
    }



    /**
     * 加权平分同一运费模板下商品实际应付运费
     *
     * @param float $dispatch_amount
     * @param array $currentBuyInfos
     * @param array $data
     * @return void
     */
    private function equalDispatch($dispatch_amount, $currentBuyInfos, $data)
    {
        $goods_num = $data['goods_num'];
        $weight = $data['weight'];
        $finalExpress = $data['final_express'];    

        // 当前运费模板中的商品 Ids
        $goodsIds = array_column($currentBuyInfos, 'goods_id');

        foreach ($this->goodsList as $key => &$buyInfo) {
            if (in_array($buyInfo['goods_id'], $goodsIds)) {
                $scale = 0;                             // 重量或者数量计算比例
                if ($finalExpress['type'] == 'number') {
                    // 按件
                    if ($goods_num) {          // 字符串 0.00 是 true, 这里转下类型在判断
                        $scale = bcdiv($buyInfo['goods_num'], $goods_num, 6);
                    }

                    $current_dispatch_amount = round(bcmul($dispatch_amount, $scale, 3), 2);
                } else {
                    // 按重量
                    if (floatval($weight)) {
                        $scale = bcdiv($buyInfo['weight'], $weight, 6);
                    }

                    $current_dispatch_amount = round(bcmul($dispatch_amount, $scale, 3), 2);
                }

                $buyInfo['dispatch_amount'] = $current_dispatch_amount;         // 每个商品分配到的实际应支付运费
            }
        }
    }



    public function calcReturn() {
        $invoiceConfig = sheep_config('shop.order.invoice');
        $this->invoiceConfig = [
            'status' => $invoiceConfig['status'] && $this->orderData['pay_fee'] ? 1 : 0,        // 可开具发票状态，
            'amount_type' => $invoiceConfig['amount_type'] ?? 'pay_fee',
            'user_invoice' => null
        ];

        if ($this->invoiceConfig['status'] && $this->invoice_id) {
            // 获取用户发票信息
            $this->invoiceConfig['user_invoice'] = UserInvoice::where('user_id', $this->user->id)->find($this->invoice_id);
            if (!$this->invoiceConfig['user_invoice']) {
                $this->exception('请选择正确的发票信息');
            }
        }

        $temp_remain_pay_fee = bcsub($this->orderData['pay_fee'], $this->money, 2);

        $result = [
            'goods_original_amount' => $this->orderData['goods_original_amount'],
            'goods_old_amount' => $this->orderData['goods_old_amount'],
            'goods_amount' => $this->orderData['goods_amount'],
            'dispatch_amount' => $this->orderData['dispatch_amount'],
            'real_dispatch_amount' => $this->orderData['real_dispatch_amount'],
            'order_amount' => $this->orderData['order_amount'],
            'pay_fee' => $this->orderData['pay_fee'],
            'temp_remain_pay_fee' => $temp_remain_pay_fee >= 0 ? $temp_remain_pay_fee : 0,     // 临时剩余应支付金额，订单确认页面显示
            'total_discount_fee' => $this->orderData['total_discount_fee'],
            'coupon_discount_fee' => $this->orderData['coupon_discount_fee'],
            'promo_discount_fee' => $this->orderData['promo_discount_fee'],     // 包含包邮的运费优惠
            'money' => $this->money,                                             // 余额支付部分
            "invoice_amount" => $this->orderData[$this->invoiceConfig['amount_type']]       // 可开票金额
        ];

        // 处理小数点保留两位小数
        foreach ($result as $key => $amount) {
            $result[$key] = number_format($amount, 2, '.', '');
        }

        // 合并不需要处理小数点的
        $result = array_merge($result, [
            'activity_id' => $this->activity['activity']['id'] ?? 0,
            'activity_type' => $this->activity['activity']['type'] ?? null,
            'activity_title' => $this->activity['activity']['title'] ?? null,
            'promo_types' => array_column($this->activity['promo_infos'], 'activity_type'),
            'score_amount' => $this->orderData['score_amount'],
            'goods_list' => $this->goodsList,
            'promo_infos' => $this->activity['promo_infos'],
            "invoice_status" => $this->invoiceConfig['status'],                 // 发票可开状态
            "invoice_amount_type" => $this->invoiceConfig['amount_type'],        // 发票金额类型
            "need_address" => $this->need_address,
            "offline_status" => $this->offline_status,
        ]);

        // 如果是下单，合并 优惠券， 收货地址
        if ($this->calc_type == 'create') {
            $result = array_merge($result, [
                "coupon" => $this->orderData['coupon'],
                "user_address" => $this->userAddress
            ]);
        } else {
            $result = array_merge($result, ['msg' => $this->msg]);
        }

        return $result;
    }



    /**
     * 计算订单
     *
     * @return void
     */
    public function calc($calc_type = 'calc') 
    {
        $this->calc_type = $calc_type;

        // 检查系统必要条件
        check_env(['bcmath', 'queue']);

        // 检查是否可下单
        $this->calcCheck();

        // 计算订单各种费用
        $this->calcAmount();

        // 计算订单各种优惠（promo 等）
        if ($this->isCalcPromos()) {        // 判断是否参与优惠
            $this->calcDiscount();
        }

        // 计算优惠券费用
        if ($this->coupon_id) {
            $this->calcCoupon();
        }

        // 订单应付金额
        $this->orderData['order_amount'] = bcadd($this->orderData['goods_amount'], $this->orderData['dispatch_amount'], 2);
        $this->orderData['total_discount_fee'] = bcadd($this->orderData['coupon_discount_fee'], $this->orderData['promo_discount_fee'], 2);
        $this->orderData['pay_fee'] = bcsub($this->orderData['order_amount'], $this->orderData['total_discount_fee'], 2);
        $this->orderData['pay_fee'] = $this->orderData['pay_fee'] < 0 ? 0 : $this->orderData['pay_fee'];

        return $this->calcReturn();
    }



    public function create($result)
    {
        $stockSale = new StockSale();

        try {
            // 如果失败，被扣除的库存，退回
            $order = $this->createOrder($result);

            $stockSale->successDelHashKey();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if ($e instanceof \think\exception\HttpResponseException) {
                $data = $e->getResponse()->getData();
                $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            }

            // 下单失败，检测并返还锁定的库存
            $stockSale->faildStockUnLock();

            error_stop($message);
        }

        // 重新获取订单
        $order = Order::with('items')->where('id', $order->id)->find();

        $pay_money = floatval($result['money']);
        if ($order->status == Order::STATUS_UNPAID && $pay_money > 0) {
            // 如果订单未支付，并且使用了余额混合支付 (支付失败，不影响订单状态)
            $order = Db::transaction(function () use ($order, $pay_money) {
                $payOper = new PayOper();
                // 余额支付，并判断是否直接支付成功了
                $order = $payOper->money($order, $pay_money, 'order');

                return $order;
            });
        }

        return $order;
    }


    private function createOrder($result)
    {
        $order = Db::transaction(function () use ($result) {
            $orderData['type'] = $this->order_type;
            $orderData['order_sn'] = get_sn($this->user->id);
            $orderData['user_id'] = $this->user->id;
            $orderData['activity_id'] = $result['activity_id'];
            $orderData['activity_type'] = $result['activity_type'];
            $orderData['promo_types'] = join(',', $result['promo_types']);
            $orderData['goods_original_amount'] = $result['goods_original_amount'];
            $orderData['goods_amount'] = $result['goods_amount'];
            $orderData['dispatch_amount'] = $result['dispatch_amount'];
            $orderData['remark'] = $this->remark;
            $orderData['order_amount'] = $result['order_amount'];
            $orderData['score_amount'] = $result['score_amount'];
            $orderData['pay_fee'] = $result['pay_fee'];
            $orderData['original_pay_fee'] = $result['pay_fee'];        // 记录支付总金额，后面可能会订单改价
            $orderData['remain_pay_fee'] = $result['pay_fee'];          // 这里还是显示应支付总金额，扣除动作放到实际支付时
            $orderData['total_discount_fee'] = $result['total_discount_fee'];
            $orderData['coupon_discount_fee'] = $result['coupon_discount_fee'];
            $orderData['promo_discount_fee'] = $result['promo_discount_fee'];
            $orderData['coupon_id'] = $result['coupon'] ? $result['coupon']['id'] : 0;
            $orderData['status'] = Order::STATUS_UNPAID;
            $orderData['platform'] = request()->header('platform');

            $ext = $result['promo_infos'] ? ['promo_infos' => $result['promo_infos']] : [];       // 促销活动信息
            if ($this->activity['activity']) {
                $rules = $this->activity['activity']['rules'];
                $ext['refund_type'] = $rules['refund_type'] ?? 'back';      // 退款方式，（目前主要针对拼团失败自动退款）
            }
            $ext['real_dispatch_amount'] = $result['real_dispatch_amount'];     // 真实运费，剪掉运费优惠金额

            $goods_discount_amount = bcsub($result['goods_old_amount'], $result['goods_amount'], 2);       // 如果参与活动时商品总价的优惠费用
            $ext['activity_discount_amount'] = $goods_discount_amount > 0 ? $goods_discount_amount : 0;     // 如果参与活动时的优惠费用
            if ($result['activity_id']) {
                $ext['activity_type'] = $result['activity_type'];
                $ext['activity_title'] = $result['activity_title'];
            }
            $ext['offline_status'] = $result['offline_status'];
            $ext['need_address'] = $result['need_address'];     // 后台判断是否有 编辑收货地址按钮
            $orderData['ext'] = $ext;

            // 发票开具状态
            $invoice_status = 0;    // 没有申请
            if ($result['invoice_status'] && $this->invoice_id) {
                // 可开具，并且申请了
                $invoice_status = 1;
            } else if ($result['invoice_status'] == 0) {
                // 不可开具
                $invoice_status = -1;
            }
            $orderData['invoice_status'] = $invoice_status;        // 可开具发票，并且申请了
            // 订单创建前
            $hookData = [
                'params' => $result,
                'order_data' => $orderData
            ];
            \think\Hook::listen('order_create_before', $hookData);

            $order = new Order();
            $order->save($orderData);

            // 添加收货地址信息
            if ($result['user_address']) {
                $this->createOrderAddress($order, $result);
            }

            // 添加发票信息
            if ($invoice_status == 1) {
                $this->createOrderInvoice($order, $result);
            }

            // 将优惠券使用掉
            if ($result['coupon']) {
                $result['coupon']->use_order_id = $order->id;
                $result['coupon']->use_time = time();
                $result['coupon']->allowField(true)->save();
            }

            // 添加 订单 item
            foreach ($result['goods_list'] as $key => $buyInfo) {
                $goods = $buyInfo['goods'];
                $current_sku_price = $buyInfo['current_sku_price'];

                $orderItem = new OrderItem();

                $orderItem->order_id = $order->id;
                $orderItem->user_id = $this->user->id;
                $orderItem->goods_id = $buyInfo['goods_id'];
                $orderItem->goods_type = $goods['type'];
                $orderItem->goods_sku_price_id = $buyInfo['goods_sku_price_id'];
                $orderItem->activity_id = $result['activity_id'];     // 商品当前参与的活动 id
                $orderItem->activity_type = $result['activity_type'];     // 商品当前的活动类型
                $orderItem->promo_types = join(',', $buyInfo['promo_types']);     // 商品当前的活动类型
                // 当前商品规格对应的 活动下对应商品规格的 id
                $orderItem->item_goods_sku_price_id = isset($current_sku_price['item_goods_sku_price']) ?
                            $current_sku_price['item_goods_sku_price']['id'] : 0;
                $orderItem->goods_sku_text = is_array($current_sku_price->goods_sku_text) ? join(',', $current_sku_price->goods_sku_text) : '';
                $orderItem->goods_title = $goods->title;
                $orderItem->goods_image = empty($current_sku_price->image) ? $goods->image : $current_sku_price->image;
                $orderItem->goods_original_price = $goods->original_price;
                $orderItem->goods_price = $current_sku_price->price;
                $orderItem->goods_num = $buyInfo['goods_num'] ?? 1;
                $orderItem->goods_weight = $current_sku_price->weight;
                $orderItem->discount_fee = bcadd(bcadd($buyInfo['promo_discount_fee'], $buyInfo['coupon_discount_fee'], 2), $buyInfo['dispatch_discount_fee'], 2);   // 当前商品所享受的折扣 （promo_discount_fee + coupon_discount_fee + dispatch_discount_fee）

                $pay_fee = bcsub($buyInfo['goods_amount'], bcadd($buyInfo['promo_discount_fee'], $buyInfo['coupon_discount_fee'], 2), 2);        // 商品总金额(不算运费)，减去（活动优惠（不包含包邮优惠） + 优惠券优惠）
                $orderItem->pay_fee = $pay_fee;        // 平均计算单件商品不算运费，算折扣时候的金额
                $orderItem->dispatch_status = 0;
                $orderItem->dispatch_fee = $buyInfo['dispatch_amount'];     // 运费模板中商品自动合并后的加权平均运费，没有扣除包邮优惠
                $orderItem->dispatch_type = $buyInfo['dispatch_type'];
                $orderItem->dispatch_id = $buyInfo['dispatch_id'] ? $buyInfo['dispatch_id'] : 0;
                $orderItem->aftersale_status = 0;
                $orderItem->comment_status = 0;
                $orderItem->refund_status = 0;

                $ext = [
                    'original_dispatch_amount' => $buyInfo['original_dispatch_amount'],         // 原始运费总金额(未判断活动的，并且也未合并相同运费模板商品的原始运费)
                    'promo_discount_fee' => bcadd($buyInfo['promo_discount_fee'], $buyInfo['dispatch_discount_fee'], 2),     // 促销优惠，包含满包邮
                    'dispatch_discount_fee' => $buyInfo['dispatch_discount_fee'],           // 包邮优惠，已经包含在 promo_discount_fee
                    'coupon_discount_fee' => $buyInfo['coupon_discount_fee'],               // 优惠券优惠
                    'activity_sku_price_ext' => $current_sku_price['ext'] ?? [],            // 活动规格 ext，保存备用    
                    'ladder' => $buyInfo['ladder'] ?? [],                                   // (阶梯拼团仅有)阶梯拼团,当前购买的阶梯
                ];
                if (isset($buyInfo['is_commission'])) {
                    $ext['is_commission'] = $buyInfo['is_commission'];
                }

                $orderItem->ext = $ext;
                $orderItem->save();
            }

            // 订单创建后
            $hookData = [
                'order' => $order,
                'activity' => $this->activity['activity'],
            ];
            \think\Hook::listen('order_create_after', $hookData);

            return $order;
        });

        return $order;
    }


    /**
     * 添加收货地址信息
     *
     * @param \think\Model $order
     * @param array $result
     * @return void
     */
    private function createOrderAddress($order, $result)
    {
        // 保存收货地址
        $orderAddress = new OrderAddress();
        $orderAddress->order_id = $order->id;
        $orderAddress->user_id = $this->user->id;
        $orderAddress->consignee = $result['user_address']->consignee;
        $orderAddress->mobile = $result['user_address']->mobile;
        $orderAddress->province_name = $result['user_address']->province_name;
        $orderAddress->city_name = $result['user_address']->city_name;
        $orderAddress->district_name = $result['user_address']->district_name;
        $orderAddress->address = $result['user_address']->address;
        $orderAddress->province_id = $result['user_address']->province_id;
        $orderAddress->city_id = $result['user_address']->city_id;
        $orderAddress->district_id = $result['user_address']->district_id;
        $orderAddress->save();
    }


    /**
     * 添加发票信息
     *
     * @param \think\Model $order
     * @param array $result
     * @return void
     */
    private function createOrderInvoice($order, $result) 
    {
        $userInvoice = $this->invoiceConfig['user_invoice'];

        // 保存收货地址
        $orderInvoice = new OrderInvoice();
        $orderInvoice->type = $userInvoice->type;
        $orderInvoice->order_id = $order->id;
        $orderInvoice->user_id = $userInvoice->user_id;
        $orderInvoice->name = $userInvoice->name;
        $orderInvoice->tax_no = $userInvoice->tax_no;
        $orderInvoice->address = $userInvoice->address;
        $orderInvoice->mobile = $userInvoice->mobile;
        $orderInvoice->bank_name = $userInvoice->bank_name;
        $orderInvoice->bank_no = $userInvoice->bank_no;
        $orderInvoice->amount = $result['invoice_amount'];
        $orderInvoice->status = 'unpaid';
        $orderInvoice->save();
    }


    /**
     * 判断并处理异常
     *
     * @param string $msg 处理结果
     * @param boolean $force 是否强制抛出异常
     * @return boolean
     */
    private function exception($msg, $force = false)
    {
        if ($this->calc_type == 'create' || $force) {
            // 如果是创建订单，或者强制抛出异常
            error_stop($msg);
        } else {
            // 预下单，记录第一条错误信息
            if (!$this->msg) {
                $this->msg = $msg;
            }
        }

        return false;
    }



    /**
     * 获取商品服务实例
     *
     * @return GoodsService
     */
    private function getGoodsService()
    {
        // 实例化商品服务
        return new GoodsService(function ($goods, $service) {
            // $goods->sku_prices;

            // 这个写法没用，只要判断 promos 还是会获取 promos
            // if (!$this->activity_id) {
            //     // 可能要参与的营销活动
            //     $goods->promos = $goods->promos;
            // }
            return $goods;
        });
    }


    /**
     * 是否计算促销
     *
     * @return bool
     */
    private function isCalcPromos()
    {
        if ($this->order_type == 'score') {
            return false;       // 积分商品不能参与促销
        } else if ($this->activity_id) {
            if ($this->activity_promos) {
                // 活动与促销共存
                return true;
            } else {
                // 参与活动，不能在参与促销
                return false;
            }
        } else {
            return true;
        }
    }
}
