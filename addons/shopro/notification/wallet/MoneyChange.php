<?php

namespace addons\shopro\notification\wallet;

use addons\shopro\notification\Notification;
use addons\shopro\notification\traits\Notification as NotificationTrait;

/**
 * 余额变动通知
 */
class MoneyChange extends Notification
{
    use NotificationTrait;

    // 队列延迟时间，必须继承 ShouldQueue 接口
    public $delay = 0;

    public $receiver_type = 'user';      // 接收人:user=用户

    // 消息类型  Notification::$notificationType
    public $notification_type = 'shop';

    // 发送类型
    public $event = 'money_change';

    // 额外数据
    public $data = [];

    public $template = [
        'MessageDefaultContent' => '您的余额发生变化，变动类型：{event_name}，变动金额：{amount}，请及时查看',
        // 'WechatOfficialAccount' => [
        //     'temp_no' => 'OPENTM401833445',
        //     'fields' => [
        //         // 'first' => '您的余额发生变动，请及时查看',
        //         // 'keyword1' => 'create_date',                               // 变动时间
        //         // 'keyword2' => 'event_name',                               // 变动类型
        //         // 'keyword3' => 'amount',                               // 变动金额
        //         // 'keyword4' => 'money',                               // 当前余额
        //         // 'remark' => '您的余额发生变动，请及时查看',
        //         [
        //             "template_field" => "first",
        //             "value" => '您的余额发生变动，请及时查看',
        //         ],
        //         [
        //             "name" => "变动时间",
        //             "field" => "create_date",
        //             "template_field" => "keyword1",
        //         ],
        //         [
        //             "name" => "变动类型",
        //             "field" => "event_name",
        //             "template_field" => "keyword2",
        //         ],
        //         [
        //             "name" => "变动金额",
        //             "field" => "amount",
        //             "template_field" => "keyword3",
        //         ],
        //         [
        //             "name" => "当前余额",
        //             "field" => "money",
        //             "template_field" => "keyword4",
        //         ],
        //         [
        //             "template_field" => "remark",
        //             "value" => '您的余额发生变动，请及时查看',
        //         ]
        //     ],
        // ],
        'WechatOfficialAccount' => [
            'temp_no' => false,         // 目前公众号类目模板库，找不到符合条件模板
            'keywords' => [],
            'fields' => [],
        ],
        'WechatMiniProgram' => [
            'category_id' => 670,
            'tid' => '4148',
            'kid' => [8,1,2,4,5],      // 变动类型,变动金额,账户余额,时间,备注
            'scene_desc' => '当余额发生变化时通知用户',     // 申请模板场景描述
            'fields' => [
                // 'thing8' => 'event_name',                               // 变动类型
                // 'amount1' => 'amount',                               // 变动金额
                // 'amount2' => 'money',                               // 账户余额
                // 'date' => 'create_date',                               // 时间
                // 'thing5' => 'memo',                               // 备注
                [
                    "name" => "变动类型",
                    "field" => "event_name",
                    "template_field" => "thing8",
                ],
                [
                    "name" => "变动金额",
                    "field" => "amount",
                    "template_field" => "amount1",
                ],
                [
                    "name" => "账户余额",
                    "field" => "money",
                    "template_field" => "amount2",
                ],
                [
                    "name" => "时间",
                    "field" => "create_date",
                    "template_field" => "date4",
                ],
                [
                    "name" => "备注",
                    "field" => "memo",
                    "template_field" => "thing5",
                ],
            ],
        ]
    ];

    // 返回的字段列表
    public $returnField = [
        'name' => '余额变动通知',
        'channels' => ['Sms', 'Email', 'WechatOfficialAccount', 'WechatMiniProgram'],
        'fields' => [
            ['name' => '消息名称', 'field' => 'template'],
            ['name' => '变动用户', 'field' => 'nickname'],
            ['name' => '用户手机', 'field' => 'mobile'],
            ['name' => '变动类型', 'field' => 'event_name'],
            ['name' => '变动金额', 'field' => 'amount'],
            ['name' => '变动前', 'field' => 'before'],
            ['name' => '变动后', 'field' => 'after'],
            ['name' => '当前余额', 'field' => 'money'],
            ['name' => '备注信息', 'field' => 'memo'],
            ['name' => '变动时间', 'field' => 'create_date'],
        ]
    ];



    /**
     * 组合数据参数
     *
     * @param \think\Model $notifiable
     * @return array
     */
    protected function getData($notifiable)
    {
        $walletLog = $this->data['walletLog'];
        $type = $this->data['type'];

        $data['template'] = $this->returnField['name'];           // 模板名称
        $data['nickname'] = $notifiable['nickname'];
        $data['mobile'] = $notifiable['mobile'];
        $data['event_name'] = '余额变动';
        $data['amount'] = '￥' . $walletLog['amount'];
        $data['before'] = '￥' . $walletLog['before'];
        $data['after'] = '￥' . $walletLog['after'];
        $data['money'] = '￥' . $walletLog['after'];
        $data['memo'] = $walletLog['event_text'] . ($walletLog['memo'] ? ('-' . $walletLog['memo']) : '');
        $data['create_date'] = $walletLog['createtime'];

        // 统一跳转地址
        $data['jump_url'] = "/pages/user/wallet/money";

        return $data;
    }
}
