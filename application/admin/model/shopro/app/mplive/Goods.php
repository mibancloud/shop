<?php

namespace app\admin\model\shopro\app\mplive;

use app\admin\model\shopro\Common;

class Goods extends Common
{

    protected $name = 'shopro_mplive_goods';

    protected $append = [
        'audit_status_text',
        'type_text',
        'price_type_text',
    ];

    /**
     * 类型列表
     *
     * @return array
     */
    public function typeList()
    {
        return [0 => '我的小程序', 1 => '其他小程序'];
    }

    public function auditStatusList()
    {
        return [0 => '未审核', 1 => '审核中', 2 => '审核通过', 3 => '审核失败'];
    }

    public function priceTypeList()
    {
        return [1 => '一口价', 2 => '价格区间', 3 => '折扣价'];
    }

    public function getPriceTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['price_type'] ?? null);

        $list = $this->priceTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getAuditStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['audit_status'] ?? null);

        $list = $this->auditStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }
}
