<?php

namespace addons\shopro\library\pay\provider;

use think\Log;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Contract\HttpClientInterface;
use addons\shopro\facade\HttpClient;
use app\admin\model\shopro\PayConfig;

class Base
{

    /**
     * yansongda 支付示例
     *
     * @var Yansongda\Pay\Pay
     */
    protected $pay = null;      // yansongda 支付实例

    public $config = null;     // 支付参数    


    /**
     * yansongda 支付初始化
     *
     * @param string $payment
     * @param array $config
     * @return Yansongda\Pay\Pay
     */
    public function init($payment, $config = [], $type = 'normal')
    {
        $this->config = $this->getConfig($payment, $config, $type);

        $this->pay = Pay::config($this->config);
        Pay::set(HttpClientInterface::class, HttpClient::instance());       // 使用自定义 client （也继承至 GuzzleHttp\Client）
    }


    /**
     * 获取支付的所有参数
     *
     * @param string $payment
     * @return array
     */
    protected function getConfig($payment, $config = [], $type = 'normal')
    {
        // 获取平台配置
        $platformConfig = $this->getPlatformConfig();
        extract($platformConfig);

        $params = $this->getPayConfig($payment, $paymentConfig);

        // 格式化支付参数
        $params['mode'] = (int)($params['mode'] ?? 0);
        $params = $this->formatConfig($params, ['app_id' => $app_id], $type);

        // 合并传入的参数
        $params = array_merge($params, $config);

        // 合并参数
        $config = $this->baseConfig();
        $config = array_merge($config, [$payment => ['default' => $params]]);

        return $config;
    }



    /**
     * 获取平台配置参数
     *
     * @return array
     */
    protected function getPlatformConfig()
    {
        $platformConfig = sheep_config('shop.platform.' . $this->platform);

        $paymentConfig = $platformConfig['payment'] ?? [];
        $app_id = $platformConfig['app_id'] ?? '';

        return compact('paymentConfig', 'app_id');
    }


    /**
     * 根据平台以及支付方式 获取支付配置表的配置参数
     *
     * @param string $payment
     * @return array
     */
    protected function getPayConfig($payment, $paymentConfig) 
    {
        $methods = $paymentConfig['methods'];
        $payment_config = $paymentConfig[$payment] ?? 0;

        if (!in_array($payment, $methods)) {
            error_stop('当前平台未开启该支付方式');
        }
        if ($payment_config) {
            $payConfig = PayConfig::normal()->where('type', $payment)->find($payment_config);
        }

        if (!isset($payConfig) || !$payConfig) {
            error_stop('支付配置参数不存在');
        }

        return $payConfig->params;
    }



    /**
     * 获取对应的支付方法名
     *
     * @param strign $payment
     * @return string
     */
    protected function getMethod($payment)
    {
        $method = [
            'wechat' => [
                'WechatOfficialAccount' => 'mp',        //公众号支付 Collection
                'WechatMiniProgram' => 'mini',       //小程序支付 Collection 
                'H5' => 'wap',                      //手机网站支付 Response
                'App' => 'app'                      // APP 支付 JsonResponse
            ],
            'alipay' => [
                'WechatOfficialAccount' => 'wap',       //手机网站支付 Response
                'WechatMiniProgram' => 'wap',           //小程序支付
                'H5' => 'wap',                      //手机网站支付 Response
                'App' => 'app'                      //APP 支付 JsonResponse
            ],
        ];

        return $method[$payment][$this->platform];
    }


    /**
     * yansongda 基础配置
     *
     * @return void
     */
    protected function baseConfig()
    {
        $log_path = RUNTIME_PATH . 'log/pay/';
        if (!is_dir($log_path)) {
            @mkdir($log_path, 0755, true);
        }

        return [
            'logger' => [ // optional
                'enable' => true,
                'file' => $log_path . 'pay.log',
                'level' => config('app_debug') ? 'debug' : 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                'type' => 'daily', // optional, 可选 daily.
                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
                // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
            ],
        ];
    }

}