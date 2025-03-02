<?php

namespace addons\shopro\library\express\provider;

use addons\shopro\library\express\contract\ExpressInterface;
use app\admin\model\shopro\order\Express;
use app\admin\model\shopro\order\ExpressLog;

class Base implements ExpressInterface
{

    public function __construct()
    {
    }


    /**
     * 快递查询
     *
     * @param array $data
     * @param mixed $order_express_id
     * @return array
     */
    public function search(array $data, $orderExpress = 0) 
    {
        return null;
    }


    /**
     * 物流信息订阅
     *
     * @param array $data
     * @return void
     */
    public function subscribe(array $data)
    {
        error_stop('当前快递驱动不支持物流信息订阅');
    }


    /**
     * 物流信息推送
     *
     * @param array $data
     * @return array
     */
    public function push(array $data)
    {
        error_stop('当前快递驱动不支持接受推送');
    }


    /**
     * 电子面单
     *
     * @param array $data
     * @param array $items
     * @return array
     */
    public function eOrder(array $data, $items)
    {
        error_stop('当前快递驱动不支持电子面单');
    }



    /**
     * 更新包裹信息
     *
     * @param array $data
     * @param mixed $orderExpress
     * @return array
     */
    protected function updateExpress(array $data, $orderExpress) 
    {
        // 更新包裹状态
        if (is_numeric($orderExpress)) {
            $orderExpress = Express::find($orderExpress);
        }
        if ($orderExpress) {
            $orderExpress->status = $data['status'];
            $orderExpress->save();

            $this->syncTraces($data['traces'], $orderExpress);
        }
    }


    /**
     * 更新物流信息
     *
     * @param array $traces
     * @param mixed $orderExpress
     * @return void
     */
    protected function syncTraces($traces, $orderExpress)
    {
        // 查询现有轨迹记录
        $orderExpressLog = ExpressLog::where('order_express_id', $orderExpress->id)->select();

        $log_count = count($orderExpressLog);
        if ($log_count > 0) {
            // 移除已经存在的记录
            array_splice($traces, 0, $log_count);
        }

        // 增加包裹记录
        foreach ($traces as $k => $trace) {
            $orderExpressLog = new ExpressLog();

            $orderExpressLog->user_id = $orderExpress['user_id'];
            $orderExpressLog->order_id = $orderExpress['order_id'];
            $orderExpressLog->order_express_id = $orderExpress['id'];
            $orderExpressLog->content = $trace['content'];
            $orderExpressLog->change_date = $trace['change_date'];
            $orderExpressLog->status = $trace['status'];
            $orderExpressLog->save();
        }
    }
}
