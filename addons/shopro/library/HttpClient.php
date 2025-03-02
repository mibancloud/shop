<?php

namespace addons\shopro\library;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


/**
 * 本 HttpClient 主要为了解决 yansongda\pay Http 必须继承 ClientInterface 问题（fa 框架的 GuzzleHttp\client 为 6.* 未继承 psr ClientInterface
 * 也可直接将本类当作 GuzzleHttp\Client 使用
 */
class HttpClient extends Client implements ClientInterface
{

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $options[RequestOptions::SYNCHRONOUS] = true;
        $options[RequestOptions::ALLOW_REDIRECTS] = false;
        $options[RequestOptions::HTTP_ERRORS] = false;

        return $this->sendAsync($request, $options)->wait();
    }
}
