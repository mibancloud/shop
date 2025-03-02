<?php

namespace addons\shopro\controller;

use think\Lang;
use app\common\controller\Api;
use addons\shopro\controller\traits\Util;
use addons\shopro\controller\traits\UnifiedToken;

/**
 * shopro 基础控制器
 */
class Common extends Api
{
    use Util, UnifiedToken;

    protected $model = null;

    public function _initialize()
    {
        Lang::load(APP_PATH . 'api/lang/zh-cn.php');        // 加载语言包
        parent::_initialize();

        if (check_env('yansongda', false)) {
            set_addon_config('epay', ['version' => 'v3'], false);
        }
    }


    // /**
    //  * 操作成功返回的数据
    //  * @param string $msg    提示信息
    //  * @param mixed  $data   要返回的数据
    //  * @param int    $code   错误码，默认为1
    //  * @param string $type   输出类型
    //  * @param array  $header 发送的 Header 信息
    //  */
    // protected function success($msg = '', $data = null, $error = 0, $type = null, array $header = [])
    // {
    //     $this->result($msg, $data, $error, $type, $header);
    // }

    // /**
    //  * 操作失败返回的数据
    //  * @param string $msg    提示信息
    //  * @param mixed  $data   要返回的数据
    //  * @param int    $code   错误码，默认为0
    //  * @param string $type   输出类型
    //  * @param array  $header 发送的 Header 信息
    //  */
    // protected function error($msg = '', $error = 1, $data = null, $type = null, array $header = [])
    // {
    //     $this->result($msg, $data, $error, $type, $header);
    // }

    // /**
    //  * 返回封装后的 API 数据到客户端
    //  * @access protected
    //  * @param mixed  $msg    提示信息
    //  * @param mixed  $data   要返回的数据
    //  * @param int    $code   错误码，默认为0
    //  * @param string $type   输出类型，支持json/xml/jsonp
    //  * @param array  $header 发送的 Header 信息
    //  * @return void
    //  * @throws HttpResponseException
    //  */
    // protected function result($msg, $data = null, $error = 0, $type = null, array $header = [])
    // {
    //     $result = [
    //         'error' => $error,
    //         'msg'  => $msg,
    //         'time' => Request::instance()->server('REQUEST_TIME'),
    //         'data' => $data,
    //     ];
    //     // 如果未设置类型则自动判断
    //     $type = $type ? $type : ($this->request->param(config('var_jsonp_handler')) ? 'jsonp' : $this->responseType);

    //     $statuscode = 200;
    //     if (isset($header['statuscode'])) {
    //         $statuscode = $header['statuscode'];
    //         unset($header['statuscode']);
    //     } 
    //     $response = Response::create($result, $type, $statuscode)->header($header);
    //     throw new HttpResponseException($response);
    // }

}
