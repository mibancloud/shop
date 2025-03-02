<?php

namespace addons\shopro\notification\order;

use addons\shopro\notification\Notification;
use addons\shopro\notification\traits\Notification as NotificationTrait;

/**
 * 订单申请全额退款通知
 */
class OrderApplyRefund extends Notification
{
    use NotificationTrait;

    // 队列延迟时间，必须继承 ShouldQueue 接口
    public $delay = 0;

    public $receiver_type = 'admin';      // 接收人:admin=管理员

    // 消息类型  Notification::$notificationType
    public $notification_type = 'shop';

    // 发送类型
    public $event = 'order_apply_refund';

    // 额外数据
    public $data = [];

    public $template = [
        'MessageDefaultContent' => '您有订单申请全额退款，下单用户：{nickname}，订单号：{order_sn}，支付金额：{pay_fee}，{refund_status}',
        // 'WechatOfficialAccount' => [
        //     'temp_no' => 'TM00004',
        //     'fields' => [
        //         // 'first' => '您有订单申请全额退款，请及时处理',
        //         // 'reason' => 'reason',                               // 退款原因
        //         // 'refund' => 'pay_fee',                               // 退款金额
        //         // 'remark' => '您有订单申请全额退款，请及时处理',
        //         [
        //             "template_field" => "first",
        //             "value" => '您有订单申请全额退款，请及时处理',
        //         ],
        //         [
        //             "name" => "退款原因",
        //             "field" => "reason",
        //             "template_field" => "reason",
        //         ],
        //         [
        //             "name" => "退款金额",
        //             "field" => "pay_fee",
        //             "template_field" => "refund",
        //         ],
        //         [
        //             "template_field" => "remark",
        //             "value" => '您有订单申请全额退款，请及时处理',
        //         ]
        //     ],
        // ],
        'WechatOfficialAccount' => [
            'temp_no' => '46044',
            'keywords' => ['订单编号', '退款金额', '申请时间'],
            'fields' => [
                [
                    "name" => "订单编号",
                    "field" => "order_sn",
                    "template_field" => "character_string12",
                ],
                [
                    "name" => "退款金额",
                    "field" => "pay_fee",
                    "template_field" => "amount4",
                ],
                [
                    "name" => "申请时间",
                    "field" => "apply_date",
                    "template_field" => "time3",
                ]
            ],
        ],
        'WechatMiniProgram' => [
            'category_id' => 670,
            'tid' => '1468',
            'kid' => [9,10],      // 退款金额， 退款原因
            'scene_desc' => '当有用户申请退款时，通知管理员',     // 申请模板场景描述
            'fields' => [
                // 'amount9' => 'pay_fee',                               // 退款金额
                // 'thing10' => 'reason',                               // 退款原因
                [
                    "name" => "退款金额",
                    "field" => "pay_fee",
                    "template_field" => "amount9",
                ],
                [
                    "name" => "退款原因",
                    "field" => "reason",
                    "template_field" => "thing10",
                ]
            ],
        ]
    ];

    // 返回的字段列表
    public $returnField = [
        'name' => '订单退款申请',
        'channels' => ['Sms', 'Email', 'WechatOfficialAccount'],
        'fields' => [
            ['name' => '消息名称', 'field' => 'template'],
            ['name' => '订单ID', 'field' => 'order_id'],
            ['name' => '订单号', 'field' => 'order_sn'],
            ['name' => '下单用户', 'field' => 'nickname'],
            ['name' => '用户手机', 'field' => 'mobile'],
            ['name' => '退款金额', 'field' => 'pay_fee'],
            ['name' => '下单时间', 'field' => 'create_date'],
            ['name' => '支付时间', 'field' => 'paid_date'],
            ['name' => '退款原因', 'field' => 'reason'],
            ['name' => '退款状态', 'field' => 'refund_status'],
            ['name' => '申请时间', 'field' => 'apply_date']
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
        $order = $this->data['order'];
        $ext = $order['ext'] ?? [];
        $user = $this->data['user'];
        $auto_refund = $this->data['auto_refund'];

        $data['template'] = $this->returnField['name'];           // 模板名称
        $data['order_id'] = $order['id'];
        $data['order_sn'] = $order['order_sn'];
        $data['nickname'] = $user['nickname'] ?? '';
        $data['mobile'] = $user['mobile'] ?? '';
        $data['pay_fee'] = '￥' . $order['pay_fee'];
        $data['create_date'] = $order['createtime'];
        $data['paid_date'] = $order['paid_time'];
        $data['reason'] = '订单申请全额退款' . ($auto_refund ? '，已自动退款，请及时查看' : '，请及时处理');
        $data['refund_status'] = $auto_refund ? '已自动退款，请及时查看' : '请及时处理';
        $data['apply_date'] = $ext['apply_refund_date'] ?? date('Y-m-d H:i:s');

        // 统一跳转地址
        $data['jump_url'] = "";
        return $data;
    }
}
