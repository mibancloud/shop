<?php

namespace addons\shopro\controller\traits;

/**
 * 控制器工具方法
 */
trait Util
{

    /**
     * 表单验证
     */
    protected function svalidate(array $params, string $validator = "")
    {
        if (false !== strpos($validator, '.')) {
            // 是否支持场景验证
            [$validator, $scene] = explode('.', $validator);
        }

        $current_class = static::class;
        $validate_class = false !== strpos($validator, '\\') ? $validator : str_replace('controller', 'validate', $current_class);

        if (!class_exists($validate_class)) {
            return;
        }

        $validate = validate($validate_class);

        // 添加场景验证
        if (!empty($scene)) {
            if (!$validate->check($params, [], $scene)) {
                $this->error($validate->getError());
            }
        } else {
            // 添加自定义验证场景，字段为当前提交的所有字段
            $validate->scene('custom', array_keys($params));
            if (!$validate->check($params, [], 'custom')) {
                $this->error($validate->getError());
            }
        }

        return true;
    }



    /**
     * 过滤前端发来的短时间内的重复的请求
     *
     * @return void
     */
    public function repeatFilter($key = null, $expire = 5)
    {
        if (!$key) {
            $url = request()->baseUrl();
            $ip = request()->ip();

            $key = 'shopro:' . $url . ':' . $ip;
        }

        if (redis_cache('?' . $key)) {
            error_stop('请稍后再试');
        }

        // 缓存 5 秒
        redis_cache($key, time(), $expire);
    }


    /**
     * 监听数据库 sql
     *
     * @return void
     */
    public function dbListen()
    {
        \think\Db::listen(function ($sql, $time) {
            echo $sql . ' [' . $time . 's]' . "<br>";
        });
    }
}
