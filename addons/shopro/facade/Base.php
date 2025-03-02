<?php

namespace addons\shopro\facade;

/**
 * @see RedisManager
 * 
 */
class Base
{

    public static function getFacadeClass()
    {
        error_stop('facade 初始化失败');
    }

    public static function instance()
    {
        return static::getFacadeClass();
    }

    public static function __callStatic($funcname, $arguments)
    {
        return static::instance()->{$funcname}(...$arguments);
    }

}
