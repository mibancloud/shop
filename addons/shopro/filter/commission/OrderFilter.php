<?php

declare(strict_types=1);

namespace addons\shopro\filter\commission;

use addons\shopro\filter\BaseFilter;
use think\db\Query;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\commission\Reward;

/**
 * 分销订单筛选
 */
class OrderFilter extends BaseFilter
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
     * 关联售后
     *
     * @param Query $query
     * @param string|array $aftersale
     * @return Query
     */
    public function reward($query, $rewards)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $rewards) {
            // 子查询表名
            $table_name = (new Reward())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.id=' . $table_name . '.commission_order_id');

            if (isset($rewards['agent_id'])) {
                $query->where('agent_id', (int)$this->getValue($rewards['agent_id']));
                unset($rewards['agent_id']);
            }
            // 拼接查询条件
            $newRewards = [];
            foreach ($rewards as $key => $value) {
                $newRewards[str_replace('agent_', '', $key)] = $value;
            }

            if ($newRewards) {
                $query->whereExists(function ($query) use ($table_name, $newRewards) {
                    // 子查询表名
                    $user_name = (new User())->getQuery()->getTable();
                    // 子查询条件
                    $query->table($user_name)->where($table_name . '.agent_id=' . $user_name . '.id');
                    // 拼接查询条件
                    foreach ($newRewards as $field => $value) {
                        $query = $this->builderFilter($query, $field, $value);
                    }
    
                    return $query;
                });
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


    /**
     * 用户相关信息 whereExists 查询
     *
     * @param Query $query
     * @param string|array $user   查询数据
     * @return Query
     */
    public function orderItem($query, $agent)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $agent) {
            // 子查询表名
            $table_name = (new OrderItem())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.order_item_id=' . $table_name . '.id');
            // 拼接查询条件
            foreach ($agent as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }
}
