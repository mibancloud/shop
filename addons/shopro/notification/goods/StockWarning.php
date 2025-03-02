<?php

namespace addons\shopro\notification\goods;

use addons\shopro\notification\Notification;
use addons\shopro\notification\traits\Notification as NotificationTrait;

/**
 * 库存预警提醒
 */
class StockWarning extends Notification
{
    use NotificationTrait;

    // 队列延迟时间，必须继承 ShouldQueue 接口
    public $delay = 0;

    public $receiver_type = 'admin';      // 接收人:user=用户

    // 消息类型  Notification::$notificationType
    public $notification_type = 'shop';

    // 发送类型
    public $event = 'stock_warning';

    // 额外数据
    public $data = [];

    public $template = [
        'MessageDefaultContent' => '商品 {goods_name} 库存不足，请及时补货',
        // 'WechatOfficialAccount' => [
        //     'temp_no' => 'OPENTM415437052',
        //     'fields' => [
        //         // 'first' => '您的积分发生变动，请及时查看',
        //         // 'keyword1' => 'event_name',                               // 交易类型
        //         // 'keyword2' => 'amount',                               // 交易金额
        //         // 'keyword3' => 'create_date',                               // 交易时间
        //         // 'keyword4' => 'score',                               // 账户余额
        //         // 'remark' => '您的积分发生变动，请及时查看',
        //         [
        //             "template_field" => "first",
        //             "value" => '您的积分发生变动，请及时查看',
        //         ],
        //         [
        //             "name" => "交易类型",
        //             "field" => "event_name",
        //             "template_field" => "keyword1",
        //         ],
        //         [
        //             "name" => "交易金额",
        //             "field" => "amount",
        //             "template_field" => "keyword2",
        //         ],
        //         [
        //             "name" => "交易时间",
        //             "field" => "create_date",
        //             "template_field" => "keyword3",
        //         ],
        //         [
        //             "name" => "账户余额",
        //             "field" => "score",
        //             "template_field" => "keyword4",
        //         ],
        //         [
        //             "template_field" => "remark",
        //             "value" => '您的积分发生变动，请及时查看',
        //         ]
        //     ],
        // ],
        'WechatMiniProgram' => [
            'category_id' => 670,
            'tid' => '2105',
            'kid' => [1, 4],      // 商品名称,备注
            'scene_desc' => '当库存不足时通知管理员',     // 申请模板场景描述
            'fields' => [
                // 'thing1' => 'event_name',                               // 商品名称
                // 'thing4' => 'amount',                               // 备注
                [
                    "name" => "商品名称",
                    "field" => "goods_name",
                    "template_field" => "thing1",
                ],
                [
                    "name" => "备注",
                    "field" => "description",
                    "template_field" => "thing4",
                ],
            ],
        ]
    ];

    // 返回的字段列表
    public $returnField = [
        'name' => '商品补货通知',
        'channels' => ['Sms', 'Email'],
        'fields' => [
            ['name' => '消息名称', 'field' => 'template'],
            ['name' => '商品名称', 'field' => 'goods_name'],
            ['name' => '当前库存', 'field' => 'stock'],
            ['name' => '预警值', 'field' => 'stock_warning'],
            ['name' => '说明', 'field' => 'description'],
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
        $goods = $this->data['goods'];
        $goodsSkuPrice = $this->data['goodsSkuPrice'];
        $stock_warning = $this->data['stock_warning'];

        $data['template'] = $this->returnField['name'];           // 模板名称
        $data['goods_name'] = ($goodsSkuPrice['goods_sku_text'] ? join(',', $goodsSkuPrice['goods_sku_text']) . '-' : '') . ($goods ?  $goods['title'] : '');
        $data['stock'] = $goodsSkuPrice['stock'];
        $data['stock_warning'] = $stock_warning;
        $data['description'] = '当前商品剩余库存' . $goodsSkuPrice['stock'] . '，低于预警阈值' . $stock_warning . '，请及时补货';

        // 统一跳转地址(先不跳)
        // $data['jump_url'] = "/pages/order/detail?id=" . $goods['id'];

        return $data;
    }
}
