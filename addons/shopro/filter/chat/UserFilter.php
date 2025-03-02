<?php

namespace addons\shopro\filter\chat;

use addons\shopro\filter\BaseFilter;
use think\db\Query;
use app\admin\model\shopro\user\User;

/**
 * 会话筛选
 */
class UserFilter extends BaseFilter
{

    /**
     * 用户相关信息 whereExists 查询
     *
     * @param Query $query
     * @param string|array $admin   查询数据
     * @return Query
     */
    public function user($query, $admin)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $admin) {
            // 子查询表名
            $table_name = (new User())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.auth_id=' . $table_name . '.id');
            // 拼接查询条件
            foreach ($admin as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }
}
