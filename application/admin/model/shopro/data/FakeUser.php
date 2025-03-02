<?php

namespace app\admin\model\shopro\data;

use app\admin\model\shopro\Common;

class FakeUser extends Common
{

    
    // 表名
    protected $name = 'shopro_data_fake_user';
    

    // 追加属性
    protected $append = [
        'gender_text'
    ];

    /**
     * 设置密码
     * @param mixed $value
     * @return string
     */
    public function setPasswordAttr($value)
    {
        $value = md5(md5($value) . mt_rand(1000, 9999));
        return $value;
    }


    public function getNicknameHideAttr($value, $data)
    {
        $value = $value ?: ($data['nickname'] ?? '');
        return $value ? string_hide($value, 2) : $value;
    }



    /**
     * 获取性别文字
     * @param   string $value
     * @param   array  $data
     * @return  object
     */
    public function getGenderTextAttr($value, $data)
    {
        $value = $value ?: ($data['gender'] ?? 0);

        $list = ['1' => __('Male'), '0' => __('Female')];
        return isset($list[$value]) ? $list[$value] : '';
    }

}
