<?php

namespace addons\shopro\library\express\contract;

interface ExpressInterface
{


    /**
     * 快递查询
     *
     * @param array $data
     * @param mixed $orderExpress
     * @return array
     */
    public function search(array $data, $orderExpress = null);


    /**
     * 物流信息订阅
     *
     * @param array $data
     * @return array
     */
    public function subscribe(array $data);


    /**
     * 物流信息推送
     * 
     * @param array $data
     */
    public function push(array $data);


    /**
     * 电子面单
     *
     * @param array $data
     * @param array $items
     * @return array
     */
    public function eOrder(array $data, $items);

}