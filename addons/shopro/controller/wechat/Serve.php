<?php

namespace addons\shopro\controller\wechat;

use addons\shopro\facade\Wechat;
use app\admin\model\shopro\wechat\Reply;
use app\admin\model\shopro\wechat\Material;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\Kernel\Messages\Media;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Messages\News;
use EasyWeChat\Kernel\Messages\NewsItem;
use EasyWeChat\Kernel\Messages\Video;
use EasyWeChat\Kernel\Messages\Voice;
use app\admin\model\shopro\ThirdOauth;

class Serve
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    protected $wechat = null;
    protected $openid = "";

    // 微信api
    public function index()
    {
        $this->wechat = Wechat::officialAccountManage();
        $this->wechat->server->push(function ($message) {
            $this->openid = $message['FromUserName'];
            return $this->reply($message);
        });
        $this->wechat->server->serve()->send();
    }

    // 回复消息
    private function reply($message)
    {
        switch ($message['MsgType']) {
                // 收到事件
            case 'event':
                switch ($message['Event']) {
                        // 订阅（关注）事件
                    case 'subscribe':
                        $data = ['openid' => $this->openid];
                        \think\Hook::listen('wechat_subscribe', $data);

                        $reply = Reply::where('group', 'subscribe')->enable()->find();
                        if ($reply) {
                            $this->getReplyData($reply);
                        }
                        if (!empty($message['EventKey'])) {
                            $event = str_replace('qrscene_', '', $message['EventKey']);
                            return $this->scanQrcode($event);
                        }
                        break;

                        // 取消订阅（关注）事件
                    case 'unsubscribe':
                        $data = ['openid' => $this->openid];
                        \think\Hook::listen('wechat_unsubscribe', $data);
                        break;

                        //自定义菜单事件
                    case 'CLICK':
                        $event = explode('|', $message['EventKey']);
                        return $this->getClickData($event);
                        break;
                    case 'SCAN':
                        if (!empty($event = $message['EventKey'])) {
                            return $this->scanQrcode($event);
                        }
                        break;
                }
                break;
                // 收到文本消息
            case 'text':
                //检测关键字回复
                $keywords = $message['Content'];
                $reply = Reply::where('group', 'keywords')->enable()->where('find_in_set(:keywords, keywords)', ['keywords' => $keywords])->find();
                if ($reply) {
                    return $this->getReplyData($reply);
                }
                break;
                // 收到图片消息 暂不支持此消息类型
            case 'image':
                // 收到语音消息 暂不支持此消息类型
            case 'voice':
                // 收到视频消息 暂不支持此消息类型
            case 'video':
                // 收到坐标消息 暂不支持此消息类型
            case 'location':
                // 收到链接消息 暂不支持此消息类型
            case 'link':
                // 收到文件消息 暂不支持此消息类型
            case 'file':
                // 默认回复消息
            default:
                $reply = Reply::where('group', 'default')->enable()->find();
                if ($reply) {
                    return $this->getReplyData($reply);
                }
        }
        return true;
    }

    // 组装回复消息的数据结构
    private function getReplyData($reply)
    {
        switch ($reply->type) {
                // 回复文本消息
            case 'text':
                $material = Material::find($reply->content);
                if ($material) {
                    $data = new Text($material->content);
                }
                break;
                // 回复链接卡片
            case 'link':
                $material = Material::find($reply->content);
                if ($material) {
                    $link = $material->content;
                    $items = new NewsItem([
                        'title'       => $link['title'],
                        'description' => $link['description'],
                        'url'         => $link['url'],
                        'image'       => cdnurl($link['image'], true),
                    ]);
                    $data = new News([$items]);
                }
                break;
            case 'video':
                $data = new Video($reply->content);
                break;
            case 'voice':
                $data = new Voice($reply->content);
                break;
            case 'image':
                $data = new Image($reply->content);
                break;
            case 'news':
                $data = new Media($reply->content, 'mpnews');
                break;
        }
        // 使用客服消息发送
        $this->wechat->customer_service->message($data)->to($this->openid)->send();
    }

    // 组装事件消息的数据结构
    private function getClickData($event)
    {
        switch ($event[0]) {
                // 回复文本消息
            case 'text':
                $material = Material::find($event[1]);
                if ($material) {
                    $data = new Text($material->content);
                }
                break;
                // 回复链接卡片
            case 'link':
                $material = Material::find($event[1]);
                if ($material) {
                    $link = $material->content;
                    $items = new NewsItem([
                        'title'       => $link['title'],
                        'description' => $link['description'],
                        'url'         => $link['url'],
                        'image'       => cdnurl($link['image'], true),
                    ]);
                    $data = new News([$items]);
                }
                break;
            case 'video':
                $data = new Video($event[1]);
                break;
            case 'voice':
                $data = new Voice($event[1]);
                break;
            case 'image':
                $data = new Image($event[1]);
                break;
            case 'news':
                $data = new Media($event[1], 'mpnews');
                break;
        }
        // 使用客服消息发送
        $this->wechat->customer_service->message($data)->to($this->openid)->send();
    }

    // 扫一扫微信二维码
    private function scanQrcode($eventStr)
    {
        list($flag, $event, $eventId) = explode('.', $eventStr);
        $text = '';
        if (empty($flag) || empty($event)) {
            $text = '未找到对应扫码事件';
        } else {
            switch ($event) {
                case 'login':
                    // $text = $this->login($eventId);
                    break;
                case 'bind':
                    $text = $this->bind($eventId);
                    break;
            }
        }
        if (!empty($text)) {
            $this->wechat->customer_service->message(new Text($text))->to($this->openid)->send();
        }
        return;
    }


    // 扫一扫绑定管理员
    private function bind($eventId)
    {
        $cacheKey = "wechatAdmin.bind.{$eventId}";

        $cacheValue = cache($cacheKey);
        if (empty($cacheValue)) {
            return '二维码已过期,请重新扫码';
        }

        $thirdOauth = ThirdOauth::where([
            'provider' => 'wechat',
            'platform' => 'admin',
            'openid' => $this->openid
        ])->find();

        if ($thirdOauth && $thirdOauth->admin_id !== 0) {
            return '该微信账号已绑定其他管理员';
        }

        cache($cacheKey, ['id' => $this->openid], 1 * 60);

        return '正在绑定管理员快捷登录';
    }
}
