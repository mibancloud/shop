<?php

namespace app\admin\controller\shopro\app\mplive;

use fast\Http;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\app\mplive\Room as MpliveRoomModel;
use addons\shopro\library\mplive\ServiceProvider;
use addons\shopro\facade\Wechat;

/**
 * 小程序直播
 */
class Index extends Common
{
    protected $wechat;

    public function _initialize()
    {
        parent::_initialize();
        $this->wechat = Wechat::miniProgram();

        (new ServiceProvider())->register($this->wechat);
    }

    // 上传临时素材
    protected function uploadMedia($path)
    {
        $filesystem = config('filesystem.default');
        if ($filesystem !== 'local' || is_url($path)) {
            $body = Http::get(cdnurl($path, true));
            $dir = RUNTIME_PATH . 'storage' . DS . 'temp';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $temp_path = $dir . $this->getBaseName($path);
            file_put_contents($temp_path, $body);
        } else {
            $temp_path = ROOT_PATH . 'public' . $path;
        }

        if (!isset($temp_path) || empty($temp_path)) {
            error_stop("上传失败，不是一个有效的文件: " . $path);
        }

        $media = $this->wechat->media;
        $res = $media->uploadImage($temp_path);
        @unlink($temp_path);        // 删除临时文件
        if (isset($res['media_id'])) {
            return $res['media_id'];
        }
        return '';
    }

    // 解析图片文件名
    private function getBaseName($path)
    {
        if (strpos($path, 'mmbiz.qpic.cn') !== false) {
            return DS . gen_random_str(8) . '.jpg';
        }

        return basename($path);
    }

    // 转义直播错误信息
    protected function catchLiveError($response)
    {
        if (!isset($response['errcode'])) {
            error_stop("未知错误");
        }

        $errorMap = MpliveRoomModel::ERR_CODE;
        if (isset($response['errcode']) && ($response['errcode'] !== 0 && $response['errcode'] !== 1001)) {
            if (isset($errorMap[$response['errcode']])) {
                error_stop("{$errorMap[$response['errcode']]} [错误码: {$response['errcode']}]");
            } else {
                error_stop("{$response['errmsg']} [错误码: {$response['errcode']}]");
            }
        }
    }
}
