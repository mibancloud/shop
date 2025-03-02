<?php

namespace app\admin\model\shopro;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\goods\Goods;
use app\admin\model\shopro\goods\SkuPrice;
use addons\shopro\facade\Activity as ActivityFacade;

class Cart extends Common
{
    protected $name = 'shopro_cart';

    // 追加属性
    protected $append = [
    ];


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


    public function getStatusAttr($value, $data)
    {
        $status = 'normal';
        if (!$this->goods || !is_null($this->goods->deletetime) || !$this->sku_price) {
            $status = 'deleted';        // 已删除
        } else if ($this->goods->status == 'down' || $this->sku_price->status == 'down') {
            $status = 'down';           // 已下架
        } 

        return $status;
    }


    public function getTagsAttr($value, $data) 
    {
        $tags = [
            'activity' => [],
        ];

        $activities = $this->activities;
        foreach ($activities as $activity) {
            $tags['activity'][] = $activity['type_text'] . $activity['status_text'];
        } 

        if ($this->sku_price && $this->sku_price->price < $data['snapshot_price']) {
            // 当前规格价格，低于加入购物车时候的价格，则提示商品比加入时降价
            $tags['price'] = '距加入降 ￥ ' . bcsub($data['snapshot_price'], $this->sku_price->price, 2);
        }
    }


    public function goods() 
    {
        return $this->belongsTo(Goods::class, 'goods_id');
    }


    public function skuPrice()
    {
        return $this->belongsTo(SkuPrice::class, 'goods_sku_price_id');
    }
}
