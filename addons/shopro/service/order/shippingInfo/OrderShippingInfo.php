<?php

namespace addons\shopro\service\order\shippingInfo;

use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\order\Express as OrderExpress;
use app\admin\model\shopro\order\Address as OrderAddress;

class OrderShippingInfo extends Base
{

    protected $orderItems = null;
    
    protected $dispatchTypes = [];


    /**
     * 获取整个订单的 shippingParams 参数
     *
     * @return array
     */
    public function getShippingParams()
    {
        $wechatPay = $this->getWechatPay();

        $this->setSendOrderItems();

        $uploadParams = [];
        if (in_array('express', $this->dispatchTypes)) {
            // 有 快递物流 商品
            $expressUploadParams = $this->getExpressShippingParams();
            $uploadParams = array_merge($uploadParams, $expressUploadParams);
        }

        if (!$uploadParams && array_intersect(['autosend', 'custom'], $this->dispatchTypes)) {
            // 有 自动发货，或者手动发货 商品
            $virtualParams = $this->getVirtualShippingParams();
            $uploadParams[] = $virtualParams;
        }

        if (!$uploadParams && in_array('selfetch', $this->dispatchTypes)) {
            // 有 到店自提 商品
            $selfParams = $this->getSelfetchShippingParams();
            $uploadParams[] = $selfParams;
        }


        if (!$uploadParams && in_array('store_delivery', $this->dispatchTypes)) {
            // 有 店铺配送 商品
            $storeDeliveryParams = $this->getStoreDeliveryShippingParams();
            $uploadParams[] = $storeDeliveryParams;
        }

        // 处理微信相关参数
        return $this->setWechatParams($uploadParams, $wechatPay);
    }




    /**
     * 修改物流是获取指定 包裹的 shippingParams
     *
     * @param \think\Model $express
     * @return array
     */
    public function getChangeShippingParams($express)
    {
        $wechatPay = $this->getWechatPay();

        $this->setSendOrderItems();

        $orderExpresses = collection([$express]);       // 指定包裹

        // 获取包裹的 params
        $uploadParams = $this->getExpressShippingParamsByExpresses($orderExpresses);

        // 处理微信相关参数
        return $this->setWechatParams($uploadParams, $wechatPay);
    }




    /**
     * 获取订单所有包裹的 shippingParams
     *
     * @return array
     */
    private function getExpressShippingParams()
    {
        $orderExpresses = collection(OrderExpress::where('order_id', $this->order['id'])->select());

        return $this->getExpressShippingParamsByExpresses($orderExpresses);
    }



    /**
     * 获取订单指定包裹的 shippingParams
     *
     * @param \think\Model $order
     * @param \think\Collection $orderExpresses
     * @return array
     */
    private function getExpressShippingParamsByExpresses($orderExpresses)
    {
        $uploadParams = [];
        if (!$orderExpresses->isEmpty()) {
            $orderAddress = OrderAddress::where('order_id', $this->order['id'])->find();

            $receiver_contact = $orderAddress ? mb_substr($orderAddress->mobile, 0, 3) . '****' . mb_substr($orderAddress->mobile, -4) : '130****0000';

            $shippingList = [];
            foreach ($orderExpresses as $orderExpress) {
                $currentItems = $this->getItemsByCondition('order_express_id', $orderExpress->id);

                $item_desc = [];
                foreach ($currentItems as $currentItem) {
                    $item_desc[] = $currentItem['goods_title'] . '*' . $currentItem['goods_num'];
                }

                $item_desc = join(', ', $item_desc);
                $item_desc = mb_strlen($item_desc) > 110 ? mb_substr($item_desc, 0, 110) . ' 等商品' : $item_desc;      // 处理字符串

                $shippingList[] = [
                    'tracking_no' => $orderExpress['express_no'],
                    'express_company' => $orderExpress['express_code'],
                    'item_desc' => $item_desc,
                    'contact' => [
                        'receiver_contact' => $receiver_contact
                    ]
                ];
            }

            if ($shippingList) {
                // 发货
                $uploadParams[] = [
                    'logistics_type' => $this->getLogisticsType('express'),
                    'shipping_list' => $shippingList,
                ];
            }
        }

        return $uploadParams;
    }



    /**
     * 获取订单中虚拟商品的 shippingParams
     *
     * @return array
     */
    private function getVirtualShippingParams()
    {
        // 是否存在虚拟发货商品
        $virtualItems = $this->getItemsByCondition('dispatch_type', ['autosend', 'custom'], 'in_array');

        if (!$virtualItems->isEmpty()) {
            $shippingList = [];

            $item_desc = [];
            foreach ($virtualItems as $virtualItem) {
                $item_desc[] = $virtualItem['goods_title'] . '*' . $virtualItem['goods_num'];
            }

            $item_desc = join(', ', $item_desc);
            $item_desc = mb_strlen($item_desc) > 110 ? mb_substr($item_desc, 0, 110) . ' 等商品' : $item_desc;      // 处理字符串

            $shippingList[] = [
                'item_desc' => $item_desc,
            ];

            // 发货
            $currentParams = [
                'logistics_type' => $this->getLogisticsType('autosend'),
                'shipping_list' => $shippingList,
            ];
        }

        return $currentParams ?? null;
    }



    /**
     * 获取订单中到店自提商品的 shippingParams
     *
     * @return array
     */
    public function getSelfetchShippingParams()
    {
        // 到店自提商品
        $selfetchItems = $this->getItemsByCondition('dispatch_type', ['selfetch'], 'in_array');
        if (!$selfetchItems->isEmpty()) {
            $shippingList = [];

            $item_desc = [];
            foreach ($selfetchItems as $selfetchItem) {
                $item_desc[] = $selfetchItem['goods_title'] . '*' . $selfetchItem['goods_num'];
            }

            $item_desc = join(', ', $item_desc);
            $item_desc = mb_strlen($item_desc) > 110 ? mb_substr($item_desc, 0, 110) . ' 等商品' : $item_desc;      // 处理字符串

            $shippingList[] = [
                'item_desc' => $item_desc,
            ];

            // 发货
            $currentParams = [
                'logistics_type' => $this->getLogisticsType('selfetch'),
                'shipping_list' => $shippingList,
            ];
        }

        return $currentParams ?? null;
    }



    /**
     * 获取订单中店铺配送商品的 shippingParams
     *
     * @return array
     */
    public function getStoreDeliveryShippingParams()
    {
        // 到店自提商品
        $storeDeliveryItems = $this->getItemsByCondition('dispatch_type', ['store_delivery'], 'in_array');
        if (!$storeDeliveryItems->isEmpty()) {
            $shippingList = [];

            $item_desc = [];
            foreach ($storeDeliveryItems as $storeDeliveryItem) {
                $item_desc[] = $storeDeliveryItem['goods_title'] . '*' . $storeDeliveryItem['goods_num'];
            }

            $item_desc = join(', ', $item_desc);
            $item_desc = mb_strlen($item_desc) > 110 ? mb_substr($item_desc, 0, 110) . ' 等商品' : $item_desc;      // 处理字符串

            $shippingList[] = [
                'item_desc' => $item_desc,
            ];

            // 发货
            $currentParams = [
                'logistics_type' => $this->getLogisticsType('store_delivery'),
                'shipping_list' => $shippingList,
            ];
        }

        return $currentParams ?? null;
    }



    /**
     * 设置 orderItems （这里是订单中的所有 items）
     *
     * @return void
     */
    private function setSendOrderItems()
    {
        $orderItems = OrderItem::where('order_id', $this->order['id'])->where('refund_status', OrderItem::REFUND_STATUS_NOREFUND)
            ->whereIn('dispatch_status', [OrderItem::DISPATCH_STATUS_SENDED, OrderItem::DISPATCH_STATUS_GETED])->select();

        $this->orderItems = $orderItems instanceof \think\Collection ? $orderItems : collection($orderItems);

        $this->dispatchTypes = array_values(array_unique(array_filter($this->orderItems->column('dispatch_type'))));
    }



    /**
     * 根据条件获取指定 itemd
     *
     * @param string $field
     * @param mixed $value
     * @return \think\Collection
     */
    private function getItemsByCondition($field, $value, $exp = '')
    {
        $new = [];
        foreach ($this->orderItems as $item) {
            if ($exp == 'in_array') {
                if (in_array($item[$field], $value)) {
                    $new[] = $item;
                }    
            } else {
                if ($item[$field] == $value) {
                    $new[] = $item;    
                }
            }
        }

        return collection($new);
    }
}
