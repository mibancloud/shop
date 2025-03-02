<?php

namespace app\admin\model\shopro\chat\traits;

trait ChatCommon
{

    /**
     * 默认房间
     *
     * @var array
     */
    public static function defaultRooms()
    {
        return [
            ['name' => '总后台', 'value' => 'admin'],
            // ['name' => '官网', 'value' => 'official'],
            // ['name' => '商城', 'value' => 'shop']
        ];
    }


    public function getRoomNameAttr($value, $data)
    {
        $value = $value ?: ($data['room_id'] ?? null);

        $list = array_column(self::defaultRooms(), null, 'value');
        return isset($list[$value]) ? $list[$value]['name'] : $value;
    }

}
