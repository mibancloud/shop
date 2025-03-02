<?php

namespace app\admin\model\shopro\order\traits;

use app\admin\model\shopro\order\OrderItem;

trait OrderItemStatus
{

    public function getStatus($data, $type)
    {
        $btns = [];
        $backendBtns = [];
        $status_text = '';
        $status_desc = '';
        $ext = $this->ext;
        $aftersale_id = (isset($ext['aftersale_id']) && !empty($ext['aftersale_id'])) ? $ext['aftersale_id'] : 0;

        $status_code = $this->status_code;

        $item_code = 'null';        // 有售后的时候，第二状态
        if (strpos($status_code, '|') !== false) {
            $codes = explode('|', $status_code);
            $status_code = $codes[0] ?? 'null';
            $item_code = $codes[1] ?? 'null';
        }

        switch ($status_code) {
            case 'null':
            case 'cancel':
            case 'closed':
            case 'unpaid':
                // 未支付的返回空
                break;
            case 'refuse':  // 拒收
                $status_text = '已拒收';
                $status_desc = '用户拒绝收货';
                break;
            case 'nosend':
                $status_text = '待发货';
                $status_desc = '等待卖家发货';
                $backendBtns[] = 'send';
                $backendBtns[] = 'refund';      // 退款
                // $btns[] = $aftersale_id ? 're_aftersale' : 'aftersale';          // 售后，售后取消会有 aftersale_id , 待发货商品，不用申请售后，直接用订单退款
                break;
            case 'noget':
                $status_text = '待收货';
                $status_desc = '等待买家收货';
                // $btns[] = 'get';            // 确认收货，总订单上才有
                $btns[] = $aftersale_id ? 're_aftersale' : 'aftersale';          // 售后，售后取消会有 aftersale_id
                $backendBtns[] = 'send_cancel'; // 取消发货
                $backendBtns[] = 'refund';
                break;
            case 'nocomment':
                $status_text = '待评价';
                $status_desc = '等待买家评价';
                $btns[] = 'comment';         // 评价，总订单上才有（前端通过这个判断待评价商品）
                $btns[] = $aftersale_id ? 're_aftersale' : 'aftersale';          // 售后，售后取消会有 aftersale_id
                $backendBtns[] = 'refund';      // 退款
                break;
            case 'commented':
                $status_text = '已评价';
                $status_desc = '订单已评价';
                $btns[] = 'buy_again';
                $backendBtns[] = 'refund';          // 退款
                $backendBtns[] = 'comment_view';          // 查看评价
                break;
            case 'refund_completed':
                $status_text = '退款完成';
                $status_desc = '订单退款完成';
                break;
            case 'refund_agree':      // 不需要申请退款（状态不会出现）
                $status_text = '退款完成';
                $status_desc = '卖家已同意退款';
                break;
            case 'aftersale_ing':
                $status_text = '售后中';
                $status_desc = '售后处理中';

                if ($item_code == 'noget') {
                    if (in_array($data['dispatch_type'], ['express'])) {       // 除了 自提扫码核销外，都可确认收货
                        $backendBtns[] = 'send_cancel';     // 取消发货
                    }
                } else if ($item_code == 'nocomment') {
                    // 售后中也可以评价订单
                    $btns[] = 'comment';         // 评价，总订单上才有（前端通过这个判断待评价商品）
                }
                break;
            case 'aftersale_refuse':
            case 'aftersale_completed':
                switch ($status_code) {
                    case 'aftersale_refuse':
                        $status_text = '售后拒绝';
                        $status_desc = '售后申请拒绝';
                        if ($item_code != 'commented') {
                            $btns[] = 're_aftersale';          // 售后
                        }
                        break;
                    case 'aftersale_completed':
                        $status_text = '售后完成';
                        $status_desc = '售后完成';
                        break;
                }

                // 售后拒绝，或者完成的时候，还可以继续操作订单
                switch ($item_code) {
                    case 'nosend':
                        if (in_array($data['dispatch_type'], ['express'])) {       // 除了 自提扫码核销外，都可确认收货
                            $backendBtns[] = 'send';            // 发货
                        }
                        break;
                    case 'noget':
                        if (in_array($data['dispatch_type'], ['express'])) {       // 除了 自提扫码核销外，都可确认收货
                            // $btns[] = 'get';            // 确认收货，总订单上才有
                            $backendBtns[] = 'send_cancel';     // 取消发货
                        }
                        break;
                    case 'nocomment':
                        $btns[] = 'comment';         // 评价，总订单上才有（前端通过这个判断待评价商品）
                        break;
                    case 'commented':
                        $btns[] = 'buy_again';
                        $backendBtns[] = 'comment_view';          // 查看评价
                        break;
                }

                break;
        }

        // 如果有售后id 就显示售后详情按钮，退款中可能是售后退的款
        if ($aftersale_id) {
            $btns[] = 'aftersale_info';
            $backendBtns[] = 'aftersale_info';
        }

        $return = null;
        switch($type) {
            case 'status_text':
                $return = $status_text;
                break;
            case 'btns':
                $return = $btns;
                break;
            case 'status_desc':
                $return = $status_desc;
                break;
            case 'backend_btns':
                $return = $backendBtns;
                break;
        }

        return $return;
    }



    public function getStatusTextAttr($value, $data)
    {
        return $this->getStatus($data, 'status_text');
    }

    public function getStatusDescAttr($value, $data)
    {
        return $this->getStatus($data, 'status_desc');
    }

    public function getBtnsAttr($value, $data)
    {
        $btn_name = strpos(request()->url(),  'addons/shopro') !== false ? 'btns' : 'backend_btns';
        
        return $this->getStatus($data, $btn_name);
    }


    // 获取订单 item status_code 状态，不进行订单是否支付判断，在这里查询数据库特别慢，
    // 需要处理情况，订单列表：要正确显示item 状态，直接获取 item 的状态
    public function getStatusCodeAttr($value, $data)
    {
        // $status_code = 'null';

        // $order = Order::withTrashed()->where('id', $data['order_id'])->find();
        // if (!$order) {
        //     return $status_code;
        // }

        // // 判断是否支付
        // if (!in_array($order->status, [Order::STATUS_PAYED, Order::STATUS_FINISH])) {
        //     return $order->status_code;
        // }

        // 获取 item status_code
        return $this->getBaseStatusCode($data);
    }


    /**
     * $data 当前 item 数据
     * $from 当前 model 调用，还是 order 调用
     */
    public function getBaseStatusCode($data, $from = 'item')
    {
        $status_code = 'null';

        if ($data['refund_status'] == OrderItem::REFUND_STATUS_AGREE) {
            // 退款已同意
            return 'refund_agree';
        }
        if ($data['refund_status'] == OrderItem::REFUND_STATUS_COMPLETED) {
            // 退款完成
            return 'refund_completed';
        }

        if ($data['aftersale_status']) {
            // 只申请了售后，没有退款
            // status_code
            $status_code = $this->getNormalStatusCode($data);

            // item 要原始状态，总订单还要原来的未退款状态
            if ($from == 'item') {
                switch ($data['aftersale_status']) {
                    case OrderItem::AFTERSALE_STATUS_REFUSE:
                        $status_code = 'aftersale_refuse' . '|' . $status_code;
                        break;
                    case OrderItem::AFTERSALE_STATUS_ING:
                        $status_code = 'aftersale_ing' . '|' . $status_code;
                        break;
                    case OrderItem::AFTERSALE_STATUS_COMPLETED:
                        $status_code = 'aftersale_completed' . '|' . $status_code;
                        break;
                }
            }
        } else {
            // status_code
            $status_code = $this->getNormalStatusCode($data);
        }

        return $status_code;
    }



    public function getNormalStatusCode($data)
    {
        // 获取未申请售后和退款时候的 status_code
        $status_code = 'null';

        switch ($data['dispatch_status']) {
            case OrderItem::DISPATCH_STATUS_REFUSE:
                $status_code = 'refuse';
                break;
            case OrderItem::DISPATCH_STATUS_NOSEND:
                $status_code = 'nosend';
                break;
            case OrderItem::DISPATCH_STATUS_SENDED:
                $status_code = 'noget';
                break;
            case OrderItem::DISPATCH_STATUS_GETED:
                if ($data['comment_status'] == OrderItem::COMMENT_STATUS_NO) {
                    $status_code = 'nocomment';
                } else {
                    $status_code = 'commented';
                }
                break;
        }

        return $status_code;        // status_code
    }
}
