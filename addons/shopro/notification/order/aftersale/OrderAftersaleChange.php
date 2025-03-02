<?php

namespace addons\shopro\notification\order\aftersale;

/**
 * 售后结果通知
 */
class OrderAftersaleChange extends OrderAftersaleChangeBase
{
    
    public $receiver_type = 'user';      // 接收人:user=用户


    // 发送类型
    public $event = 'order_aftersale_change';
}
