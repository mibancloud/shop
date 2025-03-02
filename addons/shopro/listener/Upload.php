<?php

namespace addons\shopro\listener;


class Upload
{

    public function uploadAfter($params)
    {
        $attachment = $params;
        $shopro_type = request()->param('shopro_type');

        // simple 包含支付证书，店铺装修截图等不需要再附件管理中存在的文件
        if ($shopro_type == 'simple' && isset($attachment->id) && $attachment->id) {
            // 删除附件管理记录
            $attachment->where('id', $attachment->id)->delete();
        }
    }
}
