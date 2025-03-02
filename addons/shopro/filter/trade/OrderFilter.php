<?php

namespace addons\shopro\filter\trade;

use addons\shopro\filter\BaseFilter;
use think\db\Query;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\Pay as PayModel;

/**
 * 订单筛选
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
     * 关联支付
     *
     * @param Query $query
     * @param string|array $pay
     * @return Query
     */
    public function pay($query, $pay)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $pay) {
            // 子查询表名
            $table_name = (new PayModel())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.id=' . $table_name . '.order_id')
                ->where('order_type', 'trade_order')
                ->where('status', '<>', PayModel::PAY_STATUS_UNPAID);

            // 拼接查询条件
            foreach ($pay as $field => $value) {
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
        if (in_array($status, ['closed', 'cancel', 'unpaid', 'paid', 'completed'])) {
            $query = $query->{$status}();
        }
        return $query;
    }
}
