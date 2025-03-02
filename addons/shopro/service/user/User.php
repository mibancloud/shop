<?php

namespace addons\shopro\service\user;

use app\admin\model\shopro\user\User as UserModel;

class User
{
    /**
     * @name 追加消费金额
     * @param  int|object   $user       会员对象或会员ID
     * @param  float        $amount      变更金额
     * @return boolean
     */
    public static function consume($user, $amount)
    {
        // 判断金额
        if ($amount == 0) {
            return false;
        }

        // 判断用户
        if (is_numeric($user)) {
            $user = UserModel::getById($user);
        }
        if (!$user) {
            error_stop('未找到用户');
        }


        // 更新会员余额信息
        $user->setInc('total_consume', $amount);

        return true;
    }
}
