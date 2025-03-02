<?php

namespace app\admin\model\shopro\chat;

use app\admin\model\Admin;
use app\admin\model\shopro\Common;
use app\admin\model\shopro\chat\CustomerService;
use app\admin\model\shopro\user\User as ShopUser;

class CustomerServiceUser extends Common
{
    protected $name = 'shopro_chat_customer_service_user';

    
    protected $append = [
        'auth_model',
        'auth_text'
    ];

    public static $authType = [
        'admin' => ['name' => '管理员', 'value' => 'admin'],
        'user' => ['name' => '用户', 'value' => 'user'],
    ];

    public function scopeAuthAdmin($query, $admin_id)
    {
        return $query->where('auth', 'admin')->where('auth_id', $admin_id);
    }


    public function scopeAuthUser($query, $user_id)
    {
        return $query->where('auth', 'user')->where('auth_id', $user_id);
    }


    public function getAuthModelAttr($value, $data)
    {
        return $this->{$data['auth']};
    }

    public function getAuthTextAttr($value, $data)
    {
        return self::$authType[$data['auth']]['name'] ?? '';
    }


    public function admin()
    {
        return $this->belongsTo(Admin::class, 'auth_id');
    }

    public function customerService()
    {
        return $this->belongsTo(CustomerService::class, 'customer_service_id');
    }

    public function user()
    {
        return $this->belongsTo(ShopUser::class, 'auth_id');
    }
}
