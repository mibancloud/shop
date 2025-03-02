<?php

namespace app\admin\model\shopro\order\traits;

use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\activity\Groupon;
use app\admin\model\shopro\Config;


trait OrderStatus
{

    protected function getStatus($data, $type)
    {
        $btns = [];             // 前端订单操作按钮
        $backendBtns = [];
        $status_text = '';
        $status_desc = '';
        $ext = $this->ext;

        switch ($this->status_code) {
            case 'cancel':
                $status_text = '已取消';
                $status_desc = '买家已取消';
                $btns[] = 'delete';     // 删除订单
                break;
            case 'closed':
                $status_text = '交易关闭';
                if (isset($ext['closed_type']) && $ext['closed_type'] == 'refuse') {
                    $status_desc = '买家拒绝收货';    
                } else {
                    $status_desc = '买家未在规定时间内付款';
                }
                $btns[] = 'delete';     // 删除订单
                break;
            case 'unpaid':
                $status_text = '待付款';
                $status_desc = '等待买家付款';
                $btns[] = 'cancel';     // 取消订单
                $btns[] = 'pay';        // 支付
                $backendBtns[] = 'change_fee';        // 订单改价
                break;
                // 已支付的
            case 'apply_refund':
                $status_text = '申请退款中';
                $status_desc = '等待卖家处理退款';
                $backendBtns[] = 'apply_refund_oper';   // 处理申请退款按钮
                $backendBtns[] = 'refund';      // 只能退款，或者在列表上拒绝申请退款
                break;
            case 'commented':
                $status_text = '已评价';
                $status_desc = '订单已评价';

                $dispatchType = $this->getItemDispatchTypes();
                if (in_array('express', $dispatchType)) {
                    $btns[] = 'express';        // 查看物流
                }
                $backendBtns[] = 'refund';
                break;
            case 'nocomment':
                $status_text = '待评价';
                $status_desc = '等待买家评价';

                $dispatchType = $this->getItemDispatchTypes();
                if (in_array('express', $dispatchType)) {
                    $btns[] = 'express';        // 查看物流
                }
                $btns[] = 'comment';
                $backendBtns[] = 'refund';
                break;
            case 'noget':
                $status_text = '待收货';
                $status_desc = '等待买家收货';

                $dispatchType = $this->getItemDispatchTypes();
                if (in_array('express', $dispatchType)) {
                    $btns[] = 'express';        // 查看物流
                }

                if ($this->isOffline($data)) {
                    $status_desc = '卖家已发货，等待包裹运达';

                    // 用户可以拒收，后台确认收货
                    $btns[] = 'refuse';             // 用户拒收
                    $backendBtns[] = 'confirm';   
                }else {
                    // 计算自动确认收货时间
                    $send_time = $ext['send_time'] ?? 0;
                    $auto_confirm = Config::getConfigField('shop.order.auto_confirm');
                    $auto_confirm_unix = $auto_confirm * 86400;
                    if ($send_time && $auto_confirm_unix) {
                        $auto_confirm_time = $send_time + $auto_confirm_unix;
    
                        $status_desc .= '，还剩' . diff_in_time($auto_confirm_time, null, true, true) . '自动确认';
                    }

                    $btns[] = 'confirm';            // 确认收货
                    $backendBtns[] = 'refund';
                }

                break;
            case 'nosend':
                $status_text = '待发货';
                $status_desc = '等待卖家发货';
                $statusCodes = $this->getItemStatusCode();
                if (in_array('noget', $statusCodes)) {      // 只要存在待收货的item
                    $btns[] = 'confirm';        // 确认收货 (部分发货时也可以收货)
                }

                if ($this->isOffline($data)) {
                    // 发货之前用户可以取消
                    $btns[] = 'cancel';             // 用户取消订单
                } else {
                    $backendBtns[] = 'refund';
                }
                $backendBtns[] = 'send';
                if (!isset($ext['need_address']) || $ext['need_address']) {                 // 自动发货这些不需要收货地址的，没有 edit_consignee
                    $backendBtns[] = 'edit_consignee';      //修改收货地址
                }

                break;
            case 'refund_completed':
                $status_text = '退款完成';
                $status_desc = '订单退款完成';
                break;
            case 'refund_agree':
                $status_text = '退款完成';
                $status_desc = '订单退款完成';
                break;
            case 'groupon_ing':
                $status_text = '等待成团';
                $status_desc = '等待拼团成功';
                if ($this->isOffline($data)) {
                    // 货到付款未付款，不能退款，等待拼团时还未发货，用户可取消订单
                    $btns[] = 'cancel';             // 用户取消订单
                } else {
                    $backendBtns[] = 'refund';        // 全部退款  直接不申请退款
                }
                break;
            case 'groupon_invalid':
                $status_text = '拼团失败';
                $status_desc = '拼团失败';
                break;
                // 已支付的结束
            case 'completed':
                $status_text = '交易完成';
                $status_desc = '交易已完成';
                $btns[] = 'delete';     // 删除订单
                break;
        }

        // 有活动
        if (in_array($data['activity_type'], ['groupon', 'groupon_ladder', 'groupon_lucky'])) {
            // 是拼团订单
            if (isset($ext['groupon_id']) && $ext['groupon_id']) {
                $btns[] = 'groupon';    // 拼团详情
            }
        }

        if (in_array($this->status_code, ['nosend', 'groupon_ing']) && !$this->isOffline($data)) {      // 线下付款订单，不可申请全额退款
            if (in_array($data['apply_refund_status'], [Order::APPLY_REFUND_STATUS_NOAPPLY, Order::APPLY_REFUND_STATUS_REFUSE])) {
                // 获取所有的 item 状态
                $statusCodes = $this->getItemStatusCode();
                if (count($statusCodes) == 1 && current($statusCodes) == 'nosend') {
                    // items 只有 未发货，就显示 申请退款按钮    
                    if ($data['apply_refund_status'] == Order::APPLY_REFUND_STATUS_REFUSE) {
                        $btns[] = 're_apply_refund';    // 重新申请退款
                    } else {
                        $btns[] = 'apply_refund';    // 申请退款
                    }
                }
            }
        }
        
        if ($data['invoice_status'] == 1) {
            $btns[] = 'invoice';    // 查看发票
        }

        $return = null;
        switch ($type) {
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
                if (in_array('refund', $backendBtns)) {
                    // 判断是否有退款,如果存在退款就移除 refund 按钮
                    $refundCount = OrderItem::where('order_id', $data['id'])->where('refund_status', '<>', OrderItem::REFUND_STATUS_NOREFUND)->count();
                    if ($refundCount && ($key = array_search('refund', $backendBtns))) {
                        unset($backendBtns[$key]);
                    }

                    $backendBtns = array_values($backendBtns);
                }

                $return = $backendBtns;
                break;
        }

        return $return;
    }


    /**
     * 获取支付成功之后的子状态
     */
    public function getPayedStatusCode($data)
    {
        $status_code = '';

        // 获取所有的 item 状态
        $statusCodes = $this->getItemStatusCode();

        if (in_array('nosend', $statusCodes)) {
            // 存在待发货就是待发货
            $status_code = 'nosend';
        } else if (in_array('noget', $statusCodes)) {
            // 存在待收货，就是待收货
            $status_code = 'noget';
        } else if (in_array('nocomment', $statusCodes)) {
            // 存在待评价，就是待评价
            $status_code = 'nocomment';
        } else if (in_array('commented', $statusCodes)) {
            // 存在已评价，就是已评价
            $status_code = 'commented';
        } else if (in_array('refund_completed', $statusCodes)) {
            // 所有商品退款完成,或者退款中（不可能存在待发货或者收货的商品，上面已判断过）
            $status_code = 'refund_completed';
        } else if (in_array('refund_agree', $statusCodes)) {
            // 所有商品都同意退款了 （不可能存在待发货或者收货的商品，上面已判断过）
            $status_code = 'refund_agree';
        } // 售后都不在总状态显示

        if ($data['apply_refund_status'] == Order::APPLY_REFUND_STATUS_APPLY && !in_array($status_code, ['refund_completed', 'refund_agree'])) {
            return $status_code = 'apply_refund';       // 申请退款中,并且还没退款
        }

        $ext = $this->ext;
        // 是拼团订单
        if (
            in_array($data['activity_type'], ['groupon', 'groupon_ladder', 'groupon_lucky']) &&
            isset($ext['groupon_id']) && $ext['groupon_id']
        ) {
            $groupon = Groupon::where('id', $ext['groupon_id'])->find();
            if ($groupon) {
                if ($groupon['status'] == 'ing') {
                    // 尚未成团
                    $status_code = $statusCodes[0] ?? '';       // 拼团订单只能有一个商品
                    $status_code = in_array($status_code, ['refund_agree', 'refund_completed']) ? $status_code : 'groupon_ing';       // 如果订单已退款，则是退款状态，不显示拼团中
                } else if ($groupon['status'] == 'invalid') {
                    $status_code = 'groupon_invalid';
                }
            }
        }

        return $status_code;
    }


    /**
     * 获取订单items状态
     *
     * @param string $type
     * @return array
     */
    private function getItemStatusCode($type = 'order') 
    {
        // 循环判断 item 状态
        $itemStatusCode = [];
        foreach ($this->items as $key => $item) {
            // 获取 item status
            $itemStatusCode[] = (new OrderItem)->getBaseStatusCode($item, $type);
        }

        // 取出不重复不为空的 status_code
        $statusCodes = array_values(array_unique(array_filter($itemStatusCode)));

        return $statusCodes;
    }


    private function getItemDispatchTypes()
    {
        $dispatchType = [];
        foreach ($this->items as $key => $item) {
            // 获取 item status
            $dispatchType[] = $item['dispatch_type'];
        }
        $dispatchType = array_unique(array_filter($dispatchType));  // 过滤重复，过滤空值
        return $dispatchType;
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


    // 获取订单状态
    public function getStatusCodeAttr($value, $data)
    {
        $status_code = '';

        switch ($data['status']) {
            case Order::STATUS_CLOSED:
                $status_code = 'closed';        // 订单交易关闭
                break;
            case Order::STATUS_CANCEL:
                $status_code = 'cancel';        // 订单已取消
                break;
            case Order::STATUS_UNPAID:
                $status_code = 'unpaid';        // 订单等待支付
                break;
            case Order::STATUS_PENDING:
                $status_code = $this->getPayedStatusCode($data);        // 订单线下付款 
                break;
            case Order::STATUS_PAID:
                // 根据 item 获取支付后的状态信息
                $status_code = $this->getPayedStatusCode($data);
                break;
            case Order::STATUS_COMPLETED:
                $status_code = 'completed';
                break;
        }

        return $status_code;
    }


    /**
     * 处理未支付 item status_code 
     * 查询列表 item status_code 不关联订单表,使用这个方法进行处理
     */
    public function setOrderItemStatusByOrder($order)
    {
        $order = $order instanceof \think\Model ? $order->toArray() : $order;

        foreach ($order['items'] as $key => &$item) {
            if ((!in_array($order['status'], [Order::STATUS_PAID, Order::STATUS_COMPLETED]) && !$this->isOffline($order))       // 没有支付，并且也不是货到付款
                || $order['apply_refund_status'] == Order::APPLY_REFUND_STATUS_APPLY) {
                // 未支付，status_code = 订单的 status_code
                $item['status_code'] = $order['status_code'];
                $item['status_text'] = '';
                $item['status_desc'] = '';
                $item['btns'] = [];
            } else {
                if (strpos(request()->url(), 'addons/shopro') !== false) {
                    // 前端
                    if (strpos($item['status_code'], 'nosend') !== false) {
                        if (!$this->isOffline($order) && !array_intersect(['re_apply_refund', 'apply_refund'], $order['btns'])) {
                            // 不能申请全额退款了（有部分发货，或者退款）的待发货的 item 要显示申请售后的按钮 
                            $aftersale_id = (isset($item['ext']['aftersale_id']) && !empty($item['ext']['aftersale_id'])) ? $item['ext']['aftersale_id'] : 0;
        
                            if (strpos($item['status_code'], 'aftersale_ing') === false && strpos($item['status_code'], 'aftersale_completed') === false) {
                                // 不是售后中，也不是售后完成
                                if (strpos($item['status_code'], 'aftersale_refuse') !== false && $aftersale_id) {
                                    // 如果申请过退款，被拒绝了，则为重新申请售后
                                    $item['btns'][] = 're_aftersale';
                                } else {
                                    // 取消售后是 re_aftersale ,未申请过是 aftersale
                                    $item['btns'][] = $aftersale_id ? 're_aftersale' : 'aftersale';          // 售后，售后取消会有 aftersale_id
                                }
                            }
                        }
                    } else if (strpos($item['status_code'], 'noget') !== false) {
                        // 如果是货到付款的 待收货
                        if ($this->isOffline($order)) {
                            foreach($item['btns'] as $btnk => $btn) {
                                if (in_array($btn, ['re_aftersale', 'aftersale'])) {
                                    unset($item['btns'][$btnk]);
                                }
                            }
                        }
                    }
                } else {
                    // 后端，如果是货到付款， 并且还没收货，不显示退款按钮
                    if ($this->isOffline($order) && (strpos($item['status_code'], 'nosend') !== false || strpos($item['status_code'], 'noget') !== false)) {
                        $refund_key = array_search('refund', $item['btns']);
                        if ($refund_key !== false) {
                            unset($item['btns'][$refund_key]);
                        }
                    }
                }


            }
        }

        return $order;
    }
}
