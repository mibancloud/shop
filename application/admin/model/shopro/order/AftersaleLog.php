<?php

namespace app\admin\model\shopro\order;

use app\admin\model\shopro\Common;
use app\admin\model\Admin;
use app\admin\model\shopro\user\User;

class AftersaleLog extends Common
{
    protected $name = 'shopro_order_aftersale_log';

    protected $type = [
        'images' => 'json',
    ];

    protected $append = [
        'log_type_text'
    ];

    public function logTypeList()
    {
        return [
            'apply_aftersale' => '售后服务单申请成功，等待售后处理',
            'cancel' => '用户取消申请售后',
            'delete' => '用户删除售后单',
            'completed' => '售后订单已完成',
            'refuse' => '卖家拒绝售后',
            'refund' => '卖家同意退款',
            'add_log' => '卖家留言'
        ];
    }




    public static function add($order = null, $aftersale = null, $oper = null, $type = 'user', $data = [])
    {
        $oper_id = $oper ? $oper['id'] : 0;
        $images = $data['images'] ?? [];

        $self = new self();
        $self->order_id = $order ? $order->id : ($aftersale ? $aftersale->id : 0);
        $self->order_aftersale_id = $aftersale ? $aftersale->id : 0;
        $self->oper_type = $type;
        $self->oper_id = $oper_id;
        $self->dispatch_status = $aftersale ? $aftersale->dispatch_status : 0;
        $self->aftersale_status = $aftersale ? $aftersale->aftersale_status : 0;
        $self->refund_status = $aftersale ? $aftersale->refund_status : 0;
        $self->log_type = $data['log_type'];
        $self->content = $data['content'] ?? '';
        $self->images = $images;
        $self->save();

        // 售后单变动行为
        $data = ['aftersale' => $aftersale, 'order' => $order, 'aftersaleLog' => $self];
        \think\Hook::listen('order_aftersale_change', $data);

        return $self;
    }


    /**
     * log 类型获取器
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getLogTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['log_type'] ?? null);

        $list = $this->logTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

}
