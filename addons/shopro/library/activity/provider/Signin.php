<?php

namespace addons\shopro\library\activity\provider;

/**
 * 签到
 */
class Signin extends Base
{

    protected $rules = [
        "everyday" => "require",
        "is_inc" => "require|boolean",
        "inc_num" => "require",
        "until_day" => "require|egt:0",
        "discounts" => "array",
        "is_replenish" => "require|boolean",
        "replenish_days" => "require|gt:0",
        "replenish_limit" => "require|egt:0",
        "replenish_num" => "require|gt:0"
    ];


    protected $message  =   [
    ];


    protected $default = [
        "everyday" => 0,            // 每日签到固定积分
        "is_inc" => 0,              // 是否递增签到
        "inc_num" => 0,             // 递增奖励
        "until_day" => 0,                     // 递增持续天数
        "discounts" => [],               // 连续签到奖励 {full:5, value:10} // 可以为空
        "is_replenish" => 0,            // 是否开启补签
        "replenish_days" => 1,            // 可补签天数，最小 1
        "replenish_limit" => 0,            // 补签时间限制，0 不限制
        "replenish_num" => 1,           // 补签所消耗积分           
    ];


    public function check($params, $activity_id = 0)
    {
        // 数据验证
        $params = parent::check($params);

        // 检测活动之间是否存在冲突
        $this->checkActivityConflict($params, [], $activity_id);

        return $params;
    }
}