<?php

namespace addons\shopro\library\activity\traits;

use addons\shopro\facade\Redis;
use app\admin\model\shopro\activity\Activity;
use think\helper\Str;

/**
 * 获取活动 redis 基础方法
 */
trait ActivityRedis
{

    protected $zsetKey = 'zset-activity';               // 活动集合 key
    protected $hashPrefix = 'hash-activity:';           // 活动前缀
    protected $hashGoodsPrefix = 'goods-';              // 活动中商品的前缀
    protected $hashGrouponPrefix = 'groupon-';


    // ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ 获取活动相关信息 ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
    /**
     * 获取活动完整信息
     *
     * @param integer $id
     * @param string $type
     * @return array
     */
    public function getActivity($id, $type)
    {
        $keyActivity = $this->keyActivity($id, $type);        

        return $this->getActivityByKey($keyActivity);
    }


    /**
     * 通过活动的键值，获取活动完整信息
     *
     * @param string $activityHashKey
     * @return array
     */
    public function getActivityByKey($keyActivity)
    {
        // 取出整条 hash 记录
        $activity = Redis::HGETALL($keyActivity);

        return $activity;
    }


    /**
     * 删除活动
     *
     * @param integer $id
     * @param string $type
     * @return void
     */
    public function delActivity($id, $type)
    {
        $keyActivity = $this->keyActivity($id, $type);

        $this->delActivityByKey($keyActivity);
    }



    /**
     * 通过 key 删除活动
     *
     * @param string $keyActivity
     * @return void
     */
    public function delActivityByKey($keyActivity)
    {
        // 删除 hash
        Redis::DEL($keyActivity);

        // 删除集合
        Redis::ZREM($this->zsetKey, $keyActivity);
    }


    /**
     * 获取活动的状态
     * 
     * @param string $keyActivity
     * @return string
     */
    public function getActivityStatusByKey($keyActivity)
    {
        $prehead_time = Redis::HGET($keyActivity, 'prehead_time');      // 预热时间
        $start_time = Redis::HGET($keyActivity, 'start_time');          // 开始时间
        $end_time = Redis::HGET($keyActivity, 'end_time');              // 结束时间

        // 获取活动状态
        $status = Activity::getStatusCode($prehead_time, $start_time, $end_time);

        return $status;
    }

    // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ 获取活动相关信息 ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑




    // ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ 操作活动 hash ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

    /**
     * 计算每个规格的真实库存、销量
     *
     * @param array $goods
     * @param string $keyActivity
     * @return array
     */
    private function calcGoods($goods, $keyActivity)
    {
        // 销量 key 
        $keyActivityGoods = $this->keyActivityGoods($goods['goods_id'], $goods['goods_sku_price_id'], true);

        // 缓存中的销量
        $cacheSale = Redis::HGET($keyActivity, $keyActivityGoods);

        $stock = $goods['stock'] - $cacheSale;
        $goods['stock'] = $stock > 0 ? $stock : 0;
        $goods['sales'] = $cacheSale;

        return $goods;
    }


    // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ 操作活动 hash ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑



    // ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ 格式化活动内容 ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

    /**
     * 格式化活动
     *
     * @param string array $keyActivity 活动 key 或者活动完整信息
     * @param string $type  格式化方式
     * @param array $data  额外参数
     * @return array
     */
    public function formatActivityByKey($keyActivity, $type = 'normal', $data = [])
    {
        $activity = $this->{'formatActivity' . Str::studly($type)}($keyActivity, $data);

        return $activity;
    }


    /**
     * 正常模式，只移除销量， 团信息，保留全部商品规格数据
     *
     * @param string|array $originalActivity
     * @param array $data  额外数据，商品 id
     * @return array|null
     */
    public function formatActivityNormal($originalActivity, $data = [])
    {
        if (is_string($originalActivity)) {
            // 传入的是活动的key
            $keyActivity = $originalActivity;
            $originalActivity = $this->getActivityByKey($originalActivity);
        } else {
            $keyActivity = $this->keyActivity($originalActivity['id'], $originalActivity['type']);
        }

        $activity = [];

        foreach ($originalActivity as $key => $value) {
            // 包含 -sale 全部跳过
            if (strpos($key, '-sale') !== false) {
                continue;
            } else if (strpos($key, $this->hashGrouponPrefix) !== false) {
                // 拼团的参团人数，团用户，移除
                continue;
            } else if ($key == 'rules') {
                $activity[$key] = json_decode($value, true);
            } else {
                // 普通键值
                $activity[$key] = $value;
            }
        }

        if ($activity) {
            // 处理活动状态
            $activity['status'] = Activity::getStatusCode($activity['prehead_time'], $activity['start_time'], $activity['end_time']);
            $activity['status_text'] = Activity::getStatusText($activity['status']);
        }

        return $activity ?: null;
    }


    /**
     * 简洁模式，只保留活动表基本信息
     *
     * @param string $originalActivity
     * @param array $data  额外数据，商品 id
     * @return array|null
     */
    private function formatActivityClear($originalActivity, $data = [])
    {
        if (is_string($originalActivity)) {
            // 传入的是活动的key
            $keyActivity = $originalActivity;
            $originalActivity = $this->getActivityByKey($originalActivity);
        } else {
            $keyActivity = $this->keyActivity($originalActivity['id'], $originalActivity['type']);
        }

        $activity = [];

        foreach ($originalActivity as $key => $value) {
            // 包含 -sale 全部跳过
            if (strpos($key, $this->hashGoodsPrefix) !== false) {
                continue;
            } else if (strpos($key, $this->hashGrouponPrefix) !== false) {
                // 拼团的参团人数，团用户，移除
                continue;
            } else if ($key == 'rules') {
                $activity[$key] = json_decode($value, true);
            } else {
                // 普通键值
                $activity[$key] = $value;
            }
        }

        if ($activity) {
            // 处理活动状态
            $activity['status'] = Activity::getStatusCode($activity['prehead_time'], $activity['start_time'], $activity['end_time']);
            $activity['status_text'] = Activity::getStatusText($activity['status']);
        }

        return $activity ?: null;
    }


    /**
     * 获取并按照商品展示格式化活动数据
     *
     * @param string $originalActivity hash key
     * @param array $data  额外数据，商品 id
     * @return array|null
     */
    private function formatActivityGoods($originalActivity, $data = [])
    {
        $goods_id = $data['goods_id'] ?? 0;

        if (is_string($originalActivity)) {
            // 传入的是活动的key
            $keyActivity = $originalActivity;
            $originalActivity = $this->getActivityByKey($originalActivity);
        } else {
            $keyActivity = $this->keyActivity($originalActivity['id'], $originalActivity['type']);
        }

        $activity = [];

        // 商品前缀
        $goodsPrefix = $this->hashGoodsPrefix . ($goods_id ? $goods_id . '-' : '');

        foreach ($originalActivity as $key => $value) {
            // 包含 -sale 全部跳过
            if (strpos($key, '-sale') !== false) {
                continue;
            } else if (strpos($key, $goodsPrefix) !== false) {
                // 商品规格信息，或者特定商品规格信息
                $goods = json_decode($value, true);

                // 计算销量库存数据
                $goods = $this->calcGoods($goods, $keyActivity);

                // 商品规格项
                $activity['activity_sku_prices'][] = $goods;
            } else if ($goods_id && strpos($key, $this->hashGoodsPrefix) !== false) {
                // 需要特定商品时，移除别的非当前商品的数据
                continue;
            } else if (strpos($key, $this->hashGrouponPrefix) !== false) {
                // 拼团的参团人数，团用户，移除
                continue;
            } else if ($key == 'rules') {
                $activity[$key] = json_decode($value, true);
            } else {
                // 普通键值
                $activity[$key] = $value;
            }
        }

        if ($activity) {
            // 处理活动状态
            $activity['status'] = Activity::getStatusCode($activity['prehead_time'], $activity['start_time'], $activity['end_time']);
            $activity['status_text'] = Activity::getStatusText($activity['status']);
        }

        return $activity ?: null;
    }


    /**
     * 获取并按照折扣格式展示格式化活动数据
     *
     * @param string $originalActivity hash key
     * @param array $data  额外数据
     * @return array|null
     */
    public function formatActivityPromo($originalActivity, $data = [])
    {
        if (is_string($originalActivity)) {
            // 传入的是活动的key
            $keyActivity = $originalActivity;
            $originalActivity = $this->getActivityByKey($originalActivity);
        } else {
            $keyActivity = $this->keyActivity($originalActivity['id'], $originalActivity['type']);
        }

        $activity = [];
        foreach ($originalActivity as $key => $value) {
            if ($key == 'rules') {
                $rules = json_decode($value, true);
                $activity[$key] = $rules;
            } else {
                // 普通键值
                $activity[$key] = $value;
            }
        }

        if ($activity) {
            // 处理活动状态
            $activity['status'] = Activity::getStatusCode($activity['prehead_time'], $activity['start_time'], $activity['end_time']);
            $activity['status_text'] = Activity::getStatusText($activity['status']);
        }

        return $activity ?: null;
    }



    // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ 格式化活动内容 ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑














    // ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ 获取活动的 keys 数组 ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓


    /**
     * 获取所有活动的 keys
     *
     * @return array
     */
    public function getKeysActivity()
    {
        // 获取活动集合
        $keysActivity = Redis::ZRANGE($this->zsetKey, 0, 999999999);

        return $keysActivity;
    }


    /**
     * 通过活动 id 获取活动的 key(不知道活动类型，只知道 id 的时候用)
     *
     * @param integer $id
     * @return string
     */
    public function getKeyActivityById($id)
    {
        $keysActivity = $this->getKeysActivity();

        foreach ($keysActivity as $keyActivity) {
            $suffix = ':' . $id;
            // 判断是否是要找的活动id, 截取 hashKey 后面几位，是否为当前要查找的活动 id
            if (substr($keyActivity, (strlen($keyActivity) - strlen($suffix))) == $suffix) {
                $currentKeyActivity = $keyActivity;
                break;
            }
        }

        return $currentKeyActivity ?? null;
    }


    /**
     * 通过活动 id 和 活动 type 获取 活动 key
     *
     * @param integer $activity_id
     * @param string $activity_type
     * @return string
     */
    public function getKeyActivityByIdType($activity_id, $activity_type)
    {
        $keyActivity = $this->keyActivity($activity_id, $activity_type);
        return $keyActivity;
    }


    /**
     * 获取对应活动类型的 活动 keys
     *
     * @param array $activityTypes
     * @param array|string $status        要查询的活动的状态
     * @return array
     */
    public function getKeysActivityByTypes($activityTypes, $status = 'all')
    {
        $status = is_array($status) ? $status : [$status];
        
        $activityTypes = is_array($activityTypes) ? $activityTypes : [$activityTypes];
        $activityTypes = array_values(array_filter(array_unique($activityTypes)));  // 过滤空值

        $keysActivity = $this->getKeysActivity();

        // 获取对应的活动类型的集合
        $keysActivityTypes = [];
        foreach ($keysActivity as $keyActivity) {
            // 循环要查找的活动类型数组
            foreach ($activityTypes as $type) {
                $prefix = $this->hashPrefix . $type . ':';
                if (strpos($keyActivity, $prefix) === 0) {        // 是要查找的类型
                    $keysActivityTypes[] = $keyActivity;
                    break;
                }
            }
        }

        // 判断活动状态
        if (!in_array('all', $status)) {
            foreach ($keysActivityTypes as $key => $keyActivity) {
                $activity_status = $this->getActivityStatusByKey($keyActivity);
                if (!in_array($activity_status, $status)) {
                    unset($keysActivityTypes[$key]);
                }
            }
        }

        return array_values($keysActivityTypes);
    }



    /**
     * 通过商品获取该商品参与的活动的hash key
     *
     * @param integer $goods_id
     * @param Array $activityType
     * @param array|string $status        要查询的活动的状态
     * @return array
     */
    private function getkeysActivityByGoods($goods_id, $activityType = [], $status = 'all')
    {
        // 获取对应类型的活动集合
        $keysActivity = $this->getKeysActivityByTypes($activityType, $status);

        $keysActivityGoods = [];
        foreach ($keysActivity as $keyActivity) {
            // 判断这条活动是否包含该商品
            $goods_ids = array_filter(explode(',', Redis::HGET($keyActivity, 'goods_ids')));
            if (!in_array($goods_id, $goods_ids) && !empty($goods_ids)) {
                continue;
            }

            $keysActivityGoods[] = $keyActivity;
        }

        return $keysActivityGoods;
    }

    // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ 获取活动的 keys 数组 ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑








    // ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ 获取活动相关 key ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

    /**
     * 获取活动 hash 的 key 
     *
     * @param integer $activity_id 活动 id
     * @param string $activity_type 活动类型
     * @return string
     */
    private function keyActivity($activity_id, $activity_type)
    {
        // 示例 hash-activity:groupon:25
        return $this->hashPrefix . $activity_type . ':' . $activity_id;
    }


    /**
     * 获取活动 hash 中 商品相关的 key （is_sale 对应的活动商品的销量）
     *
     * @param integer $goods_id     商品
     * @param integer $sku_price_id     规格
     * @param boolean $is_sale          对应的活动商品的销量
     * @return string
     */
    private function  keyActivityGoods($goods_id, $sku_price_id, $is_sale = false)
    {
        // 示例 商品规格：goods-25-30 or 商品规格销量：goods-25-30-sale
        return $this->hashGoodsPrefix . $goods_id . '-' . $sku_price_id . ($is_sale ? '-sale' : '');
    }



    /**
     * 获取活动中 拼团 的团数据的 key
     *
     * @param integer $groupon_id
     * @param integer $goods_id
     * @param string $type      空=团 key|num=团人数|users=团用户
     * @return string
     */
    private function keyActivityGroupon($groupon_id, $goods_id, $type = '')
    {
        return $this->hashGrouponPrefix . $groupon_id . '-' . $goods_id . ($type ? '-' . $type : '');
    }


    /**
     * 获取活动相关的所有 key
     *
     * @param array $detail 商品相关数据
     * @param array $activity 活动相关数据
     * @return array
     */
    public function keysActivity($detail, $activity)
    {
        // 获取 hash key
        $keyActivity = $this->keyActivity($activity['activity_id'], $activity['activity_type']);

        $keyGoodsSkuPrice = '';
        $keySale = '';
        if (isset($detail['goods_sku_price_id']) && $detail['goods_sku_price_id']) {
            // 获取 hash 表中商品 sku 的 key
            $keyGoodsSkuPrice = $this->keyActivityGoods($detail['goods_id'], $detail['goods_sku_price_id']);
            // 获取 hash 表中商品 sku 的 销量的 key
            $keySale = $this->keyActivityGoods($detail['goods_id'], $detail['goods_sku_price_id'], true);
        }

        // 需要拼团的字段
        $keyGroupon = '';
        $keyGrouponNum = '';
        $keyGrouponUserlist = '';
        if (isset($detail['groupon_id']) && $detail['groupon_id']) {
            // 获取 hash 表中团 key
            $keyGroupon = $this->keyActivityGroupon($detail['groupon_id'], $detail['goods_id']);
            // 获取 hash 表中团当前人数 key
            $keyGrouponNum = $this->keyActivityGroupon($detail['groupon_id'], $detail['goods_id'], 'num');
            // 获取 hash 表中团当前人员列表 key
            $keyGrouponUserlist = $this->keyActivityGroupon($detail['groupon_id'], $detail['goods_id'], 'users');
        }

        return compact('keyActivity', 'keyGoodsSkuPrice', 'keySale', 'keyGroupon', 'keyGrouponNum', 'keyGrouponUserlist');
    }


    // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ 获取活动相关 key ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
}