<?php

namespace addons\shopro\filter\order;

use addons\shopro\filter\BaseFilter;
use think\db\Query;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\order\Aftersale;
use app\admin\model\shopro\order\Address;
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
     * 关联售后
     *
     * @param Query $query
     * @param string|array $aftersale
     * @return Query
     */
    public function aftersale($query, $aftersale)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $aftersale) {
            // 子查询表名
            $table_name = (new Aftersale())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.id=' . $table_name . '.order_id');
            // 拼接查询条件
            foreach ($aftersale as $field => $value) {
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
                ->where('order_type', 'order')
                ->where('status', '<>', PayModel::PAY_STATUS_UNPAID);

            // 拼接查询条件
            foreach ($pay as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }


    /**
     * 关联收货地址
     *
     * @param Query $query
     * @param string|array $address
     * @return Query
     */
    public function address($query, $address)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $address) {
            // 子查询表名
            $table_name = (new Address())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.id=' . $table_name . '.order_id');
            // 拼接查询条件
            foreach ($address as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }



    /**
     * 关联 items
     *
     * @param Query $query
     * @param string|array $items
     * @return Query
     */
    public function item($query, $items)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $items) {
            // 子查询表名
            $table_name = (new OrderItem())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.id=' . $table_name . '.order_id');
            // 拼接查询条件
            foreach ($items as $field => $value) {
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
        if (in_array($status, ['closed', 'cancel', 'unpaid', 'nosend', 'noget', 'refuse', 'nocomment', 'aftersale', 'applyRefundIng', 'refund', 'paid', 'completed'])) {
            if (in_array($status, ['nosend', 'noget'])) {
                $query = $query->pretendPaid();       // 包含货到付款 待付款的订单
            } else if (in_array($status, ['nocomment', 'aftersale', 'applyRefundIng', 'refund'])) {
                $query = $query->paid();
            }
            
            $query = $query->{$status}();
        }
        return $query;
    }


    public function promoTypes($query, $promoTypes) 
    {
        $promoTypes = $this->getValue($promoTypes);

        return $query->where("find_in_set(:promo_types, promo_types)", ['promo_types' => $promoTypes]);
    }



    /**
     * 售后列表查询条件（售后列表，订单为主表）
     *
     * @param Query $query
     * @param string|array $aftersale
     * @return Query
     */
    public function aftersaleList($query, $aftersaleList)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $aftersaleList) {
            // 子查询表名
            $aftersale_name = (new Aftersale())->getQuery()->getTable();
            
            // 子查询条件
            $query->table($aftersale_name)->where($current_name . '.id=' . $aftersale_name . '.order_id');

            // 拼接查询条件
            foreach ($aftersaleList as $field => $value) {
                if ($field == 'status') {
                    if (in_array($value, ['cancel', 'refuse', 'nooper', 'ing', 'finish'])) {
                        $query = $query->where(Aftersale::getScopeWhere($value));
                    }
                    continue;
                } else if ($field == '_') {
                    continue;
                }

                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }
}
