<?php

namespace app\admin\validate\shopro\user;

use think\Validate;
use think\Db;
use think\Loader;
use think\exception\ClassNotFoundException;

class User extends Validate
{
    protected $regex = [
        'password' => '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]+\S{5,12}$/',
        'notPureNumber'  => '^[a-zA-Z][a-zA-Z0-9_]{4,15}$',
        'mobile' => '/^1[3456789]\d{9}$/',
    ];

    protected $rule = [
        'username' => 'alphaDash|length:5,12|unique:user|regex:notPureNumber',
        'nickname' => 'require|length:1,10',
        'mobile' => 'regex:mobile',
        'password' => 'length:6,16|regex:password',
        'oldPassword' => 'require',
        'newPassword' => 'require|length:6,16|regex:password',
        'avatar' => 'require',
        'email' => 'email|unique:user',
        'code' => 'require',
        'gender' => 'in:0,1'
    ];

    protected $message  =   [
        'username.require'     => '用户名必须填写',
        'username.alphaDash'     => '用户名只能包含字母,数字,_和-',
        'username.length'     => '用户名长度必须在 5-12 位',
        'username.unique'     => '用户名已被占用',
        'username.regx'     => '用户名需以字母开头',

        'nickname.require'     => '昵称必须填写',
        'nickname.chsDash'     => '昵称只能包含汉字,字母,数字,_和-',
        'nickname.length'     => '昵称长度必须在 2-10 位',

        'mobile.require'     => '手机号必须填写',
        'mobile.regex'     => '手机号格式不正确',
        'mobile.unique'     => '手机号已被占用',

        'password.require'     => '请填写密码',
        'password.length'     => '密码长度必须在 6-16 位',
        'password.regx'     => '密码必须包含字母和数字',

        'oldPassword.require'     => '请填写旧密码',

        'newPassword.require'     => '请填写新密码',
        'newPassword.length'     => '密码长度必须在 6-16 位',
        'newPassword.regx'     => '密码必须包含字母和数字',

        'avatar.require'     => '头像必须上传',

        'email.email'     => '邮箱格式不正确',
        'email.unique'     => '邮箱已被占用',

        'code.require'     => '请填写验证码',

        'gender.in'  => '请选择性别'
    ];


    protected $scene = [
        'edit' => ['username', 'nickname', 'mobile', 'password', 'avatar', 'gender', 'email', 'status']
    ];

     /**
     * 验证是否唯一
     * @access public
     * @param mixed  $value 字段值
     * @param mixed  $rule  验证规则 格式：数据表,字段名,排除ID,主键名
     * @param array  $data  数据
     * @param string $field 验证字段名
     * @return bool
     */
    public function unique($value, $rule, $data = [], $field = '')
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }

        if (false !== strpos($rule[0], '\\')) {
            // 指定模型类
            $db = new $rule[0];
        } else {
            try {
                $db = Loader::model($rule[0]);
            } catch (ClassNotFoundException $e) {
                $db = Db::name($rule[0]);
            }
        }

        $key = $rule[1] ?? $field;
        $map = [];

        if (strpos($key, '^')) {
            // 支持多个字段验证
            $fields = explode('^', $key);
            foreach ($fields as $key) {
                if (isset($data[$key])) {
                    $map[$key] = ['=', $data[$key]];
                }
            }
        } elseif (isset($data[$field])) {
            $map[$key] = ['=', $data[$field]];
        } else {
            $map = [];
        }

        $pk = !empty($rule[3]) ? $rule[3] : $db->getPk();

        if (is_string($pk)) {
            if (isset($rule[2])) {
                $map[$pk] = ['neq', $rule[2]];
            } elseif (isset($data[$pk])) {
                $map[$pk] = ['neq', $data[$pk]];
            }
        }

        if ($db->where($map)->field($pk)->find()) {
            return false;
        }

        return true;
    }
}
