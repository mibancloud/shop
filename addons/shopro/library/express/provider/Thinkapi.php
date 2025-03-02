<?php

namespace addons\shopro\library\express\provider;

use app\admin\model\shopro\Config;
use app\admin\model\shopro\order\Address as OrderAddress;
use fast\Http;

class Thinkapi extends Base
{

    protected $uri = 'https://api.topthink.com';

    protected $appCode = '';

    public function __construct()
    {
        $this->appCode = Config::getConfigField('shop.dispatch.thinkapi.app_code');
    }


    public $status = [
        '1' => 'noinfo',
        '2' => 'transport',
        '3' => 'delivery',
        '4' => 'signfor',
        '5' => 'refuse',
        '6' => 'difficulty',
        '7' => 'invalid',
        '8' => 'timeout',
        '9' => 'fail',
        '10' => 'back'
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
        $mobile = (isset($data['mobile']) && $data['mobile']) ? $data['mobile'] : '';
        if (!$mobile && stripos($data['express_no'], 'SF') === 0 && isset($data['order_id'])) {
            // 获取手机号
            $orderAddress = OrderAddress::where('order_id', $data['order_id'])->find();
            $mobile = $orderAddress ? $orderAddress->mobile : $mobile;
        }

        $requestData = [
            'appCode' => $this->appCode,
            'com' => 'auto',
            'nu' => $data['express_no'],
            'phone' => substr($mobile, 7)
        ];

        $result = Http::get($this->uri . '/express/query', $requestData);
        $result = is_string($result) ? json_decode($result, true) : $result;

        if (isset($result['code']) && $result['code'] != 0) {
            $msg = $result['data']['msg'] ?? ($result['message'] ?? '');
            error_stop($msg);
        }

        $data = $result['data'] ?? [];
        $traces = $data['data'] ?? [];

        $status = $data['status'];

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
            $traces[] = [
                'content' => $trace['context'],
                'change_date' => $trace['time'],
                'status' => $trace['status'] ?? $status
            ];
        }
        $traces = array_reverse($traces);       // 调转顺序，第一条为最开始运输信息，最后一条为最新消息
        return compact('status', 'traces');
    }


    
}
