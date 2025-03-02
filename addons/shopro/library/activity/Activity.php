<?php

namespace addons\shopro\library\activity;

use addons\shopro\facade\ActivityRedis;
use addons\shopro\library\activity\contract\ActivityInterface;
use addons\shopro\library\activity\contract\ActivityGetterInterface;

class Activity
{
    /**
     * 活动model
     */
    public $model = null;
    public $redis = null;

    protected $type = null;
    protected $rules = null;

    protected $hasRedis = null;

    protected $getters = [];

    public function __construct($model_name)
    {
        $this->hasRedis = has_redis();
        $this->model = new $model_name;
        $this->redis = ActivityRedis::instance();
    }


    /**
     * 添加活动
     *
     * @param array $params
     * @return void
     */
    public function save($params)
    {
        $this->rules = $params['rules'];
        $this->type = $params['type'];
        $params['classify'] = $this->model->getClassify($this->type);   // 设置 classify
        $params['prehead_time'] = $params['classify'] == 'promo' ? '' : ($params['prehead_time'] ?? '');    // 触发触发器，promo 不能设置 prehead_time
        // 检测活动之间的冲突
        $this->checkActivity($params);

        // 保存活动
        $this->model->allowField(true)->save($params);

        // 保存活动其他数据
        $this->saveOther($params);

        if ($this->hasRedis) {
            $this->redis->setActivity($this->model);
        }
    }



    /**
     * 更新活动
     *
     * @param \think\Model $activity
     * @param array $params
     * @return void
     */
    public function update($activity, $params)
    {
        $this->model = $activity;

        $this->rules = $params['rules'];
        $this->type = $activity->type;
        $params['type'] = $activity->type;      // 活动类型不可编辑，赋值活动本身的 type
        $params['classify'] = $this->model->getClassify($this->type);   // 设置 classify
        $params['prehead_time'] = $params['classify'] == 'promo' ? '' : ($params['prehead_time'] ?? '');    // 触发触发器，promo 不能设置 prehead_time

        if ($activity->status == 'ended') {
            error_stop('活动已结束');
        }

        // 检测活动之间的冲突
        $params = $this->checkActivity($params, $this->model->id);

        $activities = $activity->classifies()['activity'];
        $activities = array_keys($activities);
        if ($activity->status == 'ing') {
            if (in_array($activity->type, $activities)) {
                // 活动正在进行中，只能改结束时间
                $params = [
                    'title' => $params['title'],
                    'end_time' => $params['end_time'],
                    'goods_list' => $params['goods_list'],
                    'richtext_id' => $params['richtext_id'],
                    'richtext_title' => $params['richtext_title'],
                ];
            }
        }

        // 保存活动
        $this->model->allowField(true)->save($params);
        
        // 保存活动其他数据
        $this->saveOther($params);

        if ($this->hasRedis) {
            $this->redis->setActivity($this->model);
        }
    }


    /**
     * 删除活动
     *
     * @param \think\Model $activity
     * @return void
     */
    public function delete($activity)
    {
        if ($this->hasRedis) {
            $this->redis->delActivity($activity);
        }

        return $activity->delete();
    }


    /**
     * 活动规格相关数据展示
     *
     * @param string $type
     * @param array $rules
     * @return array
     */
    public function showSkuPrice($type, $skuPrice)
    {
        $skuPrice = $this->provider($type)->showSkuPrice($skuPrice);

        return $skuPrice;
    }


    /**
     * 活动规则相关信息
     *
     * @param string $type
     * @param array $rules
     * @return array
     */
    public function rulesInfo($type, $rules)
    {
        $this->rules = $rules;
        $this->type = $type;

        $activity = $this->provider()->rulesInfo($type, $rules);

        return $activity;
    }


    /**
     * 校验活动特有的数据
     *
     * @param array $params
     * @param string $type
     * @return array
     */
    public function checkActivity($params, $activity_id = 0, $type = null)
    {
        return $this->provider($type)->check($params, $activity_id);
    }


    /**
     * 保存活动特有的数据
     *
     * @param array $params
     * @param string $type
     * @return void
     */
    public function saveOther($params, $type = null)
    {
        return $this->provider($type)->save($this->model, $params);
    }


    /**
     * 格式化促销标签
     *
     * @param array $rules
     * @param string $type
     * @return array
     */
    public function formatRuleTags($rules, $type = null)
    {
        return $this->provider($type)->formatTags($rules, $type);
    }


    /**
     * 格式化促销标签
     *
     * @param array $rules
     * @param string $type
     * @return array
     */
    public function formatRuleTexts($rules, $type = null)
    {
        return $this->provider($type)->formatTexts($rules, $type);
    }


    /**
     * 用活动覆盖商品数据
     *
     * @param \think\Model|array $goods
     * @return void
     */
    public function recoverSkuPrices($goods, $activity)
    {
        $skuPrices = $this->provider($activity['type'])->recoverSkuPrices($goods, $activity);
        return $skuPrices;
    }


    /**
     * 活动购买检测(仅处理活动，不处理促销)
     *
     * @param array $buyInfo
     * @param array $activity
     * @return array
     */
    public function buyCheck($buyInfo, $activity)
    {
        if ($activity) {
            return $this->provider($activity['type'])->buyCheck($buyInfo, $activity);
        }

        return $buyInfo;
    }


    /**
     * 活动购买检测(仅处理活动，不处理促销)
     *
     * @param array $buyInfo
     * @param array $activity
     * @return array
     */
    public function buy($buyInfo, $activity)
    {
        if ($activity) {
            return $this->provider($activity['type'])->buy($buyInfo, $activity);
        }

        return $buyInfo;
    }


    /**
     * 购买成功
     *
     * @param array|object $order
     * @param array|object $user
     * @return array
     */
    public function buyOk($order, $user)
    {
        if ($order->activity_type) {
            $this->provider($order->activity_type)->buyOk($order, $user);
        }

        if ($order->promo_types) {
            $promoTypes = explode(',', $order->promo_types);
            foreach ($promoTypes as $promo_type) {
                $this->provider($promo_type)->buyOk($order, $user);
            }
        }

        return $order;
    }


    /**
     * 购买失败(释放库存,剪掉销量,移除参团数据)
     *
     * @param array|object $order
     * @param string $type      失败类型:invalid=订单取消,关闭;refund=退款
     * @return array
     */
    public function buyFail($order, $type)
    {
        if ($order->activity_type) {
            $this->provider($order->activity_type)->buyFail($order, $type);
        }

        if ($order->promo_types) {
            $promoTypes = explode(',', $order->promo_types);
            foreach ($promoTypes as $promo_type) {
                $this->provider($promo_type)->buyFail($order, $type);
            }
        }

        return $order;
    }



    /**
     * 获取促销优惠信息
     *
     * @param array $promo
     * @param array $data
     * @return array
     */
    public function getPromoInfo($promo, array $data = [])
    {
        return $this->provider($promo['type'])->getPromoInfo($promo, $data);
    }



    /**
     * 活动提供器
     *
     * @param string $type
     * @return ActivityInterface
     */
    public function provider($type = null) 
    {
        $type = $type ?: $this->type;
        $class = "\\addons\\shopro\\library\\activity\\provider\\" . \think\helper\Str::studly($type);
        if (class_exists($class)) {
            return new $class($this);
        }

        error_stop('活动类型不支持');
    }



    /**
     * 获取活动提供器
     *
     * @param string $getter
     * @return ActivityGetterInterface
     */
    public function getter($getter = null) 
    {
        $getter = $getter ? $getter : $this->defaultGetter();

        if (isset($this->getters[$getter])) {
            return $this->getters[$getter];
        }

        $class = "\\addons\\shopro\\library\\activity\\getter\\" . \think\helper\Str::studly($getter);
        if (class_exists($class)) {
            return $this->getters[$getter] = new $class($this);
        }

        error_stop('活动类型不支持');
    }


    /**
     * 获取默认获取器
     *
     * @return string
     */
    public function defaultGetter() 
    {
        return $this->hasRedis ? 'redis' : 'db';
    }


    public function __call($funcname, $arguments)
    {
        return $this->getter()->{$funcname}(...$arguments);
    }
}