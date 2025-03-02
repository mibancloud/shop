<?php

namespace addons\shopro\facade;

use addons\shopro\library\activity\Activity as ActivityManager;
use app\admin\model\shopro\activity\Activity as ActivityModel;

/**
 * @see RedisManager
 * 
 */
class Activity extends Base
{
    public static function getFacadeClass() 
    {
        if (!isset($GLOBALS['SPACTIVITY'])) {
            $GLOBALS['SPACTIVITY'] = new ActivityManager(ActivityModel::class);
        }

        return $GLOBALS['SPACTIVITY'];
    }
}
