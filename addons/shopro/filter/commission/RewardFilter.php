<?php

declare(strict_types=1);

namespace addons\shopro\filter\commission;

use addons\shopro\filter\BaseFilter;
use think\db\Query;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\order\Order;

/**
 * 分销订单佣金筛选
 */
class RewardFilter extends BaseFilter
{

    /**
     * 用户相关信息 whereExists 查询
     *
     * @param Query $query
     * @param string|array $user   查询数据
     * @return Query
     */
    public function buyer($query, $user)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $user) {
            // 子查询表名
            $table_name = (new User())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.buyer_id=' . $table_name . '.id');
            // 拼接查询条件
            foreach ($user as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }




    /**
     * 用户相关信息 whereExists 查询
     *
     * @param Query $query
     * @param string|array $user   查询数据
     * @return Query
     */
    public function agent($query, $agent)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $agent) {
            // 子查询表名
            $table_name = (new User())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.agent_id=' . $table_name . '.id');
            // 拼接查询条件
            foreach ($agent as $field => $value) {
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
            $query->table($table_name)->where($current_name . '.order_id=' . $table_name . '.id');
            // 拼接查询条件
            foreach ($order as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }

}
