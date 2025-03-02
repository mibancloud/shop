<?php

namespace app\admin\model\shopro\chat;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\chat\traits\ChatCommon;

class Record extends Common
{
    use ChatCommon;

    protected $name = 'shopro_chat_record';

    protected $append = [
        'room_name'
    ];

    // 不格式化创建更新时间
    protected $dateFormat = false;

    public function scopeCustomer($query)
    {
        return $query->where('sender_identify', 'customer');
    }


    public function scopeCustomerService($query)
    {
        return $query->where('sender_identify', 'customer_service');
    }

    public function scopeNoRead($query)
    {
        return $query->whereNull('read_time');
    }


    public function setMessageAttr($value, $data)
    {
        switch ($data['message_type']) {
            case 'order':
            case 'goods':
                $value = is_array($value) ? json_encode($value) : $value;
                break;
            default :
                $value = $value;
        }

        return $value;
    }


    /**
     * 处理消息
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getMessageAttr($value, $data)
    {
        switch($data['message_type']) {
            case 'order':
            case 'goods':
                $message = json_decode($value, true);

                break;
            default :
                $message = $value;
                break;
        }


        // if ($data['message_type'] == 'image') {
        //     $message = Online::cdnurl($value);
        // } else if (in_array($data['message_type'], ['order', 'goods'])) {
        //     $messageArr = json_decode($value, true);
        //     if (isset($messageArr['image']) && $messageArr['image']) {
        //         $messageArr['image'] = Online::cdnurl($messageArr['image']);
        //     }

        //     $message = json_encode($messageArr);
        // } else if ($data['message_type'] == 'text') {
        //     // 全文匹配图片拼接 cdnurl
        //     $url = Online::cdnurl('/uploads');
        //     $message = str_replace("<img src=\"/uploads", "<img style=\"width: 100%;!important\" src=\"" . $url, $value);
        // } else {
        //     $message = $value;
        // }

        return $message;
    }
   
}
