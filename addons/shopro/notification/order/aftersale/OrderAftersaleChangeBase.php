<?php

namespace addons\shopro\notification\order\aftersale;

use addons\shopro\notification\Notification;
use addons\shopro\notification\traits\Notification as NotificationTrait;

/**
 * 售后结果通知
 */
class OrderAftersaleChangeBase extends Notification
{
    use NotificationTrait;

    // 队列延迟时间，必须继承 ShouldQueue 接口
    public $delay = 0;

    // 消息类型  Notification::$notificationType
    public $notification_type = 'shop';

    // 发送类型
    public $event = 'order_aftersale_change';

    // 额外数据
    public $data = [];

    public $template = [
        'MessageDefaultContent' => '您的售后申请有新的变化，售后单号：{aftersale_sn}，变动内容：{content}，请及时处理',
        // 'WechatOfficialAccount' => [
        //     'temp_no' => 'OPENTM417005336',
        //     'fields' => [
        //         // 'first' => '您的售后申请有新的变化',
        //         // 'keyword1' => 'aftersale_sn',                               // 售后编号
        //         // 'keyword2' => 'goods_title',                               // 商品名称
        //         // 'keyword3' => 'aftersale_status_text',                     // 状态
        //         // 'keyword4' => 'oper_date',                               // 时间
        //         // 'remark' => '您的售后申请有新的变化，点击立即处理',
        //         [
        //             "template_field" => "first",
        //             "value" => '您的售后申请有新的变化',
        //         ],
        //         [
        //             "name" => "售后编号",
        //             "field" => "aftersale_sn",
        //             "template_field" => "keyword1",
        //         ],
        //         [
        //             "name" => "商品名称",
        //             "field" => "goods_title",
        //             "template_field" => "keyword2",
        //         ],
        //         [
        //             "name" => "状态",
        //             "field" => "aftersale_status_text",
        //             "template_field" => "keyword3",
        //         ],
        //         [
        //             "name" => "时间",
        //             "field" => "oper_date",
        //             "template_field" => "keyword4",
        //         ],
        //         [
        //             "template_field" => "remark",
        //             "value" => '您的售后申请有新的变化，点击立即处理',
        //         ]
        //     ],
        // ],
        'WechatOfficialAccount' => [
            'temp_no' => '46232',
            'keywords' => ['订单编号', '退款商品', '退款金额', '处理结果'],
            'fields' => [
                [
                    "name" => "订单编号",
                    "field" => "order_sn",
                    "template_field" => "character_string8",
                ],
                [
                    "name" => "退款商品",
                    "field" => "goods_title",
                    "template_field" => "thing4",
                ],
                [
                    "name" => "退款金额",
                    "field" => "refund_fee",
                    "template_field" => "amount5",
                ],
                [
                    "name" => "处理结果",
                    "field" => "aftersale_status_text",
                    "template_field" => "thing6",
                ]
            ],
        ],
        'WechatMiniProgram' => [
            'category_id' => 670,
            'tid' => '5334',
            'kid' => [2,9,7,1,3],      // 售后单号,订单编号,商品名称
            'scene_desc' => '当售后状态改变时通知用户',     // 申请模板场景描述
            'fields' => [
                // 'character_string2' => 'aftersale_sn',                               // 售后单号
                // 'character_string9' => 'order_sn',                               // 订单编号
                // 'thing7' => 'goods_title',                               // 商品名称
                // 'phrase1' => 'aftersale_status_text',                               // 售后状态
                // 'date3' => 'apply_date',                               // 申请时间
                [
                    "name" => "售后单号",
                    "field" => "aftersale_sn",
                    "template_field" => "character_string2",
                ],
                [
                    "name" => "订单编号",
                    "field" => "order_sn",
                    "template_field" => "character_string9",
                ],
                [
                    "name" => "商品名称",
                    "field" => "goods_title",
                    "template_field" => "thing7",
                ],
                [
                    "name" => "售后状态",
                    "field" => "aftersale_status_text",
                    "template_field" => "phrase1",
                ],
                [
                    "name" => "申请时间",
                    "field" => "apply_date",
                    "template_field" => "date3",
                ],
            ],
        ]
    ];

    // 返回的字段列表
    public $returnField = [
        'name' => '售后结果通知',
        'channels' => ['Sms', 'Email', 'WechatOfficialAccount', 'WechatMiniProgram'],
        'fields' => [
            ['name' => '消息名称', 'field' => 'template'],
            ['name' => '售后单ID', 'field' => 'aftersale_id'],
            ['name' => '售后单号', 'field' => 'aftersale_sn'],
            ['name' => '申请时间', 'field' => 'apply_date'],
            ['name' => '订单ID', 'field' => 'order_id'],
            ['name' => '订单号', 'field' => 'order_sn'],
            ['name' => '订单金额', 'field' => 'order_amount'],
            ['name' => '下单时间', 'field' => 'create_date'],
            ['name' => '申请用户', 'field' => 'nickname'],
            ['name' => '用户手机', 'field' => 'mobile'],
            ['name' => '支付金额', 'field' => 'pay_fee'],
            ['name' => '售后类型', 'field' => 'aftersale_type'],
            ['name' => '联系电话', 'field' => 'aftersale_mobile'],
            ['name' => '商品名称', 'field' => 'goods_title'],
            ['name' => '商品规格', 'field' => 'goods_sku_text'],
            ['name' => '商品原价', 'field' => 'goods_original_price'],
            ['name' => '商品价格', 'field' => 'goods_price'],
            ['name' => '购买数量', 'field' => 'goods_num'],
            ['name' => '优惠金额', 'field' => 'discount_fee'],
            ['name' => '售后状态', 'field' => 'aftersale_status_text'],
            ['name' => '退款状态', 'field' => 'refund_status_text'],
            ['name' => '退款金额', 'field' => 'refund_fee'],
            ['name' => '变动内容', 'field' => 'content'],
            ['name' => '处理时间', 'field' => 'oper_date'],
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
        $aftersale = $this->data['aftersale'];
        $order = $this->data['order'];
        $aftersaleLog = $this->data['aftersaleLog'];

        $data['template'] = $this->returnField['name'];           // 模板名称
        $data['aftersale_id'] = $aftersale['id'];
        $data['aftersale_sn'] = $aftersale['aftersale_sn'];
        $data['apply_date'] = $aftersale['createtime'];
        $data['order_id'] = $order['id'];
        $data['order_sn'] = $order['order_sn'];
        $data['order_amount'] = '￥' . $order['order_amount'];
        $data['create_date'] = $order['createtime'];
        $data['nickname'] = $notifiable['nickname'];
        $data['mobile'] = $notifiable['mobile'];
        $data['pay_fee'] = '￥' . $order['pay_fee'];
        $data['aftersale_type'] = $aftersale['type_text'];
        $data['aftersale_mobile'] = $aftersale['mobile'];
        $data['goods_title'] = $aftersale['goods_title'];
        $data['goods_sku_text'] = $aftersale['goods_sku_text'];
        $data['goods_original_price'] = '￥' . $aftersale['goods_original_price'];
        $data['goods_price'] = '￥' . $aftersale['goods_price'];
        $data['goods_num'] = $aftersale['goods_num'];
        $data['discount_fee'] = '￥' . $aftersale['discount_fee'];
        $data['aftersale_status_text'] = $aftersale['aftersale_status_text'];
        $data['refund_status_text'] = $aftersale['refund_status_text'];
        if ($aftersale['refund_fee']) {
            $data['refund_fee'] = '￥' . $aftersale['refund_fee'];
        } else {
            $data['refund_fee'] = '暂未退款';
        }
        $data['content'] = $aftersaleLog['content'] ? strip_tags($aftersaleLog['content']) : '-';
        $data['oper_date'] = $aftersaleLog['createtime'];

        // 统一跳转地址
        $data['jump_url'] = "/pages/order/aftersale/detail?id=" . $aftersale['id'];

        return $data;
    }
}
