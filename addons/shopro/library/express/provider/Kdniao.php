<?php

namespace addons\shopro\library\express\provider;

use think\Log;
use think\exception\HttpResponseException;
use addons\shopro\library\express\adapter\Kdniao as KdniaoServer;
use app\admin\model\shopro\order\Express;
use app\admin\model\shopro\order\ExpressLog;

class Kdniao extends Base
{
    public function __construct()
    {
        $this->server = new KdniaoServer();
    }


    public $status = [
        '0' => 'noinfo',
        '1' => 'collect',
        '2' => 'transport',
        '201' => 'transport',
        '202' => 'delivery',
        '211' => 'delivery',
        '3' => 'signfor',
        '301' => 'signfor',
        '302' => 'signfor',
        '311' => 'signfor',
        '4' => 'difficulty',
        '401' => 'invalid',
        '402' => 'timeout',
        '403' => 'timeout',
        '404' => 'refuse',
        '412' => 'timeout',
    ];


    /**
     * 快递查询
     *
     * @param array $data
     * @param mixed $orderExpress
     * @return array
     */
    public function search($data, $orderExpress = 0)
    {
        $requestData = $this->formatRequest($data);
        $result = $this->server->search($requestData);

        $traces = $result['Traces'] ?? [];
        $status = $result['State'];

        // 格式化结果
        $formatResult = $this->formatResult([
            'status' => $status,
            'traces' => $traces
        ]);

        if ($orderExpress) {
            $this->updateExpress($formatResult, $orderExpress);
        }

        return $formatResult;
    }



    /**
     * 物流信息订阅
     *
     * @param array $data
     * @return array
     */
    public function subscribe($data)
    {
        $requestData = $this->formatRequest($data);
        $result = $this->server->subscribe($requestData);

        return $result;
    }

    public function cancel($data)
    {
        $kdniao = sheep_config('shop.dispatch.kdniao');

        $this->server->cancel([
            'ShipperCode' => $data['express_code'],
            'OrderCode' => $data['order_code'],
            'ExpNo' => $data['express_no'],
            "CustomerName" => $kdniao['customer_name'],
            "CustomerPwd" => $kdniao['customer_pwd']
        ]);
    }


    /**
     * 物流信息推送
     *
     * @param array $data
     * @return array
     */
    public function push(array $data)
    {
        $success = true;
        $reason = '';
        try {
            $data = json_decode(html_entity_decode($data['RequestData']), true);
            $expressData = $data['Data'];

            foreach ($expressData as $key => $express) {
                $orderExpress = Express::where('express_no', $express['LogisticCode'])->where('express_code', $express['ShipperCode'])->find();

                if (!$orderExpress) {
                    // 包裹不存在,记录日志信息，然后继续下一个
                    Log::error('order-express-notfound:' . json_encode($express));
                    continue;
                }

                if (!$express['Success']) {
                    // 失败了
                    if (isset($express['Reason']) && (strpos($express['Reason'], '三天无轨迹') !== false  || strpos($express['Reason'], '七天内无轨迹变化') !== false)) {
                        // 需要重新订阅
                        $this->subscribe([
                            'express_code' => $express['ShipperCode'],
                            'express_no' => $express['LogisticCode']
                        ]);
                    }

                    Log::error('order-express-resubscribe:' . json_encode($express));
                    continue;
                }

                $traces = $express['Traces'] ?? [];
                $status = $express['State'];

                // 格式化结果
                $formatResult = $this->formatResult([
                    'status' => $status,
                    'traces' => $traces
                ]);

                $this->updateExpress($formatResult, $orderExpress);
            }
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $reason = $data ? ($data['msg'] ?? '') : $e->getMessage();
        } catch (\Exception $e) {
            $success = false;
            $reason = $e->getMessage();
        }

        return $this->server->pushResult($success, $reason);
    }



    /**
     * 电子面单
     *
     * @param array $data
     * @param array $items
     * @return array
     */
    public function eOrder($data, $items)
    {
        $kdniao = sheep_config('shop.dispatch.kdniao');
        if ($kdniao['type'] !== 'vip') {
            error_stop('仅快递鸟标准版接口支持电子面单功能！');
        }
        $consignee = $data['consignee'];
        $order = $data['order'];
        $sender = $data['sender'] ?? sheep_config('shop.dispatch.sender');
        if (empty($sender)) {
            error_stop('请配置默认发货人信息');
        }
        // 运单基础信息
        $requestData = [
            "CustomerName" => $kdniao['customer_name'],
            "CustomerPwd" => $kdniao['customer_pwd'],
            "ShipperCode" => $kdniao['express']['code'] ?? '',
            "PayType" => $kdniao['pay_type'],
            "ExpType" => $kdniao['exp_type'],
            "IsReturnPrintTemplate" => 0,   //返回打印面单模板
            "TemplateSize" => '130',    // 一联单      
            "Volume" => 0,
            "OrderCode" => $order['order_sn'] . '_' . time(),   // 商城订单号
            "Remark" => $order['remark'] ?? '小心轻放'  // 备注
        ];

        // 发货人
        $requestData['Sender'] = [
            'Name' => $sender['name'],
            'Mobile' => $sender['mobile'],
            'ProvinceName' => $sender['province_name'],
            'CityName' => $sender['city_name'],
            'ExpAreaName' => $sender['district_name'],
            'Address' => $sender['address']
        ];

        // 收货人
        $requestData['Receiver'] = [
            "Name" => $consignee['consignee'],
            "Mobile" => $consignee['mobile'],
            "ProvinceName" => $consignee['province_name'],
            "CityName" => $consignee['city_name'],
            "ExpAreaName" => $consignee['district_name'],
            "Address" => $consignee['address']
        ];

        // 包裹信息
        $totalCount = 0;
        $totalWeight = 0;
        foreach ($items as $k => $item) {
            $goodsName = $item->goods_title . ($item->goods_sku_text ? '-' . $item->goods_sku_text : '');

            $requestData['Commodity'][] = [
                "GoodsName" =>  $goodsName,
                "Goodsquantity" => $item->goods_num,
                "GoodsWeight" => $item->goods_num * $item->goods_weight
            ];
            $totalCount += $item->goods_num;
            $totalWeight += $item->goods_num * $item->goods_weight;
        }
        $requestData['Quantity'] = $totalCount; // 商品数量
        $requestData['Weight'] = $totalWeight;

        $result = $this->server->eOrder($requestData);
        if ($result['Success'] === true && $result['ResultCode'] === "100") {
            return [
                'code' => $kdniao['express']['code'],
                'name' => $kdniao['express']['name'],
                'no' => $result['Order']['LogisticCode'],
                'ext' => $result,
                'driver' => 'kdniao'
            ];
        }
        return false;
    }



    /**
     * 处理请求接口数据
     *
     * @param array $data
     * @return array
     */
    protected function formatRequest($data)
    {
        $requestData = [
            'express_code' => $data['express_code'] ?? '',
            'express_no' => $data['express_no'],
            'phone' => (isset($data['phone']) && $data['phone']) ? substr($data['phone'], 7) : ''
        ];

        return $requestData;
    }


    /**
     * 处理返回结果
     *
     * @param array $data
     * @return array
     */
    protected function formatResult($data)
    {
        $status = $this->status[$data['status']] ?? 'noinfo';

        $traces = [];
        foreach ($data['traces'] as $trace) {
            $action = $trace['Action'] ?? '';
            if ($action !== '') {
                $currentStatus = $this->status[$action] ?? 'noinfo';
            }
            $traces[] = [
                'content' => $trace['AcceptStation'],
                'change_date' => date('Y-m-d H:i:s', strtotime(substr($trace['AcceptTime'], 0, 19))),    // 快递鸟时间格式可能是 2020-08-03 16:58:272 或者 2014/06/25 01:41:06
                'status' => $currentStatus ?? 'noinfo'
            ];
        }

        return compact('status', 'traces');
    }
}
