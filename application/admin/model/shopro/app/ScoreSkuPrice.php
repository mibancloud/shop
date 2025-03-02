<?php

namespace app\admin\model\shopro\app;

use app\admin\model\shopro\Common;
use traits\model\SoftDelete;
use app\admin\model\shopro\goods\SkuPrice;

class ScoreSkuPrice extends Common
{
    use SoftDelete;
    protected $name = 'shopro_score_sku_price';

    protected $deleteTime = 'deletetime';

    protected $type = [
    ];

    // 追加属性
    protected $append = [

    ];

    public function getGoodsSkuIdsAttr($value, $data) 
    {
        $skuPrice = $this->sku_price;
        return $skuPrice ? $skuPrice->goods_sku_ids : '';
    }


    public function getGoodsSkuTextAttr($value, $data)
    {
        $skuPrice = $this->sku_price;
        return $skuPrice ? $skuPrice->goods_sku_text : '';
    }

    public function getImageAttr($value, $data)
    {
        $skuPrice = $this->sku_price;
        return $skuPrice ? $skuPrice->image : '';
    }


    /**
     * 积分加现金
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getScorePriceAttr($value, $data)
    {
        $score = $data['score'] ?? 0;
        $price = $data['price'] ?? 0;

        return $score . '积分' . ($price ? '+￥' . $price : '');
    }


    public function skuPrice() {
        return $this->belongsTo(SkuPrice::class, 'goods_sku_price_id');
    }
}
