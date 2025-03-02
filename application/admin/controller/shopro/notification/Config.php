<?php

namespace app\admin\controller\shopro\notification;

use app\admin\controller\shopro\Common;
use app\admin\model\shopro\notification\Config as ConfigModel;
use app\admin\controller\shopro\notification\traits\Notification as NotificationTraits;
use addons\shopro\facade\Wechat;

class Config extends Common
{
    use NotificationTraits;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new ConfigModel;
    }



    /**
     * 消息通知配置
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $receiver_type = $this->request->param('receiver_type');

        $notifications = $this->getNotificationsByReceiverType($receiver_type);

        $groupConfigs = $this->getGroupConfigs();
        foreach ($notifications as $key => &$notification) {
            $currentConfigs = $groupConfigs[$notification['event']] ?? [];
            foreach ($notification['channels'] as $channel) {
                $notification['configs'][$channel] = [
                    'status' => isset($currentConfigs[$channel]) ? $currentConfigs[$channel]['status'] : 'disabled',
                    'send_num' => isset($currentConfigs[$channel]) ? $currentConfigs[$channel]['send_num'] : 0,
                ];
            }
        }

        $this->success('获取成功', null, $notifications);
    }



    public function detail()
    {
        $event = $this->request->param('event');
        $channel = $this->request->param('channel');
        if (!$event || !$channel) {
            error_stop('参数错误');
        }

        $notification = $this->getNotificationByEvent($event);

        $notification = $this->formatNotification($notification, $event, $channel);

        $this->success('获取成功', null, $notification);
    }


    // 编辑配置
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $event = $this->request->param('event');
        $channel = $this->request->param('channel');
        if ($channel == 'Email') {
            $content = $this->request->param('content', '');
        } else {
            $content = $this->request->param('content/a', []);
        }
        $type = $this->request->param('type', 'default');
        if (!$event || !$channel) {
            error_stop('参数错误');
        }

        $config = $this->model->where('event', $event)->where('channel', $channel)->find();

        if (!$config) {
            $config = $this->model;
            $config->event = $event;
            $config->channel = $channel;
        }

        if (in_array($channel, ['WechatOfficialAccount', 'WechatMiniProgram']) && $type == 'default') {
            // 自动组装微信默认模板
            $content['fields'] = $this->formatWechatTemplateFields($event, $channel, $content['fields']);
        }

        $config->type = $type;
        $config->content = $content;
        $config->save();

        $this->success('设置成功');
    }



    // 配置状态
    public function setStatus($event, $channel)
    {
        $event = $this->request->param('event');
        $channel = $this->request->param('channel');
        $status = $this->request->param('status', 'disabled');

        if (!$event || !$channel) {
            $this->error('参数错误');
        }
        
        $config = $this->model->where('event', $event)->where('channel', $channel)->find();
        if (!$config) {
            $config = $this->model;
            $config->event = $event;
            $config->channel = $channel;
            $config->type = 'default';
        }
        $config->status = $status;
        $config->save();

        $this->success('设置成功');
    }


    /**
     * 自动获取微信模板 id
     */
    public function getTemplateId()
    {
        $event = $this->request->param('event');
        $channel = $this->request->param('channel');
        $is_delete = $this->request->param('is_delete', 0);
        $template_id = $this->request->param('template_id', '');
        if (!$event || !$channel) {
            error_stop('参数错误');
        }

        $notification = $this->getNotificationByEvent($event);

        $template = $notification['template'][$channel] ?? null;
        if (!$template) {
            $this->error('模板不存在');
        }

        // 请求微信接口
        switch ($channel) {
            case 'WechatMiniProgram':           // 小程序订阅消息
                $requestParams['tid'] = $template['tid'];
                $requestParams['kid'] = $template['kid'];
                $requestParams['sceneDesc'] = $template['scene_desc'];
                if (!$requestParams['tid'] || !$requestParams['kid']) {
                    $this->error('缺少模板参数');
                }
                $wechat = Wechat::miniProgram()->subscribe_message;
                $delete_method = 'deleteTemplate';
                $result_key = 'priTmplId';
                break;
            // case 'WechatOfficialAccount':       // 公众号模板消息
            //     $requestParams['template_id'] = $template['temp_no'];
            //     if (!$requestParams['template_id']) {
            //         $this->error('缺少模板参数,获取失败');
            //     }
            //     $wechat = Wechat::officialAccount()->template_message;    // 微信管理
            //     $result_key = 'template_id';
            //     $delete_method = 'deletePrivateTemplate';
            //     break;
            case 'WechatOfficialAccount':       // 新版公众号模板消息
                $requestParams['template_id'] = $template['temp_no'];
                $requestParams['keywords'] = $template['keywords'];

                if (!$requestParams['template_id']) {
                    $this->error('公众号类目模板库目前不完善，请自行在公众号后台->模板消息->选择模板配置');
                }
                if (!$requestParams['keywords']) {
                    $this->error('缺少模板关键字,获取失败');
                }

                $wechat = new \addons\shopro\library\easywechatPlus\WechatOfficialTemplate(Wechat::officialAccount());

                $result_key = 'template_id';
                $delete_method = 'deletePrivateTemplate';
                break;
            case 'WechatOfficialAccountBizsend':       // 公众号订阅消息（待补充）
                $requestParams['tid'] = $template['tid'];
                $requestParams['kid'] = $template['kid'];
                if (!$requestParams['tid'] || !$requestParams['kid']) {
                    $this->error('缺少模板参数,获取失败');
                }
                $wechat = Wechat::officialAccount()->subscribe_message;   // 微信管理
                $result_key = 'priTmplId';
                $delete_method = 'deleteTemplate';
                break;
            default:
                $this->error('当前发送渠道不能获取模板');
                break;
        }

        $result = $wechat->addTemplate(...array_values($requestParams));

        if ($result['errcode'] != 0) {
            $this->error('获取失败: errcode:' . $result['errcode'] . '; errmsg:' . $result['errmsg']);
        } else {
            if ($is_delete) {
                // 删除传入的老模板
                if ($template_id) {
                    $deleteResult = $wechat->{$delete_method}($template_id);
                }
                // 删除数据库的老模板
                $config = $this->model->where('event', $event)->where('channel', $channel)->find();
                $template_id = $config ? ($config->content['template_id'] ?? null) : null;
                if ($template_id) {
                    $deleteResult = $wechat->{$delete_method}($template_id);
                }
            }
        }

        $this->success('获取成功', null, ($result[$result_key] ?? null));
    }
}