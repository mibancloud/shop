<?php

namespace addons\shopro\facade;

use addons\shopro\library\activity\ActivityRedis as ActivityRedisManager;

/**
 * @see RedisManager
 * 
 */
class ActivityRedis extends Base
{
    public static function getFacadeClass() 
    {
        if (!isset($GLOBALS['SPACTIVITYREDIS'])) {
            $GLOBALS['SPACTIVITYREDIS'] = new ActivityRedisManager();
        }

        return $GLOBALS['SPACTIVITYREDIS'];
    }
}
