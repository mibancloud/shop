<?php

declare(strict_types=1);

namespace addons\shopro\filter\traits;

use app\admin\model\Admin;
use app\admin\model\shopro\user\User;

trait CommonSearch
{

    /**
     * 关键字模糊搜索
     *
     * @param Query $query
     * @param string|array $keyword   查询数据
     * @return Query
     */
    public function keyword($query, $keyword) {
        $value = $this->getValue($keyword);
        $keywordFields = is_array($this->keywordFields) ? join('|', $this->keywordFields) : str_replace(',', '|', $this->keywordFields);
        if ($keywordFields) {
            return $query->where($keywordFields, 'like', '%' . $value . '%');
        }
        
        return $query;
    }


    /**
     * 管理员相关信息 whereExists 查询
     *
     * @param Query $query
     * @param string|array $admin   查询数据
     * @return Query
     */
    public function admin($query, $admin)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $admin) {
            // 子查询表名
            $table_name = (new Admin())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.admin_id=' . $table_name . '.id');
            // 拼接查询条件
            foreach ($admin as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }



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
            $query->table($table_name)->where($current_name . '.user_id=' . $table_name . '.id');
            // 拼接查询条件
            foreach ($admin as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }
}
