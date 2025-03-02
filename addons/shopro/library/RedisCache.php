<?php

namespace addons\shopro\library;

use addons\shopro\facade\Redis as RedisFacade;

class RedisCache
{

    protected $redis = null;
    
    public function __construct()
    {
    }

    public function get($key, $default = null) 
    {
        if (RedisFacade::exists($key)) {
            $value = RedisFacade::get($key);
            return !is_null($value) ? $this->unserialize($value) : null;
        }

        return $default;
    }


    public function set($key, $value, $ttl = null)
    {
        $value = $this->serialize($value);
        
        if ($ttl) {
            $result = RedisFacade::setex($key, $ttl, $value);
        } else {
            $result = RedisFacade::set($key, $value);
        }

        return $result;
    }



    /**
     * 判断一个项目在缓存中是否存在
     * @param string $key 缓存键值
     *
     * @return bool  
     */
    public function has($key)
    {
        return RedisFacade::exists($key);
    }


    /**
     * 删除指定键值的缓存项
     *
     * @param string $key 指定的唯一缓存key对应的项目将会被删除
     *
     * @return bool 成功删除时返回ture，有其它错误时时返回false
     */
    public function delete($key)
    {
        return RedisFacade::del($key);
    }


    /**
     *  单次操作删除多个缓存项目.
     *
     * @param iterable $keys 一个基于字符串键列表会被删除
     *
     * @return bool True 所有项目都成功被删除时回true,有任何错误时返回false
     */
    public function deleteMultiple($keys) {}


    /**
     * 清除所有缓存中的key
     *
     * @return bool 成功返回True.失败返回False
     */
    public function clear() {}

    /**
     * 根据指定的缓存键值列表获取得多个缓存项目
     *
     * @param iterable $keys   在单次操作中可被获取的键值项
     * @param mixed    $default 如果key不存在时，返回的默认值
     *
     * @return iterable  返回键值对（key=>value形式）列表。如果key不存在，或者已经过期时，返回默认值。
     */
    public function getMultiple($keys, $default = null) {}

    /**
     * 存储一个键值对形式的集合到缓存中。
     *
     * @param iterable               $values 一系列操作的键值对列表
     * @param null|int|\DateInterval $ttl     可选项.项目的存在时间，如果该值没有设置，且驱动支持生存时间时，将设置一个默认值，或者驱自行处理。
     *
     * @return bool 成功返回True.失败返回False.
     */
    public function setMultiple($values, $ttl = null) {}


    /**
     * Serialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function serialize($value)
    {
        return is_numeric($value) && !in_array($value, [INF, -INF]) && !is_nan($value) ? $value : serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }


    public function __call($funcname, $arguments)
    {
        return RedisFacade::instance()->{$funcname}(...$arguments);
    }
    
}