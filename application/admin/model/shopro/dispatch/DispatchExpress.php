<?php

namespace app\admin\model\shopro\dispatch;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\data\Area;

class DispatchExpress extends Common
{
    protected $name = 'shopro_dispatch_express';

    protected $append = [
        'district_text'
    ];


    public function getDistrictTextAttr($value, $data)
    {
        $province_ids = $data['province_ids'] ? explode(',', $data['province_ids']) : [];
        $city_ids = $data['city_ids'] ? explode(',', $data['city_ids']) : [];
        $district_ids = $data['district_ids'] ? explode(',', $data['district_ids']) : [];
        $ids = array_merge($province_ids, $city_ids, $district_ids);
        $districtText = Area::where('id', 'in', $ids)->field('name')->select();
        $districtText = collection($districtText)->column('name');
        return implode(',', $districtText);
    }

}
