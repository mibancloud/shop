<?php

namespace addons\shopro\notification\order;

use addons\shopro\notification\Notification;
use addons\shopro\notification\traits\Notification as NotificationTrait;
use app\admin\model\shopro\order\Address as OrderAddressModel;

/**
 * 订单通知
 */
class OrderDispatched extends Notification
{
    use NotificationTrait;

    // 队列延迟时间，必须继承 ShouldQueue 接口
    public $delay = 0;

    public $receiver_type = 'user';      // 接收人:user=用户

    // 消息类型  Notification::$notificationType
    public $notification_type = 'shop';

    // 发送类型
    public $event = 'order_dispatched';

    // 额外数据
    public $data = [];

    public $template = [
        'MessageDefaultContent' => '您的订单已发货，物流公司：{express_name}，运单号：{express_no}，请注意查收',
        // 'WechatOfficialAccount' => [
        //     'temp_no' => 'OPENTM418033200',
        //     'fields' => [
        //         // 'first' => '您的订单已发货，请注意查收',
        //         // 'keyword1' => 'order_sn',                               // 订单编号
        //         // 'keyword2' => 'dispatch_date',                               // 发货时间
        //         // 'keyword3' => 'express_name',                               // 物流公司
        //         // 'keyword4' => 'express_no',                               // 快递单号
        //         // 'remark' => '',
        //         [
        //             "template_field" => "first",
        //             "value" => '您的订单已发货，请注意查收',
        //         ],
        //         [
        //             "name" => "订单编号",
        //             "field" => "order_sn",
        //             "template_field" => "keyword1",
        //         ],
        //         [
        //             "name" => "发货时间",
        //             "field" => "dispatch_date",
        //             "template_field" => "keyword2",
        //         ],
        //         [
        //             "name" => "物流公司",
        //             "field" => "express_name",
        //             "template_field" => "keyword3",
        //         ],
        //         [
        //             "name" => "快递单号",
        //             "field" => "express_no",
        //             "template_field" => "keyword4",
        //         ],
        //         [
        //             "template_field" => "remark",
        //             "value" => '您的订单已发货，请注意查收',
        //         ]
        //     ],
        // ],
        'WechatOfficialAccount' => [
            'temp_no' => '42984',
            'keywords' => ['订单编号', '发货时间', '快递公司', '快递单号'],
            'fields' => [
                [
                    "name" => "订单编号",
                    "field" => "order_sn",
                    "template_field" => "character_string2",
                ],
                [
                    "name" => "发货时间",
                    "field" => "dispatch_date",
                    "template_field" => "time12",
                ],
                [
                    "name" => "快递公司",
                    "field" => "express_name",
                    "template_field" => "thing13",
                ],
                [
                    "name" => "快递单号",
                    "field" => "express_no",
                    "template_field" => "character_string14",
                ]
            ],
        ],
        'WechatMiniProgram' => [
            'category_id' => 670,
            'tid' => '1458',
            'kid' => [7,3,1,2],      // 订单编号,发货事件,快递公司,快递单号
            'scene_desc' => '当订单发货时通知用户',     // 申请模板场景描述
            'fields' => [
                // 'character_string7' => 'order_sn',                               // 订单编号
                // 'time3' => 'dispatch_date',                                     // 发货时间
                // 'thing1' => 'express_name',                                     // 物流公司
                // 'character_string2' => 'express_no',                               // 快递单号
                [
                    "name" => "订单编号",
                    "field" => "order_sn",
                    "template_field" => "character_string7",
                ],
                [
                    "name" => "发货时间",
                    "field" => "dispatch_date",
                    "template_field" => "time3",
                ],
                [
                    "name" => "物流公司",
                    "field" => "express_name",
                    "template_field" => "thing1",
                ],
                [
                    "name" => "快递单号",
                    "field" => "express_no",
                    "template_field" => "character_string2",
                ],
            ],
        ]
    ];

    // 返回的字段列表
    public $returnField = [
        'name' => '订单发货通知',
        'channels' => ['Sms', 'Email', 'WechatOfficialAccount', 'WechatMiniProgram'],
        'fields' => [
            ['name' => '消息名称', 'field' => 'template'],
            ['name' => '订单ID', 'field' => 'order_id'],
            ['name' => '订单号', 'field' => 'order_sn'],
            ['name' => '订单金额', 'field' => 'order_amount'],
            ['name' => '发货时间', 'field' => 'dispatch_date'],
            ['name' => '商品名称', 'field' => 'goods_title'],
            ['name' => '购买数量', 'field' => 'goods_num'],
            ['name' => '快递公司', 'field' => 'express_name'],
            ['name' => '快递单号', 'field' => 'express_no'],
            ['name' => '收件信息', 'field' => 'consignee'],
        ]
    ];




    /**
     * 组合数据参数
     *
     * @param \think\Model $notifiable
     * @param \think\Model $order
     * @param \think\Collection $items
     * @param \think\Model $express
     * @return array
     */
    protected function getData($notifiable)
    {
        $order = $this->data['order'];
        $items = collection($this->data['items']);
        $express = $this->data['express'] ?? null;

        $data['template'] = $this->returnField['name'];           // 模板名称
        $data['order_id'] = $order['id'];
        $data['order_sn'] = $order['order_sn'];
        $data['order_amount'] = '￥' . $order['order_amount'];
        $data['dispatch_date'] = ($order['ext'] && isset($order['ext']['send_time'])) ?
            date('Y-m-d H:i:s', $order['ext']['send_time']) : date('Y-m-d H:i:s');

        $goods_titles = $items->column('goods_title');
        $goods_nums = $items->column('goods_num');
        $data['goods_title'] = join(',', $goods_titles);
        $data['goods_num'] = '共' . array_sum($goods_nums) . '件商品';
        $data['express_name'] = $express ? $express['express_name'] : '-';
        $data['express_no'] = $express ? $express['express_no'] : '-';

        $address = OrderAddressModel::where('order_id', $order['id'])->find();
        $data['consignee'] = $address ? ($address['consignee'] . '-' . $address['mobile']) : '';

        // 统一跳转地址
        $data['jump_url'] = "/pages/order/detail?id=" . $order['id'];

        return $data;
    }
}
