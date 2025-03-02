<?php

namespace addons\shopro\filter\goods;

use addons\shopro\filter\BaseFilter;
use think\db\Query;
use app\admin\model\shopro\goods\Goods as GoodsModel;

/**
 * 库存补货记录筛选
 */
class StockLogFilter extends BaseFilter
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
        return $query->whereExists(function ($query) use ($goods) {
            // 子查询表名
            $table_name = (new GoodsModel())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where('g.goods_id=' . $table_name . '.id');
            // 拼接查询条件
            foreach ($goods as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }
}
