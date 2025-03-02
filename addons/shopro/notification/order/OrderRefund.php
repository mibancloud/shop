<?php

namespace addons\shopro\notification\order;

use addons\shopro\notification\Notification;
use addons\shopro\notification\traits\Notification as NotificationTrait;
use app\admin\model\shopro\Refund;

/**
 * 订单退款通知
 */
class OrderRefund extends Notification
{
    use NotificationTrait;

    // 队列延迟时间，必须继承 ShouldQueue 接口
    public $delay = 0;

    public $receiver_type = 'user';      // 接收人:user=用户

    // 消息类型  Notification::$notificationType
    public $notification_type = 'shop';

    // 发送类型
    public $event = 'order_refund';

    // 额外数据
    public $data = [];

    public $template = [
        'MessageDefaultContent' => '您的订单已退款，订单号：{order_sn}，退款金额：{refund_money}，请注意查收',
        // 'WechatOfficialAccount' => [
        //     'temp_no' => 'OPENTM413279802',
        //     'fields' => [
        //         // 'first' => '您的订单已退款，点击查看详情',
        //         // 'keyword1' => 'order_sn',                               // 订单编号
        //         // 'keyword2' => 'goods_title',                               // 商品名称
        //         // 'keyword3' => 'refund_money',                               // 退款金额
        //         // 'remark' => '您的订单已退款，点击查看详情',
        //         [
        //             "template_field" => "first",
        //             "value" => '您的订单已退款，点击查看详情',
        //         ],
        //         [
        //             "name" => "订单编号",
        //             "field" => "order_sn",
        //             "template_field" => "keyword1",
        //         ],
        //         [
        //             "name" => "商品名称",
        //             "field" => "goods_title",
        //             "template_field" => "keyword2",
        //         ],
        //         [
        //             "name" => "退款金额",
        //             "field" => "refund_money",
        //             "template_field" => "keyword3",
        //         ],
        //         [
        //             "template_field" => "remark",
        //             "value" => '您的订单已退款，点击查看详情',
        //         ]
        //     ],
        // ],
        'WechatOfficialAccount' => [
            'temp_no' => '45762',
            'keywords' => ['订单编号', '商品名称', '退款金额'],
            'fields' => [
                [
                    "name" => "订单编号",
                    "field" => "order_sn",
                    "template_field" => "character_string10",
                ],
                [
                    "name" => "商品名称",
                    "field" => "goods_title",
                    "template_field" => "thing8",
                ],
                [
                    "name" => "退款金额",
                    "field" => "refund_money",
                    "template_field" => "amount2",
                ]
            ],
        ],
        'WechatMiniProgram' => [
            'category_id' => 670,
            'tid' => '1451',
            'kid' => [7,10,3],      // 订单编号,商品名称,退款金额
            'scene_desc' => '当订单发生退款时通知用户',     // 申请模板场景描述
            'fields' => [
                // 'character_string7' => 'order_sn',                               // 订单编号
                // 'thing10' => 'goods_title',                               // 商品名称
                // 'amount3' => 'refund_money',                               // 退款金额
                [
                    "name" => "订单编号",
                    "field" => "order_sn",
                    "template_field" => "character_string7",
                ],
                [
                    "name" => "商品名称",
                    "field" => "goods_title",
                    "template_field" => "thing10",
                ],
                [
                    "name" => "退款金额",
                    "field" => "refund_money",
                    "template_field" => "amount3",
                ],
            ],
        ]
    ];

    // 返回的字段列表
    public $returnField = [
        'name' => '订单退款成功通知',
        'channels' => ['Sms', 'Email', 'WechatOfficialAccount', 'WechatMiniProgram'],
        'fields' => [
            ['name' => '消息名称', 'field' => 'template'],
            ['name' => '订单ID', 'field' => 'order_id'],
            ['name' => '订单号', 'field' => 'order_sn'],
            ['name' => '订单金额', 'field' => 'order_amount'],
            ['name' => '用户昵称', 'field' => 'nickname'],
            ['name' => '用户手机', 'field' => 'mobile'],
            ['name' => '商品名称', 'field' => 'goods_title'],
            ['name' => '商品金额', 'field' => 'goods_amount'],
            ['name' => '退款金额', 'field' => 'refund_money'],
            ['name' => '退款类型', 'field' => 'refund_type'],
            ['name' => '退款时间', 'field' => 'refund_date']
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
        $items = $this->data['items'];
        $refund_method = $this->data['refund_method'];
        $refund_type = $this->data['refund_type'];

        $data['template'] = $this->returnField['name'];           // 模板名称
        $data['order_id'] = $order['id'];
        $data['order_sn'] = $order['order_sn'];
        $data['order_amount'] = '￥' . $order['order_amount'];
        $data['nickname'] = $notifiable['nickname'];
        $data['mobile'] = $notifiable['mobile'];

        if ($refund_method == 'full_refund') {
            // 全额退款
            $refund_money = $order['pay_fee'];
            $goods_title = '订单全部商品';
            $goods_amount = $order['goods_amount'];
        } else {
            $item = $items[0];      // 只有一个商品
            $refund_money = $item['refund_fee'];
            $goods_title = $item['goods_title'];
            $goods_amount = ($item['goods_price'] * $item['goods_num']);
        }

        $refundTypeList = (new Refund)->refundTypeList();
        $data['goods_title'] = $goods_title;
        $data['goods_amount'] = '￥' . $goods_amount;
        $data['refund_money'] = '￥' . $refund_money;
        $data['refund_type'] = $refundTypeList[$refund_type] ?? '';
        $data['refund_date'] = $order['updatetime'];

        // 统一跳转地址
        $data['jump_url'] = "/pages/order/detail?id=" . $order['id'];

        return $data;
    }
}
