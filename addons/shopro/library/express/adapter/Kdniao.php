<?php

namespace addons\shopro\library\express\adapter;

use fast\Http;

class Kdniao
{
    // 查询接口
    const REQURL = "https://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx";
    // 订阅接口
    const SUBURL = "https://api.kdniao.com/api/dist";
    // 电子面单下单接口
    const EORDER = "https://api.kdniao.com/api/EOrderService";
    
    

    /**
     * 发货人信息
     */
    protected $sender = [];

    /**
     * 快递鸟配置参数
     */
    protected $config = [];

    public function __construct()
    {
        $this->config = sheep_config('shop.dispatch.kdniao');
    }


    /**
     * 物流查询
     *
     * @param array $data
     * @return array
     */
    public function search($data)
    {
        $requestParams = $this->getRequestParams($data);
        $requestData = $this->getRequestData($requestParams);
        $requestData['RequestType'] = $this->config['type'] == 'free' ? '1002' : '8001';
        
        $result = Http::post(self::REQURL, $requestData);

        $result = $this->getResponse($result, '没有物流信息');
        return $result;
    }



    /**
     * 物流订阅
     *
     * @param array $data
     * @return array
     */
    public function subscribe($data)
    {
        $requestParams = $this->getRequestParams($data);
        $requestData = $this->getRequestData($requestParams);
        $requestData['RequestType'] = $this->config['type'] == 'free' ? '1008' : '8008';

        $result = Http::post(self::SUBURL, $requestData);

        $result = $this->getResponse($result, '订阅失败');
        return $result;
    }


    public function cancel($data)
    {
        $requestData = $data;

        $requestData = $this->getRequestData($data);
        $requestData['RequestType'] = '1147';

        $result = Http::post(self::EORDER, $requestData);

        $result = $this->getResponse($result, '电子面单取消失败');
        return $result;
    }

    /**
     * 电子面单
     *
     * @param array $data
     * @return array
     */
    public function eOrder($data)
    {
        $requestData = $this->getRequestData($data);
        $requestData['RequestType'] = '1007';

        $result = Http::post(self::EORDER, $requestData);

        $result = $this->getResponse($result, '电子面单下单失败');
        return $result;
    }



    /**
     * 快递鸟物流推送结果处理
     *
     * @param boolean $success
     * @param string $reason
     * @return string
     */
    public function pushResult($success, $reason) 
    {
        $result = [
            "EBusinessID" => $this->config['ebusiness_id'],
            "UpdateTime" => date('Y-m-d H:i:s'),
            "Success" => $success,
            'Reason' => $reason
        ];

        return $result;
    }



    /**
     * 组装请求数据
     * 
     * @param array $requestParams
     * @return array
     */
    private function getRequestData($requestParams)
    {
        $requestParams = is_array($requestParams) ? json_encode($requestParams, JSON_UNESCAPED_UNICODE) : $requestParams;

        $requestData = [
            'EBusinessID' => $this->config['ebusiness_id'],
            'RequestData' => urlencode($requestParams),
            'DataType' => '2',
        ];
        $requestData['DataSign'] = $this->encrypt($requestParams, $this->config['app_key']);

        return $requestData;
    }


    /**
     * 组装请求参数
     * 
     * @param array $data
     * @return array
     */
    private function getRequestParams($data = [])
    {
        $params = [
            'ShipperCode' => $data['express_code'],
            'LogisticCode' => $data['express_no'],
        ];

        if ($data['express_code'] == 'JD') {
            // 京东青龙配送单号
            $params['CustomerName'] = $this->config['jd_code'];
        } else if ($data['express_code'] == 'SF') {
            // 收件人手机号后四位
            $params['CustomerName'] = $data['phone'];
        }

        return $params;
    }



    /**
     * 处理结果
     *
     * @param object $response
     * @param string $msg
     * @return array
     */
    private function getResponse($result, $msg = '')
    {
        $result = json_decode($result, true);
        if (!$result['Success']) {
            error_stop($result['Reason'] ?: $msg);
        }

        return $result;
    }


    // 加签
    private function encrypt($data, $app_key)
    {
        return urlencode(base64_encode(md5($data . $app_key)));
    }
}
