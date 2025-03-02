<?php

namespace app\admin\model\shopro;

use app\admin\model\Admin as BaseAdmin;
use addons\shopro\library\notify\traits\Notifiable;

class Admin extends BaseAdmin
{
    use Notifiable;



    /**
     * 判断管理员是否由特定权限
     *
     * @param \think\Model $admin
     * @param array $rules
     * @return boolean
     */
    public function hasAccess(\think\Model $admin, array $rules = [])
    {
        $auth = \app\admin\library\Auth::instance();
        $RuleIds = $auth->getRuleIds($admin->id);
        $is_super = in_array('*', $RuleIds) ? 1 : 0;
        if ($is_super) {
            return true;
        }

        if ($auth->check(implode(',', $rules), $admin->id)) {
            return true;
        }

        return false;
    }


    /**
     * 是否是超级管理员
     *
     * @param \think\Model $admin
     * @return boolean
     */
    public function isSuper(\think\Model $admin)
    {
        $auth = \app\admin\library\Auth::instance();
        $RuleIds = $auth->getRuleIds($admin->id);

        $is_super = in_array('*', $RuleIds) ? 1 : 0;
        
        return $is_super;
    }
}
