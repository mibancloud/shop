<?php

namespace addons\shopro\notification\activity;

use addons\shopro\notification\Notification;
use addons\shopro\notification\traits\Notification as NotificationTrait;
use app\admin\model\shopro\order\Order as OrderModel;

/**
 * 拼团完成
 */
class GrouponFinish extends Notification
{
    use NotificationTrait;

    // 队列延迟时间，必须继承 ShouldQueue 接口
    public $delay = 0;

    public $receiver_type = 'user';      // 接收人:user=用户

    // 消息类型  Notification::$notificationType
    public $notification_type = 'shop';

    // 发送类型
    public $event = 'groupon_finish';

    // 额外数据
    public $data = [];

    public $template = [
        'MessageDefaultContent' => '您的拼团已成功，商品：{goods_title}，成团人数：{groupon_num}，请注意查收',
        // 'WechatOfficialAccount' => [
        //     'temp_no' => 'OPENTM400932513',
        //     'fields' => [
        //         // 'first' => '您的拼团已成功',
        //         // 'keyword1' => 'goods_title',                               // 商品名称
        //         // 'keyword2' => 'groupon_leader_nickname',                   // 团长
        //         // 'keyword3' => 'groupon_num',                               // 成团人数
        //         // 'remark' => '您的拼团已成功，点击查看详情',
        //         [
        //             "template_field" => "first",
        //             "value" => '您的拼团已成功',
        //         ],
        //         [
        //             "name" => "商品名称",
        //             "field" => "goods_title",
        //             "template_field" => "keyword1",
        //         ],
        //         [
        //             "name" => "团长",
        //             "field" => "groupon_leader_nickname",
        //             "template_field" => "keyword2",
        //         ],
        //         [
        //             "name" => "成团人数",
        //             "field" => "groupon_num",
        //             "template_field" => "keyword3",
        //         ],
        //         [
        //             "template_field" => "remark",
        //             "value" => '您的拼团已成功，点击查看详情',
        //         ]
        //     ],
        // ],
        'WechatOfficialAccount' => [
            'temp_no' => false,         // 目前公众号类目模板库，找不到符合条件模板
            'keywords' => [],
            'fields' => [],
            // 'temp_no' => '44579',
            // 'keywords' => ['商品名称', '拼单时间'],
            // 'fields' => [
            //     [
            //         "name" => "商品名称",
            //         "field" => "goods_title",
            //         "template_field" => "thing2",
            //     ],
            //     [
            //         "name" => "拼单时间",
            //         "field" => "groupon_start_date",
            //         "template_field" => "time4",
            //     ]
            // ],
        ],
        'WechatMiniProgram' => [
            'category_id' => 670,
            'tid' => '3098',
            'kid' => [7,3,2,5],      // 商品名称,团长,成团人数,开团时间
            'scene_desc' => '当拼团成功时通知用户',     // 申请模板场景描述
            'fields' => [
                // 'thing7' => 'goods_title',                               // 商品名称
                // 'name3' => 'groupon_leader_nickname',                               // 团长
                // 'number2' => 'groupon_num',                               // 成团人数
                // 'date5' => 'groupon_start_date',                               // 开团时间
                [
                    "name" => "商品名称",
                    "field" => "goods_title",
                    "template_field" => "thing7",
                ],
                [
                    "name" => "团长",
                    "field" => "groupon_leader_nickname",
                    "template_field" => "name3",
                ],
                [
                    "name" => "成团人数",
                    "field" => "groupon_num",
                    "template_field" => "number2",
                ],
                [
                    "name" => "开团时间",
                    "field" => "groupon_start_date",
                    "template_field" => "date5",
                ],
            ],
        ]
    ];

    // 返回的字段列表
    public $returnField = [
        'name' => '拼团成功通知',
        'channels' => ['Sms', 'Email', 'WechatOfficialAccount', 'WechatMiniProgram'],
        'fields' => [
            ['name' => '消息名称', 'field' => 'template'],
            ['name' => '团ID', 'field' => 'groupon_id'],
            ['name' => '商品名称', 'field' => 'goods_title'],
            ['name' => '拼团用户', 'field' => 'groupon_user'],
            ['name' => '用户手机', 'field' => 'groupon_mobile'],
            ['name' => '团长', 'field' => 'groupon_leader_nickname'],
            ['name' => '团长手机', 'field' => 'groupon_leader_mobile'],
            ['name' => '商品金额', 'field' => 'groupon_price'],
            ['name' => '开团时间', 'field' => 'groupon_start_date'],
            ['name' => '成团时间', 'field' => 'groupon_finish_date'],
            ['name' => '成团人数', 'field' => 'groupon_num'],
            ['name' => '订单ID', 'field' => 'order_id'],
            ['name' => '订单号', 'field' => 'order_sn'],
            ['name' => '订单金额', 'field' => 'order_amount'],
            ['name' => '支付金额', 'field' => 'pay_fee'],
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
        $groupon = $this->data['groupon'];
        $grouponLogs = $this->data['groupon_logs'];
        $grouponLogs = $grouponLogs instanceof \think\Collection ? $grouponLogs : collection($grouponLogs);     // 转为 collection
        $grouponLeader = $this->data['groupon_leader'];
        $goods = $this->data['goods'];

        // 当前订单
        $order = $this->getCurrentOrder($notifiable, $groupon, $grouponLogs);
        $data['template'] = $this->returnField['name'];           // 模板名称
        $data['groupon_id'] = $groupon['id'];
        $data['goods_title'] = $goods['title'];
        $data['groupon_user'] = $notifiable['nickname'];
        $data['groupon_mobile'] = $notifiable['mobile'];
        $data['groupon_leader_nickname'] = $grouponLeader['nickname'] ?? '';
        $data['groupon_leader_mobile'] = $grouponLeader['mobile'] ?? '';
        $data['groupon_price'] = $order ? '￥' . $order['goods_amount'] : '';
        $data['groupon_start_date'] = $groupon['createtime'];
        $data['groupon_finish_date'] = $groupon['finish_time'];
        $data['groupon_num'] = $groupon['num'];
        $data['order_id'] = $order['id'] ?? '';
        $data['order_sn'] = $order['order_sn'] ?? '';
        $data['order_amount'] = '￥' . $order['order_amount'] ?? '';
        $data['pay_fee'] = '￥' . $order['pay_fee'] ?? '';
        
        // 统一跳转地址
        $data['jump_url'] = "/pages/activity/groupon/detail?id=" . $groupon['id'];

        return $data;
    }


    // 获取当前订单
    private function getCurrentOrder($notifiable, $groupon, $grouponLogs)
    {
        $grouponLogs = $grouponLogs->column(null, 'user_id');
        $currentLog = $grouponLogs[$notifiable['id']] ?? null;

        if ($currentLog) {
            $order = OrderModel::where('id', $currentLog['order_id'])->find();
        }

        return $order ?? null;
    }
}
