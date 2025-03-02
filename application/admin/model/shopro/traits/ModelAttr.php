<?php

namespace app\admin\model\shopro\traits;

trait ModelAttr
{
    /**
     * 默认类型列表，子类重写
     *
     * @return array
     */
    public function typeList()
    {
        return [];
    }


    /**
     * 默认状态列表，子类可重写
     *
     * @return array
     */
    public function statusList()
    {
        return [
            'normal' => '正常',
            'hidden' => '隐藏',
            'enable' => '启用中',
            'disabled' => '已禁用',
            'up' => '上架',
            'down' => '下架',
        ];
    }


    /**
     * （查询范围）正常
     */
    public function scopeNormal($query)
    {
        return $query->where('status', 'normal');
    }

    /**
     * （查询范围）启用
     */
    public function scopeEnable($query)
    {
        return $query->where('status', 'enable');
    }

    /**
     * （查询范围）禁用
     */
    public function scopeDisabled($query)
    {
        return $query->where('status', 'disabled');
    }


    /**
     * （查询范围）隐藏 hidden 和框架底层 hidden 冲突了
     */
    public function scopeStatusHidden($query)
    {
        return $query->where('status', 'hidden');
    }

    /**
     * （查询范围）上架
     */
    public function scopeUp($query)
    {
        return $query->where('status', 'up');
    }

    /**
     * （查询范围）下架
     */
    public function scopeDown($query)
    {
        return $query->where('status', 'down');
    }


    // /**
    //  * 创建时间格式化
    //  *
    //  * @param string $value
    //  * @param array $data
    //  * @return string
    //  */
    // public function getCreatetimeAttr($value, $data)
    // {
    //     return $this->attrTimeFormat($value, $data, 'createtime');
    // }


    // /**
    //  * 更新时间格式化
    //  *
    //  * @param string $value
    //  * @param array $data
    //  * @return string
    //  */
    // public function getUpdatetimeAttr($value, $data)
    // {
    //     return $this->attrTimeFormat($value, $data, 'updatetime');
    // }


    /**
     * 更新时间格式化
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getDeletetimeAttr($value, $data)
    {
        return $this->attrTimeFormat($value, $data, 'deletetime');
    }



    /**
     * 通用类型获取器
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['type'] ?? null);

        $list = $this->typeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    /**
     * 通用状态获取器
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');

        $list = $this->statusList();
        return isset($list[$value]) ? $list[$value] : '';
    }



    /**
     * 时间格式化
     *
     * @param mix $value
     * @param array $data
     * @param string $field
     * @return string
     */
    protected function attrTimeFormat($value, $data, $field)
    {
        $value = $value ?: ($data[$field] ?? null);

        return $value ? (is_string($value) ? $value : date('Y-m-d H:i:s', $value)) : null;
    }


    /**
     * 获取器格式化 json
     *
     * @param mix $value
     * @param array $data
     * @param string $field
     * @param bool $return_array
     * @return array|null
     */
    protected function attrFormatJson($value, $data, $field, $return_array = false)
    {
        $value = $value ?: ($data[$field] ?? null);

        $value = $value ? json_decode($value, true) : ($return_array ? [] : $value);
        return $value === false ? $data[$field] : $value;
    }



    /**
     * 获取器格式化 , 隔开的数据
     *
     * @param mix $value
     * @param array $data
     * @param string $field
     * @param bool $return_array
     * @return array|null
     */
    protected function attrFormatComma($value, $data, $field, $return_array = false)
    {
        $value = $value ?: ($data[$field] ?? null);

        $value = $value ? explode(',', $value) : ($return_array ? [] : $value);
        return $value === false ? $data[$field] : $value;
    }


    /**
     * 将时间格式化为时间戳
     *
     * @param [type] $time
     * @return void
     */
    protected function attrFormatUnix($time) 
    {
        return $time ? (!is_numeric($time) ? strtotime($time) : $time) : null;
    }
}
