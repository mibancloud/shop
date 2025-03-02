<?php

namespace app\admin\model\shopro\goods;

use app\admin\model\shopro\Common;
use traits\model\SoftDelete;

class StockWarning extends Common
{
    use SoftDelete;

    protected $name = 'shopro_goods_stock_warning';

    protected $deleteTime = 'deletetime';

    protected $type = [
    ];

    protected $append = [
    ];


    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id', 'id');
    }


    public function skuPrice()
    {
        return $this->belongsTo(SkuPrice::class, 'goods_sku_price_id', 'id');
    }

}
