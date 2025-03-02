<?php

namespace addons\shopro\library\activity;

use addons\shopro\facade\Redis;
use app\admin\model\shopro\activity\Activity as ActivityModel;
use addons\shopro\library\activity\traits\ActivityRedis as ActivityRedisTrait;

class ActivityRedis
{
    use ActivityRedisTrait;

    public function __construct() {
    }

    /**
     * 将活动设置到 redis 中
     *
     * @param mixed $activity
     * @param array $goodsList
     * @return void
     */
    public function setActivity($activity)
    {
        $activity = ActivityModel::with('activity_sku_prices')->where('id', $activity['id'])->find();

        // hash 键值
        $keyActivity = $this->keyActivity($activity->id, $activity->type);

        // 删除旧的可变数据，需要排除销量 key 
        if (Redis::EXISTS($keyActivity)) {
            // 如果 hashKey 存在,删除规格
            $activityCache = $this->getActivityByKey($keyActivity);

            foreach ($activityCache as $field => $value) {
                // 是商品规格，并且不是销量
                if (strpos($field, $this->hashGoodsPrefix) !== false && strpos($field, '-sale') === false) {
                    // 商品规格信息，删掉
                    Redis::HDEL($keyActivity, $field);
                }
            }
        }

        Redis::HMSET(
            $keyActivity,
            [
                'id' => $activity['id'],
                'title' => $activity['title'],
                'type' => $activity['type'],
                'type_text' => $activity['type_text'],
                'classify' => $activity['classify'],
                'goods_ids' => $activity['goods_ids'],
                'richtext_id' => $activity['richtext_id'],
                'richtext_title' => $activity['richtext_title'],
                'prehead_time' => strtotime($activity['prehead_time']), 
                'start_time' => strtotime($activity['start_time']),
                'end_time' => strtotime($activity['end_time']),
                'rules' => is_array($activity['rules']) ? json_encode($activity['rules']) : $activity['rules'],
            ]
        );

        // 将活动规格保存 redis
        foreach ($activity['activity_sku_prices'] as $goods) {
            unset($goods['sales']);     // 规格销量单独字段保存 goods-id-id-sale key
            $keyActivityGoods = $this->keyActivityGoods($goods['goods_id'], $goods['goods_sku_price_id']);
            // 获取当前规格的销量，修改库存的时候，需要把 stock 加上这部分销量
            $cacheSale = Redis::HGET($keyActivity, $keyActivityGoods . '-sale');
            $goods['stock'] = $goods['stock'] + $cacheSale;
            Redis::HSET($keyActivity, $keyActivityGoods, json_encode($goods));
        }

        // 将 hash 键值存入 有序集合，score 为 id
        Redis::ZADD($this->zsetKey, strtotime($activity['start_time']), $keyActivity);
    }



    /**
     * 删除活动缓存
     *
     * @param object $activity
     * @return void
     */
    public function delActivity($activity)
    {
        // hash 键值
        $keyActivity = $this->keyActivity($activity->id, $activity->type);

        // 删除 hash
        Redis::DEL($keyActivity);

        // 删除集合
        Redis::ZREM($this->zsetKey, $keyActivity);
    }



    /**
     * 根据活动类型，获取所有活动(前端：秒杀商品列表，拼团商品列表)
     *
     * @param array $activityTypes 要查询的活动类型
     * @param array|string $status        要查询的活动的状态
     * @param string $format_type       // 格式化类型，默认clear,清理多余的字段，比如拼团的 团信息  normal=格式化拼团，秒杀等|promo=格式化满减，满折，赠送
     * @return array
     */
    public function getActivityList($activityTypes = [], $status = 'all', $format_type = 'normal')
    {
        // 获取对应的活动类型的集合
        $keysActivity = $this->getKeysActivityByTypes($activityTypes, $status);

        $activityList = [];
        foreach ($keysActivity as $keyActivity) {
            // 格式化活动
            $activity = $this->formatActivityByKey($keyActivity, $format_type);
            if ($activity) {
                $activityList[] = $activity;
            }
        }

        return $activityList;
    }


    /**
     * 查询商品列表,详情时，获取这个商品对应的秒杀拼团等活动
     *
     * @param integer $goods_id
     * @param Array $activityTypes
     * @param array|string $status        要查询的活动的状态
     * @param string $format_type
     * @return array
     */
    public function getGoodsActivitys($goods_id, $activityTypes = [], $status = 'all', $format_type = 'goods')
    {
        // 获取商品第一条活动的 hash key
        $keysActivity = $this->getkeysActivityByGoods($goods_id, $activityTypes, $status);

        // 如果存在活动
        foreach ($keysActivity as $keyActivity) {
            // 格式化活动
            $activity = $this->formatActivityByKey($keyActivity, $format_type, ['goods_id' => $goods_id]);
            if ($activity) {
                $activityList[] = $activity;
            }
        }

        return $activityList ?? [];
    }

    /**
     * 获取是商品的特定的活动
     *
     * @param integer $goods_id
     * @param integer $activity_id
     * @param array|string $status        要查询的活动的状态
     * @param string $format_type
     * @return array
     */
    public function getGoodsActivityByActivity($goods_id, $activity_id, $status = 'all', $format_type = 'goods')
    {
        // 获取商品第一条活动的 hash key
        $keyActivity = $this->getKeyActivityById($activity_id);
        if (!$keyActivity) {
            return null;
        }

        $activity = $this->formatActivityByKey($keyActivity, $format_type, ['goods_id' => $goods_id]);
        if ($activity) {
            // 判断商品
            $goods_ids = array_values(array_filter(explode(',', $activity['goods_ids'])));
            if (!in_array($goods_id, $goods_ids) && !empty($goods_ids)) {
                return null;
            }
            
            // 判断状态
            $status = is_array($status) ? $status : [$status];
            if (!in_array('all', $status)) {
                if (!in_array($activity['status'], $status)) {
                    return null;
                }
            }
        }

        return $activity ?? null;
    }    
}