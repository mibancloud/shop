<?php

namespace addons\shopro\filter\activity;

use addons\shopro\filter\BaseFilter;
use think\db\Query;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\goods\Goods;


/**
 * 拼团筛选
 */
class GrouponFilter extends BaseFilter
{
    protected $keywordFields = [];


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


    public function goods($query, $user)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $user) {
            // 子查询表名
            $table_name = (new Goods())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.goods_id=' . $table_name . '.id');
            // 拼接查询条件
            foreach ($user as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }


    /**
     * 拼团状态
     *
     * @param Query $query
     * @param string|array $status   查询数据
     * @return void
     */
    public function status($query, $status)
    {
        $status = $this->getValue($status);
        $status = $status == 'finish' ? ['finish', 'finish-fictitious'] : [$status];

        $query = $query->whereIn('status', $status);

        return $query;
    }



}
