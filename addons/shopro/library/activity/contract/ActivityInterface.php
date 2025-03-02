<?php

namespace addons\shopro\library\activity\contract;

interface ActivityInterface
{

    /**
     * 活动规则表单验证
     *
     * @param array $data
     * @return void
     */
    public function validate(array $data);

    /**
     * 检查要添加的活动状态
     *
     * @param array $params
     * @return void
     */
    public function check(array $params);


    /**
     * 获取活动相关的信息
     *
     * @param string $type
     * @param array $rules
     * @return array
     */
    public function rulesInfo($type, $rules);


    /**
     * 保存当前活动的专属数据
     *
     * @param \think\Model $activity
     * @param array $data
     * @return void
     */
    public function save(\think\Model $activity, array $params = []);
    
    /**
     * 展示当前活动规格的专属数据
     *
     * @param \think\Model $activity
     * @param array $data
     * @return void
     */
    public function showSkuPrice(\think\Model $skuPrice);

    /**
     * 格式化活动标签（满减，满折等）
     *
     * @param array $rules
     * @param string $type
     * @return array
     */
    public function formatTags(array $rules, $type);


    /**
     * 格式化活动标签单个（满减，满折等）
     *
     * @param array $rules
     * @param string $type
     * @return string
     */
    public function formatTag(array $discountData);


    /**
     * 格式化活动规则，完整版（满减，满折等）
     *
     * @param array $rules
     * @param string $type
     * @return array
     */
    public function formatTexts(array $rules, $type);


    /**
     * 覆盖商品活动数据
     *
     * @param \think\Model|array $goods
     * @param array $activity
     * @return array
     */
    public function recoverSkuPrices(array $goods, array $activity);


    /**
     * 购买活动处理
     *
     * @param array $buyInfo
     * @param array $activity
     * @return array
     */
    public function buyCheck($buyInfo, $activity);


    /**
     * 购买活动
     *
     * @param array $buyInfo
     * @param array $activity
     * @return array
     */
    public function buy($buyInfo, $activity);


    /**
     * 购买活动成功
     *
     * @param array|object $order
     * @param array|object $user
     * @return array
     */
    public function buyOk($order, $user);


    /**
     * 购买活动失败
     *
     * @param array|object $order
     * @param string $type
     * @return array
     */
    public function buyFail($order, $type);


    /**
     * 活动信息
     *
     * @param array $promo
     * @param array $data   附加数据
     * @return array
     */
    public function getPromoInfo(array $promo, array $data = []);
}