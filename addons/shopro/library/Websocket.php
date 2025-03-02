<?php

namespace addons\shopro\library;

use fast\Http;
use addons\shopro\library\chat\traits\Helper;
use GuzzleHttp\Client;

class Websocket
{
    use Helper;

    protected $config = null;

    protected $base_uri = null;

    public function __construct()
    {
        $this->config = $this->getConfig('system');
        $inside_host = $this->config['inside_host'] ?? '127.0.0.1';
        $inside_port = $this->config['inside_port'] ?? '9191';

        $this->base_uri = 'http://' . $inside_host . ':' . $inside_port;
    }



    public function notification($data) 
    {
        $client = new Client();
        $response = $client->post($this->base_uri . '/notification', [
            'form_params' => $data
        ]);

        // 获取结果
        $result = $response->getBody()->getContents();

        return $result == 'ok' ? true : $result;
    }
}
