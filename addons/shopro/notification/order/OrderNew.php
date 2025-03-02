<?php

namespace addons\shopro\notification\order;

use addons\shopro\notification\Notification;
use addons\shopro\notification\traits\Notification as NotificationTrait;
use app\admin\model\shopro\order\Address;

/**
 * 新订单通知
 */
class OrderNew extends Notification
{
    use NotificationTrait;

    // 队列延迟时间，必须继承 ShouldQueue 接口
    public $delay = 0;

    public $receiver_type = 'admin';      // 接收人:user=用户

    // 消息类型  Notification::$notificationType
    public $notification_type = 'shop';

    // 发送类型
    public $event = 'order_new';

    // 额外数据
    public $data = [];

    public $template = [
        'MessageDefaultContent' => '您有新的待处理订单，下单用户：{nickname}，订单号：{order_sn}，订单金额：{order_amount}，请及时处理',
        // 'WechatOfficialAccount' => [
        //     'temp_no' => 'OPENTM417875155',
        //     'fields' => [
        //         // 'first' => '您有新的订单待处理',
        //         // 'keyword1' => 'order_sn',                               // 订单号
        //         // 'keyword2' => 'pay_fee',                               // 实付金额
        //         // 'keyword3' => 'create_date',                               // 下单时间
        //         // 'remark' => '您有新的订单待处理，请及时处理',
        //         [
        //             "template_field" => "first",
        //             "value" => '您有新的订单待处理',
        //         ],
        //         [
        //             "name" => "订单号",
        //             "field" => "order_sn",
        //             "template_field" => "keyword1",
        //         ],
        //         [
        //             "name" => "实付金额",
        //             "field" => "pay_fee",
        //             "template_field" => "keyword2",
        //         ],
        //         [
        //             "name" => "下单时间",
        //             "field" => "create_date",
        //             "template_field" => "keyword3",
        //         ],
        //         [
        //             "template_field" => "remark",
        //             "value" => '您有新的订单待处理，请及时处理',
        //         ]
        //     ],
        // ],
        'WechatOfficialAccount' => [
            'temp_no' => '46624',
            'keywords' => ['订单号', '订单金额', '下单时间'],
            'fields' => [
                [
                    "name" => "订单号",
                    "field" => "order_sn",
                    "template_field" => "character_string1",
                ],
                [
                    "name" => "订单金额",
                    "field" => "order_amount",
                    "template_field" => "amount3",
                ],
                [
                    "name" => "下单时间",
                    "field" => "create_date",
                    "template_field" => "time9",
                ]
            ],
        ],
        'WechatMiniProgram' => [
            'category_id' => 670,
            'tid' => '1476',
            'kid' => [4,12,6],      // 订单编号,实付金额,订单时间
            'scene_desc' => '当有新订单时通知管理员',     // 申请模板场景描述
            'fields' => [
                // 'character_string4' => 'order_sn',                               // 订单编号
                // 'amount12' => 'pay_fee',                               // 实付金额
                // 'date6' => 'create_date',                               // 订单时间
                [
                    "name" => "订单编号",
                    "field" => "order_sn",
                    "template_field" => "character_string4",
                ],
                [
                    "name" => "实付金额",
                    "field" => "pay_fee",
                    "template_field" => "amount12",
                ],
                [
                    "name" => "订单时间",
                    "field" => "create_date",
                    "template_field" => "date6",
                ],
            ],
        ]
    ];

    // 返回的字段列表
    public $returnField = [
        'name' => '新订单通知',
        'channels' => ['Sms', 'Email', 'WechatOfficialAccount'],
        'fields' => [
            ['name' => '消息名称', 'field' => 'template'],
            ['name' => '订单ID', 'field' => 'order_id'],
            ['name' => '订单号', 'field' => 'order_sn'],
            ['name' => '订单金额', 'field' => 'order_amount'],
            ['name' => '下单用户', 'field' => 'nickname'],
            ['name' => '用户手机', 'field' => 'mobile'],
            ['name' => '支付金额', 'field' => 'pay_fee'],
            ['name' => '收货人', 'field' => 'consignee'],
            ['name' => '收货地址', 'field' => 'address'],
            ['name' => '下单时间', 'field' => 'create_date'],
            ['name' => '支付时间', 'field' => 'paid_date']
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
        $user = $this->data['user'];
        $address = Address::where('order_id', $order['id'])->find();
        if ($address) {
            $consignee = $address['consignee'] . '-' . $address['mobile'];
            $address_info = $address['province_name'] . '/' . $address['city_name'] . '/' . $address['district_name'] . '/' . $address['address'];
        }

        $data['template'] = $this->returnField['name'];           // 模板名称
        $data['order_id'] = $order['id'];
        $data['order_sn'] = $order['order_sn'];
        $data['order_amount'] = '￥' . $order['order_amount'];
        $data['nickname'] = $user['nickname'] ?? '';
        $data['mobile'] = $user['mobile'] ?? '';
        $data['pay_fee'] = '￥' . $order['pay_fee'];
        $data['consignee'] = $consignee ?? '';
        $data['address'] = $address_info ?? '';
        $data['create_date'] = $order['createtime'];
        $data['paid_date'] = $order['paid_time'];

        // 统一跳转地址(先不跳)
        // $data['jump_url'] = "/pages/order/detail?id=" . $order['id'];
        return $data;
    }
}
