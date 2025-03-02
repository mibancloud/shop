<?php

namespace addons\shopro\job;

use think\exception\HttpResponseException;
use think\queue\Job;
use GuzzleHttp\Client;

/**
 * 设计师模板图片转存
 */
class Designer extends BaseJob
{


    protected $client = null;

    /**
     * 转存
     */
    public function redeposit(Job $job, $data)
    {

        // 删除 job，防止这个队列一直异常无法被删除
        $job->delete();
        try {
            $imageList = $data['imageList'];
            foreach ($imageList as $image) {
                try {
                    // 路径转存图片，这里只保存到本地 public/storage 目录
                    $this->redepositSave($image, parse_url($image, PHP_URL_PATH));
                } catch (HttpResponseException $e) {
                    $data = $e->getResponse()->getData();
                    $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
                    \think\Log::error('设计师模板图片转存失败-HttpResponseException: ' . $message);
                } catch (\Exception $e) {
                    \think\Log::error('设计师模板图片转存失败: ' . $e->getMessage());
                }
            }
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'Designer.redeposit.HttpResponseException', $message);
        } catch (\Exception $e) {
            // 队列执行失败
            format_log_error($e, 'Designer.redeposit');
        }
    }


    /**
     * 转存到本地
     *
     * @param [type] $path
     * @param [type] $save_path
     * @return void
     */
    private function redepositSave($path, $save_path)
    {
        $response = $this->getClient()->get($path);
        $body = $response->getBody()->getContents();    // 图片数据流

        $save_path = ROOT_PATH . 'public/' . ltrim($save_path, '/');
        $save_dir = dirname($save_path);
        if (!is_dir($save_dir)) {
            @mkdir($save_dir, 0755, true);
        }

        file_put_contents($save_path, $body);       // 转存本地
    }



    private function getClient()
    {
        if ($this->client) {
            return $this->client;
        }

        return $this->client = new Client();
    }
}
