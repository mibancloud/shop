<?php

namespace addons\shopro\facade;

use addons\shopro\library\Redis as RedisManager;

/**
 * @see RedisManager
 * 
 */
class Redis extends Base
{
    public static function getFacadeClass() 
    {
        if (!isset($GLOBALS['SPREDIS'])) {
            $GLOBALS['SPREDIS'] = (new RedisManager())->getRedis();
        }

        return $GLOBALS['SPREDIS'];
    }
}
