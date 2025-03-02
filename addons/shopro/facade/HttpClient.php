<?php

namespace addons\shopro\facade;

use addons\shopro\library\HttpClient as HttpClientManager;

/**
 * @see HttpClientManager
 * 
 */
class HttpClient extends Base
{
    public static function getFacadeClass() 
    {
        if (!isset($GLOBALS['SPHTTPCLIENT'])) {
            $GLOBALS['SPHTTPCLIENT'] = new HttpClientManager();
        }

        return $GLOBALS['SPHTTPCLIENT'];
    }
}
