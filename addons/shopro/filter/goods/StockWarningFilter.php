<?php

namespace addons\shopro\filter\goods;

use addons\shopro\filter\BaseFilter;
use think\db\Query;
use app\admin\model\shopro\goods\Goods as GoodsModel;

/**
 * 库存预警筛选
 */
class StockWarningFilter extends BaseFilter
{
    protected $keywordFields = [];

    /**
     * 商品相关信息 whereExists 查询
     *
     * @param Query $query
     * @param string|array $goods   查询数据
     * @return Query
     */
    public function goods($query, $goods)
    {
        // 当前表名
        $current_name = 'g';        // 列表有别名
        return $query->whereExists(function ($query) use ($current_name, $goods) {
            // 子查询表名
            $table_name = (new GoodsModel())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.goods_id=' . $table_name . '.id');
            // 拼接查询条件
            foreach ($goods as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }



    /**
     * 库存是否售罄
     *
     * @param Query $query
     * @param string|array $stock_type   查询数据
     * @return void
     */
    public function stockType($query, $stock_type)
    {
        $stock_type = $this->getValue($stock_type);

        if ($stock_type == 'over') {
            return $query->where('stock', '<=', 0);
        } else {
            // no_enough
            return $query->where('stock', '>', 0);
        }
    }
}
