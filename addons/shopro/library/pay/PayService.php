<?php

namespace addons\shopro\library\pay;

use think\Log;
use addons\shopro\library\pay\provider\Base;

class PayService
{
    protected $payment;
    protected $platform;

    public function __construct($payment, $platform = null)
    {
        $this->payment = $payment;

        $this->platform = $platform ? : request()->header('platform', null);

        if (!$this->platform) {
            error_stop('缺少用户端平台参数');
        }
    }



    /**
     * 支付提供器
     *
     * @param string $type
     * @return Base
     */
    public function provider($payment = null)
    {
        $payment = $payment ?: $this->payment;
        $class = "\\addons\\shopro\\library\\pay\\provider\\" . \think\helper\Str::studly($payment);
        if (class_exists($class)) {
            return new $class($this, $this->platform);
        }

        error_stop('支付类型不支持');
    }



    public function __call($funcname, $arguments)
    {
        return $this->provider()->{$funcname}(...$arguments);
    }

}
