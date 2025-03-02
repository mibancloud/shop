<?php

namespace app\admin\model\shopro\wechat;

use app\admin\model\shopro\Common;
use traits\model\SoftDelete;

class Reply extends Common
{
    use SoftDelete;
    protected $deleteTime = 'deletetime';

    protected $name = 'shopro_wechat_reply';

    // 追加属性
    protected $append = [
        'type_text',
        'group_text',
        'status_text',
    ];


    public function getKeywordsAttr($value, $data)
    {
        if (!empty($value)) {
            return explode(',', $value);
        }
        return [];
    }

    public function setKeywordsAttr($value, $data)
    {
        if (!empty($value)) {
            return implode(',', $value);
        }
        return null;
    }
    /**
     * 状态列表
     *
     * @return array
     */
    public function typeList()
    {
        return ['text' => '文字', 'link' => '链接', 'image' => '图片', 'voice' => '语音', 'video' => '视频', 'news' => '图文消息'];
    }

    /**
     * 通用类型获取器
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getGroupTextAttr($value, $data)
    {
        $list = ['keywords' => '关键词回复', 'subscribe' => '关注回复', 'default' => '默认回复'];

        $value = $value ?: ($data['group'] ?? null);

        return isset($list[$value]) ? $list[$value] : '';
    }
}
