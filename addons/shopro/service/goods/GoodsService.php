<?php

namespace addons\shopro\service\goods;

use app\admin\model\shopro\goods\Goods;
use app\admin\model\shopro\goods\SkuPrice;
use app\admin\model\shopro\app\ScoreSkuPrice;
use app\admin\model\shopro\Category;
use think\Db;
use addons\shopro\library\Tree;
use addons\shopro\service\SearchHistory;
use addons\shopro\facade\Activity as ActivityFacade;

class GoodsService
{
    protected $query = null;
    protected $order = [];
    protected $md5s = [];
    protected $format = null;
    protected $is_activity = false;     // 是否处理活动
    protected $show_score_shop = false;     // 查询积分商城商品

    public function __construct(\Closure $format = null, \think\db\Query $query = null)
    {
        $this->query = $query ?: new Goods();
        $this->format = $format;
    }


    /**
     * with 关联
     *
     * @param mixed $with
     * @return self
     */
    public function with($with) 
    {
        $this->query->with($with);

        return $this;
    }


    /**
     * 查询上架的商品，包含隐藏商品
     *
     * @return self
     */
    public function show()
    {
        $this->md5s[] = 'show';
        $this->query = $this->query->whereIn('status', ['up', 'hidden']);

        return $this;
    }


    /**
     * 查询上架的商品，不包含隐藏商品
     *
     * @return self
     */
    public function up()
    {
        $this->md5s[] = 'up';
        $this->query = $this->query->where('status', 'up');

        return $this;
    }


    /**
     * 是否要处理活动相关数据
     *
     * @return self
     */
    public function activity($activity = false)
    {
        $this->is_activity = $activity ? true : false;

        return $this;
    }


    /**
     * 查询积分商城商品
     *
     * @return self
     */
    public function score() 
    {
        $this->show_score_shop = true;

        // 获取所有积分商品
        $scoreGoodsIds = ScoreSkuPrice::where('status', 'up')->group('goods_id')->cache(60)->column('goods_id');
        $this->query = $this->query->whereIn('id', $scoreGoodsIds);

        return $this;
    }


    /**
     * 连表查询 库存，暂时没用
     *
     * @return self
     */
    public function stock()
    {
        $this->md5s[] = 'stock';

        $skuSql = SkuPrice::field('sum(stock) as stock, goods_id as sku_goods_id')->group('goods_id')->buildSql();
        $this->query = $this->query->join([$skuSql => 'sp'], 'id = sp.sku_goods_id', 'left');

        return $this;
    }



    /**
     * 搜索商品
     *
     * @return self
     */
    public function search($keyword) 
    {
        $keyword = is_string($keyword) ? $keyword : json_encode($keyword);      // 转字符串
        $this->md5s[] = 'search:' . $keyword;           // 待补充

        // 添加搜索记录
        $searchHistory = new SearchHistory();
        $searchHistory->save(['keyword' => $keyword]);

        $this->query = $this->query->where('title|subtitle', 'like', '%' . $keyword . '%');
        return $this;
    }


    /**
     * 根据 ids 获取商品
     *
     * @param string|array $ids ids 数组或者 , 隔开的字符串
     * @return self
     */
    public function whereIds($ids = '') 
    {
        $ids = is_array($ids) ? join(',', $ids) : $ids;
        $this->md5s[] = 'ids:' . $ids;
        if ($ids) {
            $this->query = $this->query->orderRaw('field(id, ' . $ids . ')');      // 按照 ids 里面的 id 进行排序
            $this->query = $this->query->whereIn('id', $ids);
        }

        return $this;
    }


    /**
     * 根据商品分类获取商品
     *
     * @param integer $category_id
     * @return self
     */
    public function category($category_id, $is_category_deep = true)
    {
        $this->md5s[] = 'category_id:' . $category_id;
        $category_ids = [];
        if (isset($category_id) && $category_id != 0) {
            if ($is_category_deep) {
                // 查询分类所有子分类,包括自己
                $category_ids = (new Tree(function () {
                    // 组装搜索条件，排序等
                    return (new Category)->normal();
                }))->getChildIds($category_id);
            } else {
                $category_ids = [$category_id];
            }
        }

        $this->query->where(function ($query) use ($category_ids) {
            // 所有子分类使用 find_in_set or 匹配，亲测速度并不慢
            foreach ($category_ids as $key => $category_id) {
                $query->whereOrRaw("find_in_set($category_id, category_ids)");
            }
        });

        return $this;
    }


    /**
     * 排序，方法参数同 thinkphp order 方法
     *
     * @param string $sort
     * @param string $order
     * @return self
     */
    public function order($sort = 'weigh', $order = 'desc')
    {
        $sort = $sort == 'price' ? Db::raw('convert(`price`, DECIMAL(10, 2)) ' . $order) : $sort;
        $this->query->order($sort, $order);
        return $this;
    }



    /**
     * 获取商品列表
     *
     * @param bool $is_cache    是否缓存
     * @return array|\think\model\Collection
     */
    public function select($is_cache = false) 
    {
        $this->md5s[] = 'select';

        // 默认排序
        $this->order();

        $goods = $this->query->field('*,(sales + show_sales) as total_sales')
                    ->cache($this->getCacheKey($is_cache), (200 + mt_rand(0, 100)))
                    ->select();

        // 格式化数据
        foreach ($goods as $key => $gd) {
            $gd = $this->defaultFormat($gd);
            if ($this->format instanceof \Closure) {
                $gd = ($this->format)($gd, $this);
            }

            $goods[$key] = $gd;
        }

        return $goods;
    }


    /**
     * 获取商品列表
     *
     * @param bool $is_cache    是否缓存
     * @return \think\Paginator
     */
    public function paginate($is_cache = false) 
    {
        $this->md5s[] = 'paginate';

        // 默认排序
        $this->order();

        $goods = $this->query->field('*,(sales + show_sales) as total_sales')
                    ->cache($this->getCacheKey($is_cache), (200 + mt_rand(0, 100)))
                    ->paginate(request()->param('list_rows', 10));

        // 格式化数据
        $goods->each(function($god) {
            $god = $this->defaultFormat($god);
            if ($this->format instanceof \Closure) {
                ($this->format)($god, $this);
            }
        });

        return $goods;
    }


    /**
     * 获取单个商品
     *
     * @param bool $is_cache
     * @return Goods
     */
    public function find($is_cache = false)
    {
        $this->md5s[] = 'find';
        $goods = $this->query->cache($this->getCacheKey($is_cache), (200 + mt_rand(0, 100)))->find();

        if ($goods && $this->format instanceof \Closure) {
            // 格式化数据
            $goods = $this->defaultFormat($goods);
            ($this->format)($goods, $this);
        }

        return $goods;
    }


    /**
     * 获取单个商品,找不到抛出异常
     *
     * @param bool $is_cache
     * @return Goods
     */
    public function findOrFail($is_cache = false)
    {
        $this->md5s[] = 'find';
        $goods = $this->query->cache($this->getCacheKey($is_cache), (200 + mt_rand(0, 100)))->find();
        if (!$goods) {
            error_stop('商品不存在');
        }

        if ($this->format instanceof \Closure) {
            // 格式化数据
            $goods = $this->defaultFormat($goods);
            ($this->format)($goods, $this);
        }

        return $goods;
    }



    /**
     * 把活动相关数据覆盖到商品
     *
     * @param array|object $goods
     * @return array
     */
    public function defaultFormat($goods) 
    {
        $skuPrices = $goods->sku_prices;
        $activity = $this->is_activity ? $goods->activity : null;

        if ($activity) {
            $skuPrices = ActivityFacade::recoverSkuPrices($goods, $activity);
            // unset($goods['activity']['activity_sku_prices']);        // db 获取活动这里删除报错
        }

        if ($this->show_score_shop) {
            $scoreSkuPrices = $goods->all_score_sku_prices;     // 包含下架的积分规格

            // 积分商城，覆盖积分商城规格
            foreach ($skuPrices as $key => &$skuPrice) {
                $stock = $skuPrice->stock;      // 下面要用
                $skuPrice->stock = 0;
                $skuPrice->sales = 0;
                foreach ($scoreSkuPrices as $scoreSkuPrice) {
                    if ($skuPrice->id == $scoreSkuPrice->goods_sku_price_id) {
                        $skuPrice->stock = ($scoreSkuPrice->stock > $stock) ? $stock : $scoreSkuPrice->stock;     // 积分商城库存不能超过商品库存
                        $skuPrice->sales = $scoreSkuPrice->sales;
                        $skuPrice->price = $scoreSkuPrice->price;
                        $skuPrice->score = $scoreSkuPrice->score;
                        $skuPrice->status = $scoreSkuPrice->status;        // 采用积分的上下架

                        // $skuPrice->score_price = $scoreSkuPrice->score_price;

                        // 记录对应活动的规格的记录
                        $skuPrice->item_goods_sku_price = $scoreSkuPrice;
                        break;
                    }
                }
            }
        }

        // 移除下架的规格
        foreach ($skuPrices as $key => $skuPrice) {
            if ($skuPrice['status'] != 'up') {
                unset($skuPrices[$key]);
            }
        }
        $skuPrices = $skuPrices instanceof \think\Collection ? $skuPrices->values() : array_values($skuPrices);

        if ($activity) {
            // 处理活动相关的价格，销量等

            // 这里由 getPriceAttr 计算,这里后续删除
            // $prices = $skuPrices instanceof \think\Collection ? $skuPrices->column('price') : array_column($skuPrices, 'price');
            // $goods['price'] = $prices ? min($prices) : $goods['price'];      // min 里面不能是空数组

            // if ($activity['type'] == 'groupon') {
            //     $grouponPrices = $skuPrices instanceof \think\Collection ? $skuPrices->column('groupon_price') : array_column($skuPrices, 'groupon_price');
            //     $goods['groupon_price'] = $grouponPrices ? min($grouponPrices) : $goods['price'];
            // }

            // if ($activity['type'] == 'groupon_ladder') {
            //     // @sn 阶梯拼团，商品详情如何显示拼团价格，阶梯拼团
            // }

            // if ($activity['rules'] && isset($activity['rules']['sales_show_type']) && $activity['rules']['sales_show_type'] == 'real') {
            //     // 活动设置显示真实销量
            //     $goods['sales'] = array_sum($skuPrices instanceof \think\Collection ? $skuPrices->column('sales') : array_column($skuPrices, 'sales'));
            // } else {
            //     // 活动显示总销量
            //     $goods['sales'] += $goods['show_sales'];
            // }
        } elseif ($this->show_score_shop) {
            // 积分商城这里显示的是真实销量， 目前都由 getSalesAttr 计算
            // $goods['sales'] = array_sum($skuPrices instanceof \think\Collection ? $skuPrices->column('sales') : array_column($skuPrices, 'sales'));
        } else {
            // 没有活动，商品销量，加上虚增销量
            // $goods['sales'] += $goods['show_sales'];
        }

        if ($this->show_score_shop) {
            // 积分商城商品
            $goods['show_score_shop'] = $this->show_score_shop;
        }

        // 不给 sku_prices 赋值，会触发 getSkuPricesAttr 计算属性，覆盖计算好的 sku_prices| 但是这样 sku_prices 会存在下标不连续的情况
        $goods['new_sku_prices'] = $skuPrices;       // 商品详情接口用， 过滤掉了 下架的规格
        // $goods['sales'] = $goods->salesStockFormat($goods['sales'], $goods['sales_show_type']);        // 格式化销量，前端格式化，这里可删除
        $stocks = $skuPrices instanceof \think\Collection ? $skuPrices->column('stock') : array_column($skuPrices, 'stock');        // 获取规格中的库存
        $stock = array_sum($stocks);
        $goods['stock'] = $stock;
        // $goods['stock'] = $goods->salesStockFormat($stock, $goods['stock_show_type']);                 // 格式化库存，前端格式化，这里可删除
        $goods['activity_type'] = $activity['type'] ?? null;
        return $goods;
    }



    /**
     * 获取缓存 key
     *
     * @param bool $is_cache    是否缓存
     * @return string|bool
     */
    protected function getCacheKey($is_cache = false)
    {
        if ($is_cache) {
            sort($this->md5s);
            $key = 'goods-service-' . md5(json_encode($this->md5s));
        }

        return $key ?? false;
    }



    /**
     * 默认调用 query 中的方法，比如 withTrashed()
     *
     * @param [type] $funcname
     * @param [type] $arguments
     * @return void
     */
    public function __call($funcname, $arguments)
    {
        $this->query->{$funcname}(...$arguments);

        return $this;
    }
}
