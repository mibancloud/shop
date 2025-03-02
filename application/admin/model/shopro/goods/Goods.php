<?php

namespace app\admin\model\shopro\goods;

use app\admin\model\shopro\Common;
use traits\model\SoftDelete;
use addons\shopro\library\Tree;
use app\admin\model\shopro\Category;
use app\admin\model\shopro\activity\Activity as ActivityModel;
use app\admin\model\shopro\activity\SkuPrice as ActivitySkuPriceModel;
use app\admin\model\shopro\app\ScoreSkuPrice;
use app\admin\model\shopro\user\GoodsLog;
use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\order\OrderItem;
use addons\shopro\facade\Activity as ActivityFacade;


class Goods extends Common
{

    use SoftDelete;

    protected $deleteTime = 'deletetime';

    // 表名
    protected $name = 'shopro_goods';

    protected $type = [
        'images' => 'json',
        'image_wh' => 'json',
        'params' => 'json',
    ];

    // 追加属性
    protected $append = [
        'status_text',
        'type_text',
        'dispatch_type_text'
    ];


    protected $hidden = [
        'content',
        'max_sku_price',
        'score_sku_prices',
        'activity_sku_prices',
        'total_sales'       // 商品列表，销量排序会用到
    ];

    /**
     * type 中文
     */
    public function typeList()
    {
        return [
            'normal' => '实体商品',
            'virtual' => '虚拟商品',
            'card' => '电子卡密',
        ];
    }

    /**
     * status 中文
     */
    public function statusList()
    {
        return [
            'up' => '上架中',
            'down' => '已下架',
            'hidden' => '已隐藏',
        ];
    }

    /**
     * status 中文
     */
    public function dispatchTypeList()
    {
        return [
            'express' => '快递物流',
            'autosend' => '自动发货',
            'custom' => '商家发货'
        ];
    }


    /**
     * 修改器 service_ids
     *
     * @param array|string $value
     * @param array $data
     * @return string
     */
    public function setServiceIdsAttr($value, $data)
    {
        $service_ids = is_array($value) ? join(',', $value) : $value;
        return $service_ids;
    }


    public function setIsOfflineAttr($value, $data)
    {
        // 除了实体商品，其他都是 0
        return $data['type'] == 'normal' ? $value : 0;
    }


    public function scopeShow($query)
    {
        return $query->whereIn('status', ['up', 'hidden']);
    }


    public function getDispatchTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['dispatch_type'] ?? null);

        $list = $this->dispatchTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPriceAttr($value, $data)
    {
        // 前端传入的 session_id
        $activity_id = session('goods-activity_id:' . $data['id']);
        if ($activity_id && $this->activity) {
            // 活动商品的价格
            $skuPrices = $data['new_sku_prices'] ?? [];
            $prices = $skuPrices instanceof \think\Collection ? $skuPrices->column('min_price') : array_column($skuPrices, 'min_price');
            $maxPrices = $skuPrices instanceof \think\Collection ? $skuPrices->column('max_price') : array_column($skuPrices, 'max_price');
            $min_price = $prices ? min($prices) : $data['price'];
            $max_price = $maxPrices ? max($maxPrices) : $data['price'];
            $priceArr[] = $min_price;
            if ($min_price < $max_price) {
                $priceArr[] = $max_price;
            }
        } else if (isset($data['show_score_shop'])) {
            // 积分商品价格
            $skuPrices = $data['new_sku_prices'] ?? [];
            $skuPrices = $skuPrices instanceof \think\Collection ? $skuPrices->column(null, 'score') : array_column($skuPrices, null, 'score');
            ksort($skuPrices);
            $skuPrice = current($skuPrices);      // 需要积分最少的规格
            if ($skuPrice) {
                // 不自动拼接积分
                // $price = $skuPrice['score'] . '积分';
                // if ($skuPrice['price'] > 0) {
                //     $price .= '+￥' . $skuPrice['price'];
                // }
                // $priceArr[] = $price;
                $priceArr[] = $skuPrice['price'];
            } else {
                // 防止没有规格
                $priceArr[] = $data['price'];
            }
        } else {
            // 普通商品的价格区间
            $price = $value ? $value : ($data['price'] ?? 0);
            $priceArr = [$price];
            if ($price && isset($data['is_sku']) && $data['is_sku']) {
                $max_price = $this->max_sku_price->price;
                if ($price < $max_price) {
                    $priceArr[] = $max_price;
                }
            }
        }

        return $priceArr;
    }



    /**
     * 这个目前只有拼团单独购买要使用
     *
     * @return void
     */
    public function getOriginalGoodsPriceAttr($value, $data)
    {
        $activity_id = session('goods-activity_id:' . $data['id']);
        $priceArr = [];
        if ($activity_id && $this->activity) {
            // 活动商品的价格
            $skuPrices = $data['new_sku_prices'] ?? [];
            $prices = $skuPrices instanceof \think\Collection ? $skuPrices->column('old_price') : array_column($skuPrices, 'old_price');
            $min_price = $prices ? min($prices) : $data['price'];
            $max_price = $prices ? max($prices) : $data['price'];
            $priceArr[] = $min_price;
            if ($min_price < $max_price) {
                $priceArr[] = $max_price;
            }
        }

        return $priceArr;
    }


    /**
     * 前端积分商城列表，获取默认所需积分（价格不自动拼接积分了，所以这里单独设置一个属性）
     */
    public function getScoreAttr($value, $data)
    {
        // 积分商品价格
        $skuPrices = $data['new_sku_prices'] ?? [];
        $skuPrices = $skuPrices instanceof \think\Collection ? $skuPrices->column(null, 'score') : array_column($skuPrices, null, 'score');
        ksort($skuPrices);
        $skuPrice = current($skuPrices);      // 需要积分最少的规格
        if ($skuPrice) {
            $scoreAmount = $skuPrice['score'] ?? 0;
        } else {
            // 防止没有规格
            $scoreAmount = 0;
        }

        return $scoreAmount;
    }


    public function getSalesAttr($value, $data)
    {
        // 前端传入的 session_id
        $activity_id = session('goods-activity_id:' . $data['id']);
        $sales = $data['sales'] ?? 0;
        $sales += ($data['show_sales'] ?? 0);
        if ($activity_id && $this->activity) {
            if ($this->activity['rules'] && isset($this->activity['rules']['sales_show_type']) && $this->activity['rules']['sales_show_type'] == 'real') {
                // 活动设置显示真实销量
                $skuPrices = $data['new_sku_prices'] ?? [];
                $sales = array_sum($skuPrices instanceof \think\Collection ? $skuPrices->column('sales') : array_column($skuPrices, 'sales'));
            }
        } else if (isset($data['show_score_shop'])) {
            // 积分商城显示真实销量
            $skuPrices = $data['new_sku_prices'] ?? [];
            $sales = array_sum($skuPrices instanceof \think\Collection ? $skuPrices->column('sales') : array_column($skuPrices, 'sales'));
        }

        return $sales;
    }



    /**
     * 真实销量
     */
    public function getRealSalesAttr($value, $data)
    {
        $sales = $data['sales'] ?? 0;
        return $sales;
    }


    /**
     * 相关商品（包含活动）购买用户，只查三个
     *
     * @param [type] $value
     * @param [type] $data
     * @return void
     */
    public function getBuyersAttr($value, $data)
    {
        // 查询活动正在购买的人Goods
        $activity_id = session('goods-activity_id:' . $data['id']);
        $orderItems = OrderItem::with(['user' => function ($query) {
            return $query->field('id,nickname,avatar');
        }])->whereExists(function ($query) {
            $order_name = (new Order())->getQuery()->getTable();
            $order_item_name = (new OrderItem())->getQuery()->getTable();

            $query->table($order_name)->where($order_item_name . '.order_id=' . $order_name . '.id')->whereIn('status', [Order::STATUS_PAID, Order::STATUS_COMPLETED, Order::STATUS_PENDING]);
        });

        if ($activity_id) {
            $orderItems = $orderItems->where('activity_id', $activity_id);
        }

        $orderItems = $orderItems->fieldRaw('max(id),user_id')->where('goods_id', $data['id'])->group('user_id')->limit(3)->select();

        $user = [];
        foreach ($orderItems as $item) {
            if ($item['user']) {
                $user[] = $item['user'];
            }
        }

        return $user;
    }


    public function getIsScoreShopAttr($value, $data)
    {
        $scoreGoodsIds = ScoreSkuPrice::group('goods_id')->cache(20)->column('goods_id');

        return in_array($data['id'], $scoreGoodsIds) ? 1 : 0;
    }


    /**
     * 获取当前商品所属分类的所有上级
     *
     * @param string $value
     * @param array $data
     * @return array
     */
    public function getCategoryIdsArrAttr($value, $data)
    {
        $categoryIds = $data['category_ids'] ? explode(',', $data['category_ids']) : [];

        $categoryIdsArr = [];
        $category = new Category();

        foreach ($categoryIds as $key => $category_id) {
            $currentCategoryIds = (new Tree($category))->getParentFields($category_id);
            if ($currentCategoryIds) {
                $categoryIdsArr[] = $currentCategoryIds;
            }
        }

        return $categoryIdsArr;
    }


    /**
     * 获取服务ids数组
     *
     * @param [type] $value
     * @param [type] $data
     * @return void
     */
    public function getServiceIdsAttr($value, $data)
    {
        $serviceIds = $this->attrFormatComma($value, $data, 'service_ids', true);

        return $serviceIds ? array_values(array_filter(array_map("intval", $serviceIds))) : $serviceIds;
    }



    /**
     * 获取所有服务列表
     *
     * @param string $value
     * @param array $data
     * @return array
     */
    public function getServiceAttr($value, $data)
    {
        $serviceIds = $this->attrFormatComma($value, $data, 'service_ids');

        $serviceData = [];
        if ($serviceIds) {
            $serviceData = Service::whereIn('id', $serviceIds)->select();
        }
        return $serviceData;
    }


    /**
     * 获取规格列表
     *
     * @param string $value
     * @param array $data
     * @return array
     */
    public function getSkusAttr($value, $data)
    {
        $sku = Sku::with('children')->where('goods_id', $data['id'])->where('parent_id', 0)->select();
        return $sku;
    }

    /**
     * 获取规格项列表
     *
     * @param string $value
     * @param array $data
     * @return array
     */
    public function getSkuPricesAttr($value, $data)
    {
        $skuPrices = collection(SkuPrice::where('goods_id', $data['id'])->select());
        return $skuPrices;
    }



    /**
     * 获取器获取所有活动
     *
     * @param string $value
     * @param array $data
     * @return array
     */
    public function getActivitiesAttr($value, $data)
    {
        $activities = ActivityFacade::getGoodsActivitys($data['id']);

        return $activities;
    }


    /**
     * 获取器获取指定活动
     *
     * @param string $value
     * @param array $data
     * @return array
     */
    public function getActivityAttr($value, $data)
    {
        $activity_id = session('goods-activity_id:' . $data['id']);
        $activities = ActivityFacade::getGoodsActivityByActivity($data['id'], $activity_id);

        return $activities;
    }


    public function getPromosAttr($value, $data)
    {
        $promos = ActivityFacade::getGoodsPromos($data['id']);

        foreach ($promos as $key => $promo) {
            $rules = $promo['rules'];
            $rules['simple'] = true;
            $tags = ActivityFacade::formatRuleTags($rules, $promo['type']);

            $promo['tag'] = $tags[0] ?? '';
            $promo['tags'] = $tags;

            $texts = ActivityFacade::formatRuleTexts($rules, $promo['type']);
            $promo['texts'] = $texts;

            $promos[$key] = $promo;
        }

        return $promos ?? [];
    }


    /**
     * 积分商城价格，积分商城的属性
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getScorePriceAttr($value, $data)
    {
        $scoreSkuPrices = collection($this->score_sku_prices)->column(null, 'score');
        ksort($scoreSkuPrices);
        $scoreSkuPrice = current($scoreSkuPrices);      // 需要积分最少的规格
        // print_r($scoreSkuPrices);exit;
        if ($scoreSkuPrice) {
            $price['score'] = $scoreSkuPrice['score'] ?? 0;
            $price['price'] = $scoreSkuPrice['price'] ?? 0;
            return $price;
        } else {
            return null;
        }

        // return $score . '积分' . ($price > 0 ? '+￥' . $price : '');
    }


    /**
     * 积分商城销量
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getScoreSalesAttr($value, $data)
    {
        $scoreSkuPrices = $this->score_sku_prices;

        return array_sum(collection($scoreSkuPrices)->column('sales'));
    }


    /**
     * 积分商城库存
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getScoreStockAttr($value, $data)
    {
        $scoreSkuPrices = $this->score_sku_prices;

        return array_sum(collection($scoreSkuPrices)->column('stock'));
    }


    public function maxSkuPrice()
    {
        return $this->hasOne(SkuPrice::class, 'goods_id')->order('price', 'desc');
    }

    public function favorite()
    {
        $user = auth_user();
        $user_id = empty($user) ? 0 : $user->id;
        return $this->hasOne(GoodsLog::class, 'goods_id', 'id')->where('user_id', $user_id)->favorite();
    }

    public function activitySkuPrices()
    {
        return $this->hasMany(ActivitySkuPriceModel::class, 'goods_id');
    }

    public function scoreSkuPrices()
    {
        return $this->hasMany(ScoreSkuPrice::class, 'goods_id')->up();
    }


    /**
     * 包含下架的积分规格
     */
    public function allScoreSkuPrices()
    {
        return $this->hasMany(ScoreSkuPrice::class, 'goods_id');
    }

    public function delScoreSkuPrices()
    {
        return $this->hasMany(ScoreSkuPrice::class, 'goods_id')->removeOption('soft_delete')->whereNotNull('deletetime');      // 只查被删除的记录，这里使用 onlyTrashed 报错
    }

    // -- commission code start --
    public function commissionGoods()
    {
        return $this->hasOne(\app\admin\model\shopro\commission\CommissionGoods::class, 'goods_id');
    }
    // -- commission code end --
}
