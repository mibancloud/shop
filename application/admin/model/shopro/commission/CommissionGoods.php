<?php

namespace app\admin\model\shopro\commission;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\goods\Goods as GoodsModel;

class CommissionGoods extends Common
{
    protected $pk = 'goods_id';

    protected $name = 'shopro_commission_goods';
    
    protected $autoWriteTimestamp = false;

    // 分销状态
    const GOODS_COMMISSION_STATUS_OFF = 0;     // 商品不参与分佣
    const GOODS_COMMISSION_STATUS_ON = 1;     // 商品参与分佣
    const GOODS_COMMISSION_RULES_DEFAULT = 0;       // 默认分销规则  只看系统分销商等级规则
    const GOODS_COMMISSION_RULES_SELF = 1;          // 独立分销规则  等级规则对应多种规格规则
    const GOODS_COMMISSION_RULES_BATCH = 2;         // 批量分销规则  只看保存的各分销商等级规则

    protected $type = [
        'commission_rules' => 'json'
    ];
    protected $append = [
        'status_text'
    ];

    public function statusList()
    {
        return [
            0 => '不参与',
            1 => '参与中'
        ];
    }

    public function getCommissionConfigAttr($value, $data)
    {
        return json_decode($value, true);
    }

    public function goods()
    {
        return $this->belongsTo(GoodsModel::class, 'goods_id', 'id');
    }
}
