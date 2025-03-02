<?php

namespace addons\shopro\service\order\shippingInfo;

class TradeOrderShippingInfo extends Base
{


    /**
     * 获取整个订单的 shippingParams 参数
     *
     * @return array
     */
    public function getShippingParams()
    {
        $wechatPay = $this->getWechatPay('trade_order');

        $uploadParams = [];
        
        if ($this->order->type == 'recharge') {
            // 充值订单
            $virtualParams = $this->getVirtualShippingParams();
            $uploadParams[] = $virtualParams;
        }

        // 处理微信相关参数
        return $this->setWechatParams($uploadParams, $wechatPay);
    }




    /**
     * 获取订单中虚拟商品的 shippingParams
     *
     * @return array
     */
    private function getVirtualShippingParams()
    {
        $item_desc = '用户充值订单';
        $shippingList[] = [
            'item_desc' => $item_desc,
        ];

        // 发货
        return [
            'logistics_type' => $this->getLogisticsType('autosend'),
            'shipping_list' => $shippingList,
        ];
    }
}
