<?php

namespace app\admin\model\shopro\notification;

use app\admin\model\shopro\Common;
use think\Collection;

class Notification extends Common
{

    protected $pk = 'id';

    protected $name = 'shopro_notification';

    protected $type = [
        'read_time' => 'timestamp',
        'data'      => 'array',
    ];

    public static $notificationType = [
        'system' => '系统消息',
        'shop' => '商城消息',
        // 'site' => '网站消息'
    ];


    public function scopeNotificationType($query, $type)
    {
        if ($type) {
            $query = $query->where('notification_type', $type);
        }

        return $query;
    }

    /**
     * 将数据转换成可以显示成键值对的格式
     *
     * @param string $value
     * @param array $data
     * @return array
     */
    public function getDataAttr($value, $data)
    {
        $data = json_decode($data['data'], true);
        if (isset($data['message_type']) && $data['message_type'] == 'notification') {
            $messageData = $data['data'];
            $class = new $data['class_name']();
            $fields = $class->returnField['fields'] ?? [];
            $fields = array_column($fields, null, 'field');

            $newData = [];
            foreach ($messageData as $k => $d) {
                $da = $fields[$k] ?? [];
                if ($da) {
                    $da['value'] = $d;
                    $newData[] = $da;
                }
            }

            $data['data'] = $newData;
        }

        return $data;
    }
}
