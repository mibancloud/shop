<?php

namespace addons\shopro\library\easywechatPlus;

/**
 * 补充 公众号行业模板
 */
class WechatOfficialTemplate extends EasywechatPlus
{

    /**
     * 添加公众号模板
     *
     * @param string $shortId 模板 id
     * @param array $keywordList   模板关键字
     * @return void
     */
    public function addTemplate($shortId, $keywordList)
    {
        $params = ['template_id_short' => $shortId];

        if ($keywordList) {
            $params['keyword_name_list'] = $keywordList;
        }

        $access_token = $this->getAccessToken();

        $add_template_url = "https://api.weixin.qq.com/cgi-bin/template/api_add_template";
        $result = \addons\shopro\facade\HttpClient::request('post', $add_template_url, [
            'body' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'query' => ["access_token" => $access_token['access_token']],
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $result = $result->getBody()->getContents();
        return json_decode($result, true);
    }



    /**
     * 方法转发到 easywechat
     *
     * @param string $funcname
     * @param array $arguments
     * @return void
     */
    public function __call($funcname, $arguments)
    {
        if ($funcname == 'deletePrivateTemplate') {
            return $this->app->template_message->{$funcname}(...$arguments);
        }

        return $this->app->{$funcname}(...$arguments);
    }
}
