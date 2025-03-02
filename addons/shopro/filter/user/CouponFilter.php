<?php

namespace addons\shopro\filter\user;

use addons\shopro\filter\BaseFilter;
use think\db\Query;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\order\Order;

/**
 * 用户管理
 */
class CouponFilter extends BaseFilter
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
        return $query->whereExists(function ($query) use ($current_name, $user) {
            // 子查询表名
            $table_name = (new User())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.user_id=' . $table_name . '.id');
            // 拼接查询条件
            foreach ($user as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }


    /**
     * 订单相关 whereExists 查询
     *
     * @param Query $query
     * @param string|array $user   查询数据
     * @return Query
     */
    public function order($query, $order)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $order) {
            // 子查询表名
            $table_name = (new Order())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.use_order_id=' . $table_name . '.id');
            // 拼接查询条件
            foreach ($order as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }


    /**
     * 查询状态
     *
     * @param Query $query
     * @param string|array $status
     * @return Query
     */
    public function status($query, $status)
    {
        $status = $this->getValue($status);
        if (in_array($status, ['geted', 'used', 'expired'])) {
            $query = $query->{$status}();
        }
        return $query;
    }

}
