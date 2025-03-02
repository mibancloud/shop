<?php

namespace addons\shopro\notification\order\aftersale;

/**
 * 售后结果通知
 */
class OrderAdminAftersaleChange extends OrderAftersaleChangeBase
{
    
    public $receiver_type = 'admin';      // 接收人:admin=管理员

    // 发送类型
    public $event = 'order_admin_aftersale_change';

    // 返回的字段列表
    public $returnField = [
        'name' => '售后结果通知',
        'channels' => ['Sms', 'Email', 'WechatOfficialAccount'],
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
        $data = parent::getData($notifiable);
        $data['jump_url'] = '';     // 管理员消息不予跳转
        return $data;
    }
}
