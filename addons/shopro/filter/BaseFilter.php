<?php

namespace addons\shopro\filter;

use think\db\Query;
use addons\shopro\filter\traits\CommonSearch;

/**
 * filter 类都带 Filter 后缀， 避免太多的重复类名
 */
class BaseFilter
{
    use CommonSearch;

    /**
     * 当前请求实例
     */
    protected $request;
    /**
     * 当前 query 实例
     */
    protected $query;
    /**
     * 关键字模糊搜索的字段，各个 filter 覆盖
     */
    protected $keywordFields = ['id'];

    public function __construct()
    {
        $this->request = request();
    }


    /**
     * 构建查询
     *
     * @param Query $query
     * @return Query
     */
    public function apply(Query $query, $filters = null)
    {
        $this->query = $query;

        if ($filters) {
            if ($filters instanceof \Closure) {
                // 回调函数处理数据
                $filters = $filters($this->filters());
            }
        } else  {
            $filters = $this->filters();
            // $filters = [     filters 格式
            //     'name' => ['sheep', 'like'],
            //     'user.nickname' => ['sheep', 'like'],
            //     'user.mobile' => ['12345678901', 'like']
            // ];
        }

        // 首先检查并调用自定filter
        if (method_exists($this, '__customeFilter')) {
            $this->query = call_user_func_array([$this, '__customeFilter'], array_filter([$this->query, $filters]));
        }

        $joins = [];
        // joins = [        // joins 格式示例
        //     'user' => [ 'nickname' => ['sheep', 'like'], 'phone' => '12345678901' ]
        // ]
        foreach ($filters as $name => $data) {
            if (strpos($name, '.') !== false && ($currentName = explode('.', $name)) && count($currentName) == 2) {
                // 层级表字段的另外单独处理
                $joins[$currentName[0]][$currentName[1]] = $data;
            } else {
                // 筛选
                $this->query = $this->builderFilter($this->query, $name, $data);
            }
        }

        foreach ($joins as $name => $data) {
            if (method_exists($this, \think\helper\Str::camel($name))) {
                // 调用当前类方法，晒船
                $this->query = $this->funcFilter($this->query, \think\helper\Str::camel($name), $data);
            } else {
                // 没有直接查询组合字段的方法，分开单个查询
                foreach ($data as $na => $da) {
                    // 筛选子字段
                    $this->query = $this->builderFilter($this->query, $na, $da);
                }
            }
        }

        return $this->query;
    }


    /**
     * 排序
     *
     * @param Query $query
     * @return Query
     */
    public function filterOrder(Query $query) 
    {
        $this->query = $query;

        // 排序字段
        $sort = $this->request->param('sort');
        $order = $this->request->param("order");
        $order = $order ?: 'DESC';
        if (!$sort) {
            $fields = $query->getTableFields();
            if (in_array('weigh', $fields)) {
                $sort = 'weigh';
            } else {
                $sort = $query->getPk() ?: 'id';
            }
        }

        // 排序
        $this->query = $this->query->order($sort, $order);

        if ($sort != $query->getPk()) {
            // 当非主键排序时，默认加上 id 倒叙（防止 weigh 等值都一模一样排序错乱）
            $this->query = $this->query->order($query->getPk(), 'DESC');
        }

        return $this->query;
    }



    /**
     * 构建字段查询
     *
     * @param Query $query          // 如果为 null 就使用 $this->query
     * @param string $name          // 查询字段
     * @param string|array $data    // 查询数据和操作符
     * @return void
     */
    public function builderFilter($query, $name, $data)
    {
        if (method_exists($this, \think\helper\Str::camel($name))) {
            return $this->funcFilter($query, \think\helper\Str::camel($name), $data);
        } else {
            return $this->simpleFilter($name, $data, $query);
        }
    }


    /**
     * 调用当前类方法构建查询
     *
     * @param Query $query          // 如果为 null 就使用 $this->query
     * @param string $name          // 查询字段
     * @param string|array $data    // 查询数据和操作符
     * @return void
     */
    protected function funcFilter($query, $name, $data)
    {
        $query = is_null($query) ? $this->query : $query;

        return call_user_func_array([$this, $name], array_filter([$query, $data]));
    }


    /**
     * 直接构建字段查询
     * @param Query $query          // 如果为 null 就使用 $this->query
     * @param string $name          // 查询字段
     * @param string|array $data    // 查询数据和操作符
     * @return void 
     */
    protected function simpleFilter($name, $data, $query = null)
    {
        $query = is_null($query) ? $this->query : $query;

        // 获取当前查询方法和操作值
        extract($this->getOperFunc($data));     // $func, $search 

        // 构建查询条件
        return $query->$func($name, ...$search);
    }


    /**
     * 获取当前查询方法和操作值
     *
     * @param string|array $data   查询数据和操作值
     * @return array
     */
    public function getOperFunc($data)
    {
        // 获取当前操作符
        $operator = $this->getOperator($data);
        // 获取当前操作值
        $value = $this->getValue($data);

        // 要构建的方法名
        $func = 'where';
        // 要构建的操作值数组，包括操作符合操作值
        $search = [];
        switch ($operator) {
            case '<>':          // 不等于
            case '>':           // 大于
            case '>=':          // 大于等于
            case '<':           // 小于
            case '<=':          // 小于等于
                $func = 'where';
                $search = [$operator, $value];
                break;
            case 'like':        // like 查询
                $func = 'whereLike';
                $search = ['%' . $value . '%'];
                break;
            case 'not like':    // not like 查询
                $func = 'whereNotLike';
                $search = ['%' . $value . '%'];
                break;
            case 'null':        // 空值查询
                $func = $value ? 'whereNull' : 'whereNotNull';
                break;
            case 'in':          // in 查询
                $func = 'whereIn';
                $search = [$value];
                break;
            case 'not in':      // not in 查询
                $func = 'whereNotIn';
                $search = [$value];
                break;
            case 'range':       // 时间区间查询
            case 'not range':   // 时间反区间查询
                $currentValue = explode(' - ', $value);
                if ($currentValue[0] === '') {
                    // 如果第一个值不存在
                    $func = 'whereTime';
                    $search = [($operator == 'range' ? '<=' : '>'), ($currentValue[1] ?? '')];
                } else if (!isset($currentValue[1]) || $currentValue[1] === '') {
                    // 如果第二个值不存在
                    $func = 'whereTime';
                    $search = [($operator == 'range' ? '>=' : '<'), $currentValue[0]];
                } else {
                    // $func = $operator == 'range' ? 'whereBetweenTime' : 'whereNotBetweenTime';
                    // $search = $currentValue;
                    $func = 'whereTime';
                    $search = [($operator == 'range' ? 'between' : 'not between'), $currentValue];
                }

                break;
            case 'between':     // 区间查询
            case 'not between': // 反区间查询
                $currentValue = explode(' - ', $value);
                if ($currentValue[0] === '') {
                    // 如果第一个值不存在
                    $func = 'where';
                    $search = [($operator == 'between' ? '<=' : '>'), ($currentValue[1] ?? '')];
                } else if (!isset($currentValue[1]) || $currentValue[1] === '') {
                    // 如果第二个值不存在
                    $func = 'where';
                    $search = [($operator == 'between' ? '>=' : '<'), $currentValue[0]];
                } else {
                    $func = $operator == 'between' ? 'whereBetween' : 'whereNotBetween';
                    $search = [join(',', $currentValue)];       // whereBetween 示例: whereBetween('id','1,8')
                }
                break;
            case 'time':        // 时间比较     等于
            case 'timegt':      // 时间比较     大于传入时间
            case 'timeegt':     // 时间比较     大于等于传入时间
            case 'timelt':      // 时间比较     小于传入时间
            case 'timeelt':     // 时间比较     小于等于传入时间
                $func = 'whereTime';
                $search = [($operator == 'time' ? '=' : $this->expTrans(str_replace('time', '', $operator))), $value];
                break;
            case 'column':      // 字段比较     两字段相等
            case 'columngt':    // 字段比较     字段 a 大于字段 b
            case 'columnegt':   // 字段比较     字段 a 大于等于 字段 b
            case 'columnlt':    // 字段比较     字段 a 小于字段 b
            case 'columnelt':   // 字段比较     字段 a 小于等于字段 b
                $func = 'whereColumn';
                $search = [($operator == 'column' ? '=' : $this->expTrans(str_replace('column', '', $operator))), $value];
                break;
            // case 'find_in_set': // find in set
            //     $func = 'whereFindInSet';
            //     $search = [$value];
            //     break;
            default:           // 默认等于查询
                $func = 'where';
                $search = [$value];
                break;
        }

        return compact("func", "search");
    }



    /**
     * 获取当前搜索值中的操作符
     *
     * @param string|array $data
     * @return string
     */
    protected function getOperator($data)
    {
        $operator = '=';
        if (is_array($data)) {
            $operator = $data[1] ?? '=';
        }

        return strtolower($operator);
    }


    /**
     * 获取当前搜索值中的操作值
     *
     * @param string|array $data
     * @return string
     */
    protected function getValue($data)
    {
        $value = $data;
        if (is_array($data)) {
            $value = $data[0] ?? '';
        }

        return $value;
    }


    /**
     * 英文比较符，转 >= 比较符
     *
     * @param [type] $exp
     * @return void
     */
    protected function expTrans($exp)
    {
        switch($exp) {
            case 'gt':
                $trans = '>';
                break;
            case 'egt':
                $trans = '>=';
                break;
            case 'lt':
                $trans = '<';
                break;
            case 'elt':
                $trans = '<=';
                break;
            default :
                $trans = '=';
                break;
        }

        return $trans;
    }

    /**
     * 获取所有搜索字段
     *
     * @param string $name  如果存在 name 则只取 name 的搜索值
     * @return string|array
     */
    protected function filters($name = null)
    {
        $search = $this->request->param('search');
        $search = $search ?: '';
        $search = json_decode($search, true) ?: [];

        return $name ? ($search[$name] ?? null) : $search;
    }
}
