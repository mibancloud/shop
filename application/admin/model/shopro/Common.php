<?php

declare(strict_types=1);

namespace app\admin\model\shopro;

use think\Model;
use addons\shopro\filter\BaseFilter;
use think\db\Query;
use app\admin\model\shopro\traits\ModelAttr;

class Common extends Model
{

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    protected $dateFormat = 'Y-m-d H:i:s';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    use ModelAttr;

    /**
     * 当前 model 对应的 filter 实例
     *
     * @return BaseFilter
     */
    public function filterInstance()
    {
        $filter_class = static::class;

        $class = str_replace('app\admin\model\shopro', 'addons\shopro\filter',  $filter_class) . 'Filter';

        if (!class_exists($class)) {
            return new BaseFilter();
        }
        return new $class();
    }


    /**
     * 查询范围 filter 搜索入口
     *
     * @param Query $query
     * @return void
     */
    public function scopeSheepFilter($query, $sort = true, $filters = null)
    {
        $instance = $this->filterInstance();
        $query = $instance->apply($query, $filters);
        if ($sort) {
            $query = $instance->filterOrder($query);
        }

        return $query;
    }


    /**
     * 获取模型中文名
     *
     * @return string|null
     */
    // public function getModelName()
    // {
    //     if (isset($this->modelName)) {
    //         $model_name = $this->modelName;
    //     } else {
    //         $tableComment = $this->tableComment();
    //         $table_name = $this->getQuery()->getTable();
    //         $model_name = $tableComment[$table_name] ?? null;
    //     }

    //     return $model_name;
    // }
}
