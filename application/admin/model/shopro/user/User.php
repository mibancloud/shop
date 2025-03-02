<?php

namespace app\admin\model\shopro\user;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\Coupon as CouponModel;
use app\admin\model\shopro\ThirdOauth;
use addons\shopro\library\notify\traits\Notifiable;
use fast\Random;

class User extends Common
{
    use Notifiable;

    protected $name = 'user';

    protected $type = [
        'logintime' => 'timestamp',
        'jointime' => 'timestamp',
        'prevtime' => 'timestamp',
    ];

    protected $hidden = ['password', 'salt'];

    protected $append = [
        'status_text',
        'gender_text'
    ];


    public function getGenderList()
    {
        return ['1' => __('Male'), '0' => __('Female')];
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

        $list = $this->getGenderList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function setMobileAttr($value, $data)
    {
        if ($value !== "") {
            return $value;
        }
        return null;
    }

    public function setUsernameAttr($value, $data)
    {
        if ($value !== "") {
            return $value;
        }
        return null;
    }

    public function getAvatarAttr($value, $data)
    {
        if (empty($value)) {
            $config = sheep_config('shop.user');
            $value = $config['avatar'];
        }
        return $value;
    }

    public function setEmailAttr($value, $data)
    {
        if ($value !== "") {
            return $value;
        }
        return null;
    }

    public function setPasswordAttr($value, $data)
    {
        $salt = Random::alnum();
        $this->salt = $salt;
        return \app\common\library\Auth::instance()->getEncryptPassword($value, $salt);
    }

    public function getNicknameHideAttr($value, $data)
    {
        $value = $value ?: ($data['nickname'] ?? '');

        return $value ? string_hide($value, 2) : $value;
    }


    public function getMobileAttr($value, $data)
    {
        $value = $value ?: ($data['mobile'] ?? '');

        return $value ? (operate_filter(false) ? $value : account_hide($value, 3, 3)) : $value;
    }


    /**
     * 获取验证字段数组值
     * @param string $value
     * @param array  $data
     * @return  object
     */
    public function getVerificationAttr($value, $data)
    {
        $value = array_filter((array)json_decode($value, true));
        $value = array_merge(['username' => 0, 'password' => 0, 'mobile' => 0], $value);
        return (object)$value;
    }

    public function parentUser()
    {
        return $this->hasOne(User::class, 'id', 'parent_user_id')->field(['id', 'avatar', 'nickname', 'gender']);
    }

    public function thirdOauth()
    {
        return $this->hasMany(ThirdOauth::class, 'user_id', 'id');
    }


    // -- commission code start --
    public function agent()
    {
        return $this->hasOne(\app\admin\model\shopro\commission\Agent::class, 'user_id', 'id');
    }
    // -- commission code end --
}
