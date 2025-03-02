<?php

namespace addons\shopro\filter\goods;

use addons\shopro\filter\BaseFilter;
use think\db\Query;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\goods\Goods;
use app\admin\model\shopro\data\FakeUser;

/**
 * 评价筛选
 */
class CommentFilter extends BaseFilter
{

    /**
     * 用户相关信息 whereExists 查询
     *
     * @param Query $query
     * @param string|array $user   查询数据
     * @return Query
     */
    public function user($query, $user)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->where(function ($query) use ($current_name, $user) {
            $query->whereExists(function ($query) use ($current_name, $user) {
                // 子查询表名
                $table_name = (new User())->getQuery()->getTable();
                // 子查询条件
                $query->table($table_name)->where($current_name . '.user_id=' . $table_name . '.id')->where($current_name . '.user_type', 'user');
                // 拼接查询条件
                foreach ($user as $field => $value) {
                    $query = $this->builderFilter($query, $field, $value);
                }

                return $query;
            })->whereOr(function ($query) use ($current_name, $user) {
                $query->whereExists(function ($query) use ($current_name, $user) {
                    // 子查询表名
                    $table_name = (new FakeUser())->getQuery()->getTable();
                    // 子查询条件
                    $query->table($table_name)->where($current_name . '.user_id=' . $table_name . '.id')->where($current_name . '.user_type', 'fake_user');
                    // 拼接查询条件
                    foreach ($user as $field => $value) {
                        $query = $this->builderFilter($query, $field, $value);
                    }

                    return $query;
                });
            });
        });
    }



    /**
     * 商品相关 whereExists 查询
     *
     * @param Query $query
     * @param string|array $user   查询数据
     * @return Query
     */
    public function goods($query, $goods)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $goods) {
            // 子查询表名
            $table_name = (new Goods())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.goods_id=' . $table_name . '.id');
            // 拼接查询条件
            foreach ($goods as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }
}
