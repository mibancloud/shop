<?php

namespace addons\shopro\library\express;

use think\exception\HttpResponseException;
use app\admin\model\shopro\order\Express as ExpressModel;

class Express
{

    /**
     * 快递驱动
     */
    protected $driver = null;

    public function __construct()
    {
        $this->driver = sheep_config('shop.dispatch.driver');
    }



    /**
     * 提供器
     *
     * @param string $type
     * @return Base
     */
    public function provider($driver = null)
    {
        $driver = $driver ?: $this->getDefaultDriver();
        $class = "\\addons\\shopro\\library\\express\\provider\\" . \think\helper\Str::studly($driver);
        if (class_exists($class)) {
            return new $class($this);
        }

        error_stop('物流平台类型不支持');
    }



    /**
     * 更新订单的所有包裹
     *
     * @param mixed $orderExpress
     * @return void
     */
    public function updateOrderExpress($orderExpress = 0) 
    {
        try {
            if ($this->driver == 'thinkapi') {
                // thinkapi 才需要查询
                if (is_numeric($orderExpress)) {
                    $orderExpresses = ExpressModel::where('order_id', $orderExpress)->select();
                }
                foreach ($orderExpresses as $key => $orderExpress) {
                    $this->updateExpress($orderExpress);
                }
            }
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'updateOrderExpress.HttpResponseException', $message);
        } catch(\Exception $e) {
            format_log_error($e, 'updateOrderExpress.HttpResponseException', '获取物流信息错误');
        }
    }



    /**
     * 更新单个包裹
     *
     * @param \think\Model $orderExpress
     * @return void
     */
    public function updateExpress($orderExpress) 
    {
        if ($this->driver == 'thinkapi' && $orderExpress->status != 'signfor') {
            // thinkapi 并且是未签收的包裹才更新
            $key = 'express:' . $orderExpress->id . ':code:' . $orderExpress->express_no;       // 包裹 id 拼上 运单号
            if (cache('?'.$key)) {
                return true;
            }

            // 查询物流信息，并且更新 express_log
            $this->provider()->search([
                'order_id' => $orderExpress['order_id'],
                'express_code' => $orderExpress['express_code'],
                'express_no' => $orderExpress['express_no']
            ], $orderExpress);

            // 缓存 300 秒
            cache($key, time(), 300);
        }

        return true;
    }


    /**
     * 默认快递物流驱动
     *
     * @return void
     */
    public function getDefaultDriver() 
    {
        return $this->driver;
    }


    public function __call($funcname, $arguments)
    {
        return $this->provider()->{$funcname}(...$arguments);
    }

}
