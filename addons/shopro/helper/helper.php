<?php

if (!function_exists('matchLatLng')) {
    function matchLatLng($latlng)
    {
        $match = "/^\d{1,3}\.\d{1,30}$/";
        return preg_match($match, $latlng) ? $latlng : 0;
    }
}


if (!function_exists('getDistanceBuilder')) {
    function getDistanceBuilder($lat, $lng)
    {
        return "ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN((" . matchLatLng($lat) . " * PI() / 180 - latitude * PI() / 180) / 2), 2) + COS(" . matchLatLng($lat) . " * PI() / 180) * COS(latitude * PI() / 180) * POW(SIN((" . matchLatLng($lng) . " * PI() / 180 - longitude * PI() / 180) / 2), 2))) * 1000) AS distance";
    }
}



if (!function_exists('error_stop')) {
    function error_stop($msg = '', $code = 0, $data = null, $status_code = 200, $header = [])
    {
        $result = [
            'code' => $code ?: 0,
            'msg' => $msg,
            'data' => $data
        ];

        $response = \think\Response::create($result, 'json', $status_code)->header($header);
        throw new \think\exception\HttpResponseException($response);
    }
}




/**
 * 过滤掉字符串中的 sql 关键字
 */
if (!function_exists('filter_sql')) {
    function filter_sql($str)
    {
        $str = strtolower($str);        // 转小写
        $str = str_replace("and", "", $str);
        $str = str_replace("execute", "", $str);
        $str = str_replace("update", "", $str);
        $str = str_replace("count", "", $str);
        $str = str_replace("chr", "", $str);
        $str = str_replace("mid", "", $str);
        $str = str_replace("master", "", $str);
        $str = str_replace("truncate", "", $str);
        $str = str_replace("char", "", $str);
        $str = str_replace("declare", "", $str);
        $str = str_replace("select", "", $str);
        $str = str_replace("create", "", $str);
        $str = str_replace("delete", "", $str);
        $str = str_replace("insert", "", $str);
        $str = str_replace("union", "", $str);
        $str = str_replace("alter", "", $str);
        $str = str_replace("into", "", $str);
        $str = str_replace("'", "", $str);
        $str = str_replace("or", "", $str);
        $str = str_replace("=", "", $str);

        return $str;
    }
}



/**
 * 检测字符串是否是版本号
 */
if (!function_exists('is_version_str')) {
    /**
     * 检测字符串是否是版本号
     * @param string  $version
     * @return boolean
     */
    function is_version_str($version)
    {
        $match = "/^([0-9]\d|[0-9])(\.([0-9]\d|\d))+/";
        return preg_match($match, $version) ? true : false;
    }
}


/**
 * 删除目录
 */
if (!function_exists('rmdirs')) {

    /**
     * 删除目录
     * @param string $dirname  目录
     * @param bool   $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir() === 'rmdir') rmdir(($fileinfo->getRealPath()));
            if ($fileinfo->isDir() === 'unlink') unlink(($fileinfo->getRealPath()));
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }
}


/**
 * 复制目录
 */
if (!function_exists('copydirs')) {

    /**
     * 复制目录
     * @param string $source 源文件夹
     * @param string $dest   目标文件夹
     */
    function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            if (!mkdir($dest, 0755, true) && !is_dir($dest)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dest));
            }
        }
        foreach ($iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        ) as $item) {
            if ($item->isDir()) {
                $sontDir = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    if (!mkdir($sontDir, 0755, true) && !is_dir($sontDir)) {
                        throw new \RuntimeException(sprintf('Directory "%s" was not created', $sontDir));
                    }
                }
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }
}


if (!function_exists('is_url')) {
    function is_url($url)
    {
        if (preg_match("/^(http:\/\/|https:\/\/)/", $url)) {
            return true;
        }

        return false;
    }
}




/**
 * 快捷设置跨域请求头（跨域中间件失效时，一些特殊拦截时候需要用到）
 */
if (!function_exists('set_cors')) {
    /**
     * 快捷设置跨域请求头（跨域中间件失效时，一些特殊拦截时候需要用到）
     *
     * @return void
     */
    function set_cors()
    {
        $header = [
            'Access-Control-Allow-Origin' => '*',           // 规避跨域
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => 1800,
            'Access-Control-Allow-Methods'     => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers'     => 'Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With, platform',
        ];
        // 直接将 header 添加到响应里面
        foreach ($header as $name => $val) {
            header($name . (!is_null($val) ? ':' . $val : ''));
        }

        if (request()->isOptions()) {
            // 如果是预检直接返回响应，后续都不在执行
            exit;
        }
    }
}


if (!function_exists('sheep_config')) {
    /**
     * 获取SheepAdmin配置
     * @param string  $code    配置名
     * @return string
     */
    function sheep_config(string $code, $cache = true)
    {
        return \app\admin\model\shopro\Config::getConfigs($code, $cache);
    }
}


/**
 * 获取前端用户
 */
if (!function_exists('auth_user')) {
    function auth_user($throwException = false)
    {
        if (\app\common\library\Auth::instance()->isLogin()) {
            return \app\common\library\Auth::instance()->getUser();
        }
        if ($throwException) {
            error_stop('请登录后操作', 0, null, 401);
        }
        return null;
    }
}


/**
 * 获取管理员信息
 */
if (!function_exists('auth_admin')) {
    function auth_admin()
    {
        if (\app\admin\library\Auth::instance()->isLogin()) {
            $admin = \app\admin\library\Auth::instance()->getUserInfo();        // 这里获取的是个数组，转为模型
            if ($admin) {
                return \app\admin\model\shopro\Admin::where('id', $admin['id'])->find();
            }
        }

        return null;
    }
}




if (!function_exists('has_redis')) {

    /**
     * 判断是否配置好了 redis
     *
     * @param boolean $is_exception 是否 抛出异常
     * @return boolean
     */
    function has_redis($is_exception = false, $cache = true)
    {
        $key = 'has_redis';
        if ($cache && cache('?' . $key)) {
            // 使用缓存，并且存在缓存
            return cache($key);
        }

        $error_msg = '';
        try {
            addons\shopro\facade\Redis::ping();
        } catch (\BadFunctionCallException $e) {
            // 缺少扩展
            $error_msg = $e->getMessage() ? $e->getMessage() : "缺少 redis 扩展";
        } catch (\RedisException $e) {
            // 连接拒绝
            \think\Log::write('redis connection redisException fail: ' . $e->getMessage());
            $error_msg = $e->getMessage() ? $e->getMessage() : "redis 连接失败";
        } catch (\Exception $e) {
            // 异常
            \think\Log::write('redis connection fail: ' . $e->getMessage());
            $error_msg = $e->getMessage() ? $e->getMessage() : "redis 连接异常";
        }

        cache($key, ($error_msg ? false : true), 10);        // 保存缓存
        if ($error_msg) {
            if ($is_exception) {
                throw new \Exception($error_msg);
            } else {
                return false;
            }
        }

        return true;
    }
}


if (!function_exists('redis_cache')) {
    /**
     * 缓存管理
     * @param mixed     $name 缓存名称，如果为数组表示进行缓存设置
     * @param mixed     $value 缓存值
     * @param mixed     $options 缓存参数
     * @param string    $tag 缓存标签
     * @return mixed
     */
    function redis_cache($name = null, $value = '', $ttl = null)
    {
        $cache = new \addons\shopro\library\RedisCache;

        if ($name === null) {
            return $cache;
        } elseif ('' === $value) {
            // 获取缓存
            return 0 === strpos($name, '?') ? $cache->has(substr($name, 1)) : $cache->get($name);
        } elseif (is_null($value)) {
            // 删除缓存
            return $cache->delete($name);
        } else {
            // 缓存数据
            return $cache->set($name, $value, $ttl);
        }
    }
}

/**
 * 检测系统必要环境
 */
if (!function_exists('check_env')) {
    function check_env($need = [], $is_throw = true)
    {
        $need = is_string($need) ? [$need] : $need;

        // 检测是否安装浮点数运算扩展
        if (in_array('bcmath', $need)) {
            if (!extension_loaded('bcmath')) {
                if ($is_throw) {
                    error_stop('请安装浮点数扩展 【bcmath】');
                } else {
                    return false;
                }
            }
        }

        // 检测是否安装了队列
        if (in_array('queue', $need)) {
            if (!class_exists(\think\Queue::class)) {
                if ($is_throw) {
                    error_stop('请安装 【topthink/think-queue:v1.1.6 队列扩展】');
                } else {
                    return false;
                }
            }
        }

        // 检查是否有分销功能
        if (in_array('commission', $need)) {
            if (!class_exists(\addons\shopro\listener\Commission::class)) {
                if ($is_throw) {
                    error_stop('请先升级 【shopro】');
                } else {
                    return false;
                }
            }
        }

        // 检查是否安装了支付扩展包
        if (in_array('yansongda', $need)) {
            //读取插件的状态，epay为插件标识
            $info = get_addon_info('epay');
            if (!$info || !$info['state']) {
                if ($is_throw) {
                    error_stop('请确保【微信支付宝整合插件】已安装并启用');
                } else {
                    return false;
                }
            }

            if (version_compare($info['version'], '1.3.0') < 0) {
                if ($is_throw) {
                    error_stop('请安装【微信支付宝整合插件】v1.3.0 及以上版本');
                } else {
                    return false;
                }
            }
        }
        return true;
    }
}



if (!function_exists('diff_in_time')) {
    /**
     * 计算两个时间相差多少天，多少小时，多少分钟
     * 
     * @param int $first 要比较的第一个时间 Carbon 或者时间格式
     * @param mixed $second 要比较的第二个时间 Carbon 或者时间格式
     * @param bool $format 是否格式化为字符串
     * @return string|array 
     */
    function diff_in_time($first, $second = null, $format = true, $simple = false)
    {
        $second = is_null($second) ? time() : $second;

        $diff = abs($first - $second);      // 取绝对值

        $years = floor($diff / (86400 * 365));
        $surplus = $diff % (86400 * 365);

        $days = floor($surplus / 86400);
        $surplus = $surplus % 86400;

        $hours = floor($surplus / 3600);
        $surplus = $surplus % 3600;

        $minutes = floor($surplus / 60);
        $surplus = $surplus % 60;

        $second = $surplus;

        if (!$format) {
            return compact('years', 'days', 'hours', 'minutes', 'second');
        }

        $format_text = '';
        $start = false;
        if ($years) {
            $start = true;
            $format_text .= $years . '年';
        }
        if ($start || $days) {
            $start = true;
            $format_text .= ($days % 365) . '天';
        }

        if ($start || $hours) {
            $start = true;
            $format_text .= ($hours % 24) . '时';
        }
        if ($start || $minutes) {
            $start = true;
            $format_text .= ($minutes % 60) . '分钟';
        }
        if (($start || $second) && !$simple) {
            $start = true;
            $format_text .= ($second % 60) . '秒';
        }

        return $format_text;
    }
}


if (!function_exists('short_var_export')) {
    /**
     * 使用短标签打印或返回数组结构
     * @param mixed   $data
     * @param boolean $return 是否返回数据
     * @return string
     */
    function short_var_export($expression, $return = FALSE)
    {
        $export = var_export($expression, TRUE);
        $patterns = [
            "/array \(/" => '[',
            "/^([ ]*)\)(,?)$/m" => '$1]$2',
            "/=>[ ]?\n[ ]+\[/" => '=> [',
            "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
        ];
        $export = preg_replace(array_keys($patterns), array_values($patterns), $export);
        if ((bool)$return) return $export;
        else echo $export;
    }
}


if (!function_exists('get_sn')) {
    /**
     * 获取唯一编号
     *
     * @param mixed $id       唯一标识
     * @param string $type    类型
     * @return string
     */
    function get_sn($id, $type = '')
    {
        $id = (string)$id;

        $rand = $id < 9999 ? mt_rand(100000, 99999999) : mt_rand(100, 99999);
        $sn = date('Yhis') . $rand;

        $id = str_pad($id, (24 - strlen($sn)), '0', STR_PAD_BOTH);

        return $type . $sn . $id;
    }
}


if (!function_exists('string_hide')) {
    /**
     * 隐藏部分字符串
     *
     * @param string $string       原始字符串
     * @param int $start    开始位置
     * @return string
     */
    function string_hide($string, $start = 2)
    {
        if (mb_strlen($string) > $start) {
            $hide = mb_substr($string, 0, $start) . '***';
        } else {
            $hide = $string . '***';
        }

        return $hide;
    }
}


if (!function_exists('account_hide')) {
    /**
     * 隐藏账号部分字符串
     *
     * @param string $string       原始字符串
     * @param int $start    开始位置
     * @param int $end    开始位置
     * @return string
     */
    function account_hide($string, $start = 2, $end = 2)
    {
        $hide = mb_substr($string, 0, $start) . '*****' . mb_substr($string, -$end);
        return $hide;
    }
}



if (!function_exists('gen_random_str')) {
    /**
     * 随机生成字符串
     * @param int  $length    字符串长度
     * @return bool $upper 默认小写
     */
    function gen_random_str($length = 10, $upper = false)
    {
        if ($upper) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        } else {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        }

        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
}


if (!function_exists('random_mobile')) {
    /**
     * 随机生成字符串
     * @param int  $length    字符串长度
     * @return bool $upper 默认小写
     */
    function random_mobile()
    {
        $mobile = '1';
        $second = [3, 5, 7, 8, 9];
        return $mobile . $second[array_rand($second)] . mt_rand(100000000, 999999999);
    }
}


if (!function_exists('random_email')) {
    /**
     * 随机生成字符串
     * @param int  $length    字符串长度
     * @return bool $upper 默认小写
     */
    function random_email($prefix = '')
    {
        $first = [
            random_mobile(),
            gen_random_str(mt_rand(6, 15), mt_rand(0, 1)),
        ];

        $first_name = $prefix ?: $first[array_rand($first)];

        $suffix = [
            '@gmail.com',
            '@msn.com',
            '@qq.com',
            '@163.com',
            '@163.net',
            '@126.com',
            '@139.com',
            '@189.cn',
            '@sina.com',
        ];

        return $first_name . $suffix[array_rand($suffix)];
    }
}



if (!function_exists('format_log_error')) {
    /**
     * 格式化记录日志，重要地方使用
     *
     * @param object $error
     * @param string $name
     * @param string $message
     * @return void
     */
    function format_log_error($error, $name = 'QUEUE', $message = '')
    {
        $logInfo = [
            "========== $name LOG INFO BEGIN ==========",
            '[ Message ] ' . var_export('[' . $error->getCode() . ']' . $error->getMessage() . ' ' . $message, true),
            '[ File ] ' . var_export($error->getFile() . ':' . $error->getLine(), true),
            '[ Trace ] ' . var_export($error->getTraceAsString(), true),
            "============================================= $name LOG INFO ENDED ==========",
        ];

        $logInfo = implode(PHP_EOL, $logInfo) . PHP_EOL;
        \think\Log::error($logInfo);
    }
}

if (!function_exists('set_token_in_header')) {
    /**
     * 设置token令牌到响应的header中
     *
     * @param string $token
     * @return void
     */
    function set_token_in_header($token)
    {
        header("Access-Control-Expose-Headers: token");
        header("token: $token");
    }
}



if (!function_exists('morph_to')) {
    /**
     * 多态关联
     *
     * @param array|object $items
     * @param array $morphs
     * @param array $fields
     * @return array|object
     */
    function morph_to($items, $morphs, $fields)
    {
        if ($items instanceof \think\Paginator) {
            $data = $items->items();
        } else if ($items instanceof \think\Collection) {
            $data = $items;
        } else {
            $data = collection($items);
        }

        $allIds = [];
        foreach ($data as $item) {
            $allIds[$item[$fields[0]]][] = $item[$fields[1]];
        }

        $morphsData = [];
        foreach ($allIds as $key => $ids) {
            $morphData = (new $morphs[$key])::where('id', 'in', $ids)->select();
            $morphsData[$key] = array_column($morphData, null, 'id');
        }

        $morph_key = strstr($fields[0], '_', true);
        foreach ($data as &$item) {
            $item->{$morph_key} = $morphsData[$item[$fields[0]]][$item[$fields[1]]] ?? null;
        }

        if ($items instanceof \think\Paginator) {
            $items->data = $items;
        } else {
            $items = $data;
        }

        return $items;
    }
}



if (!function_exists('image_resize_save')) {
    /**
     * 图片裁剪缩放并保存
     * @param string $image_url    图片地址
     * @param string $save_path 保存地址
     * @param string $target_w 目标宽度
     * @param string $target_h 目标高度
     * @return void
     */
    function image_resize_save($image_url, $save_path, $target_w = 200, $target_h = 200)
    {
        $dst_h = 200;
        $dst_w = 200;

        list($src_w, $src_h) = getimagesize($image_url);
        $dst_scale = $dst_h / $dst_w;             // 目标图像长宽比，正方形
        $src_scale = $src_h / $src_w; // 原图长宽比

        if ($src_scale >= $dst_scale) {  // 过高
            $w = intval($src_w);
            $h = intval($dst_scale * $w);

            $x = 0;
            $y = ($src_h - $h) / 3;
        } else { // 过宽
            $h = intval($src_h);
            $w = intval($h / $dst_scale);

            $x = ($src_w - $w) / 2;
            $y = 0;
        }

        // 剪裁
        $source = imagecreatefrompng($image_url);
        $croped = imagecreatetruecolor($w, $h);
        imagecopy($croped, $source, 0, 0, $x, $y, $src_w, $src_h);

        if ($w > $dst_w) {
            $scale = $dst_w / $w;
            $target = imagecreatetruecolor($dst_w, $dst_h);
            $final_w = intval($w * $scale);
            $final_h = intval($h * $scale);
            imagecopyresampled($target, $croped, 0, 0, 0, 0, $final_w, $final_h, $w, $h);
        }

        // 创建目录
        $dir = dirname($save_path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        imagejpeg($target, $save_path);
        imagedestroy($target);
    }
}

if (!function_exists('get_addonnames')) {
    
    /**
     * 获取已安装的插件
     * 
     * @param $type enable:已安装启用的插件，disabled:已安装禁用的插件，all:所有插件
     */
    function get_addonnames($type = 'enable')
    {
        $key = 'shopro_fa_addons_list';
        if (redis_cache('?' . $key)) {
            $addons = redis_cache($key);
        } else {
            $addons = get_addon_list();
            redis_cache($key, $addons, 60);
        }

        $addonList = [
            'all' => [],
            'enable' => [],
            'disabled' => []
        ];
        foreach ($addons as $addon_name => $info) {
            $addonList['all'][] = $addon_name;
            $addonList[($info['state'] == 1 ? 'enable' : 'disabled')][] = $addon_name;
        }

        return $addonList[$type] ?? [];
    }
}



if (!function_exists('client_unique')) {
    /**
     * 获取客户端唯一标识
     *
     * @return boolean
     */
    function client_unique()
    {
        // $httpName = app('http')->getName();
        $httpName = '';
        $url = request()->baseUrl();
        $ip = request()->ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $key = $httpName . ':' . $url . ':' . $ip . ':' . $user_agent;

        return md5($key);
    }
}



/**
 * 删除 sql mode 指定模式，或者直接关闭 sql mode
 */
if (!function_exists('closeStrict')) {
    function closeStrict($modes = [])
    {
        $modes = array_filter(is_array($modes) ? $modes : [$modes]);

        $result = \think\Db::query("SELECT @@session.sql_mode ");
        $newModes = $oldModes = explode(',', ($result[0]['@@session.sql_mode'] ?? ''));

        if ($modes) {
            foreach ($modes as $mode) {
                $delkey = array_search($mode, $newModes);
                if ($delkey !== false) {
                    unset($newModes[$delkey]);
                }
            }
            $newModes = join(',', array_values(array_filter($newModes)));
        } else {
            $newModes = '';
        }

        \think\Db::execute("set session sql_mode='" . $newModes . "'");

        return $oldModes;
    }
}


/**
 * 重新打开被关闭的 sql mode
 */
if (!function_exists('recoverStrict')) {
    function recoverStrict($modes = [], $append = false)
    {
        if ($append) {
            $result = \think\Db::query("SELECT @@session.sql_mode ");
            $oldModes = explode(',', ($result[0]['@@session.sql_mode'] ?? ''));

            $modes = array_values(array_filter(array_unique(array_merge($oldModes, $modes))));
        }

        \think\Db::execute("set session sql_mode='" . join(',', $modes) . "'");
    }
}


// **********************以下方法请忽略*********************

// 当前方法直接返回 $value, 请忽略该方法
if (!function_exists('config_show')) {
    function config_show($value, $data)
    {
        if (class_exists(\app\common\library\ShowHow::class)) {
            return \app\common\library\ShowHow::configHide($value, $data);
        }

        return $value;
    }
}

// 当前方法直接返回 $config, 请忽略该方法
if (!function_exists('pay_config_show')) {
    function pay_config_show($config)
    {
        if (class_exists(\app\common\library\ShowHow::class)) {
            return \app\common\library\ShowHow::payConfigHide($config);
        }

        return $config;
    }
}

// 当前方法全部返回 true， 请忽略该方法
if (!function_exists('operate_filter')) {
    function operate_filter($is_exception = true)
    {
        if (class_exists(\app\common\library\ShowHow::class)) {
            return \app\common\library\ShowHow::operateFilter($is_exception);
        }

        return true;
    }
}

// 当前方法全部返回 true， 请忽略
if (!function_exists('operate_disabled')) {
    function operate_disabled($is_exception = true)
    {
        if (class_exists(\app\common\library\ShowHow::class)) {
            return \app\common\library\ShowHow::operateDisabled($is_exception);
        }

        return true;
    }
}

