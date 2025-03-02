<?php

namespace addons\shopro\service\order;

use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\order\Action as OrderAction;
use app\admin\model\shopro\order\Express as OrderExpress;
use app\admin\model\shopro\activity\Groupon as ActivityGroupon;
use app\admin\model\shopro\activity\GrouponLog as ActivityGrouponLog;
use app\admin\model\shopro\dispatch\Dispatch as DispatchModel;
use app\admin\model\shopro\dispatch\DispatchAutosend;
use addons\shopro\library\express\Express as ExpressLib;



class OrderDispatch
{

    public $order = null;

    public function __construct($params = [])
    {
        if (isset($params['order_id']) && !empty($params['order_id'])) {

            $this->order = Order::find($params['order_id']);
            if (!$this->order) {
                error_stop('订单不存在');
            }
        }
    }


    /**
     * 执行发货
     *
     * @return object
     */
    public function confirm($params)
    {
        $admin = auth_admin();
        $method = $params['method'] ?? '';
        if (!in_array($method, ['input', 'api', 'upload'])) {
            error_stop('请使用正确的发货方式');
        }
        if ($this->order->status !== Order::STATUS_PAID && !$this->order->isOffline($this->order)) {
            error_stop("该订单{$this->order->status_text},不能发货");
        }

        if ($this->order->apply_refund_status === Order::APPLY_REFUND_STATUS_APPLY) {
            error_stop("该订单已申请退款,暂不能发货");
        }

        switch ($method) {
            case 'api':
                list($orderItems, $express) = $this->doByApi($params);
                break;
            case 'input':
                list($orderItems, $express) = $this->doByInput($params);
                break;
            case 'upload':
                list($orderItems, $express) = $this->doByUpload($params);
                break;
        }

        // 添加包裹信息
        $orderExpress = OrderExpress::create([
            'user_id' => $this->order->user_id,
            'order_id' => $this->order->id,
            'express_name' => $express['name'],
            'express_code' => $express['code'],
            'express_no' => $express['no'],
            'method' => $method,
            'driver' => $express['driver'] ?? null,
            'ext' => $express['ext'] ?? null
        ]);

        // 修改订单商品发货状态
        foreach ($orderItems as $orderItem) {
            $orderItem->order_express_id = $orderExpress->id;
            $orderItem->dispatch_status = OrderItem::DISPATCH_STATUS_SENDED;
            $orderItem->ext = array_merge($orderItem->ext, ['send_time' => time()]);    // item 发货时间
            $orderItem->save();
            OrderAction::add($this->order, $orderItem, $admin, 'admin', "商品{$orderItem->goods_title}已发货");
        }
        $this->subscribeExpressInfo($orderExpress);
        // 订单发货后
        $data = [
            'order' => $this->order,
            'items' => $orderItems,
            'express' => $orderExpress,
            'dispatch_type' => 'express',
        ];
        \think\Hook::listen('order_dispatch_after', $data);
        return $express;
    }

    // 手动发货
    private function doByInput($params)
    {
        $orderItems = $this->getDispatchOrderItems($params, 'express');

        $express = $params['express'] ?? null;
        if (empty($express['name']) || empty($express['code']) || empty($express['no'])) {
            error_stop('请输入正确的快递信息');
        }
        return [$orderItems, $express];
    }

    // API发货
    private function doByApi($params)
    {
        $orderItems = $this->getDispatchOrderItems($params, 'express');
        $sender = $params['sender'] ?? null;
        $expressLib = new ExpressLib();

        $data = [
            'order' => $this->order,
            'sender' => $sender,
            'consignee' => $this->order->address
        ];
        $express = $expressLib->eOrder($data, $orderItems);
        return [$orderItems, $express];
    }

    // 上传发货模板发货 TODO: 如果发货单比较多，循环更新可能会比较慢，考虑解析完模版信息以后，把数据返回前端，再次执行批量发货流程
    private function doByUpload($params)
    {
        $orderItems = $this->getDispatchOrderItems($params, 'express');

        $express = $params['express'] ?? null;
        if (empty($express['name']) || empty($express['code']) || empty($express['no'])) {
            error_stop('请输入正确的快递信息');
        }
        return [$orderItems, $express];
    }

    // 获取可发货的订单商品
    public function getDispatchOrderItems($params = null, $dispatch_type = 'express')
    {
        $orderItemIds = $params['order_item_ids'] ?? [];
        $whereCanDispatch['order_id'] = $this->order->id;
        $whereCanDispatch['dispatch_status'] = OrderItem::DISPATCH_STATUS_NOSEND;
        $whereCanDispatch['aftersale_status'] = ['<>', OrderItem::AFTERSALE_STATUS_ING];
        $whereCanDispatch['refund_status'] = OrderItem::REFUND_STATUS_NOREFUND;
        $whereCanDispatch['dispatch_type'] = $dispatch_type;
        
        if (empty($orderItemIds)) {
            $orderItems = OrderItem::where($whereCanDispatch)->select();
        } else {
            $orderItems = OrderItem::where('id', 'in', $orderItemIds)->where($whereCanDispatch)->select();

            if (count($orderItems) !== count($orderItemIds)) {
                error_stop('选中商品暂不能发货');
            }
        }

        if (!$orderItems) {
            error_stop('该订单无可发货商品');
        }
        return $orderItems;
    }



    /**
     * 取消发货
     *
     */
    public function cancel($params)
    {
        $admin = auth_user();

        $order_express_id = $params['order_express_id'] ?? 0;
        $orderExpress = OrderExpress::where('id', $order_express_id)->find();
        if (!$orderExpress) {
            error_stop('未找到发货单');
        }
        // 1.检测是不是用api发的 有些快递不支持取消接口 所以不判断了，统一手动取消
        // if ($orderExpress->method === 'api') {
        //     // TODO: 走取消运单接口
        //     $expressLib = new ExpressLib();

        //     $data = [
        //         'express_no' => $orderExpress['express_no'],
        //         'express_code' => $orderExpress['express_code'],
        //         'order_code' => $orderExpress['ext']['Order']['OrderCode']
        //     ];

        //     $express = $expressLib->cancel($data);
        // }
        // 2. 变更发货状态
        $orderItems = OrderItem::where([
            'order_id' => $this->order->id,
            'order_express_id' => $orderExpress->id
        ])->where('dispatch_type', 'express')->select();


        foreach ($orderItems as $orderItem) {
            $orderItem->order_express_id = 0;
            $orderItem->dispatch_status = OrderItem::DISPATCH_STATUS_NOSEND;
            $orderItem->save();
            OrderAction::add($this->order, null, $admin, 'admin', "已取消发货");
        }
        // 删除发货单
        $orderExpress->delete();
        return true;
    }

    /**
     * 修改发货信息
     *
     */
    public function change($params)
    {
        $admin = auth_user();

        $order_express_id = $params['order_express_id'] ?? 0;

        $orderExpress = OrderExpress::where('id', $order_express_id)->find();
        if (!$orderExpress) {
            error_stop('未找到发货单');
        }
        // 1.1 检测是不是用api发的 如果是则提醒取消运单再重新走发货流程 此时什么都不用做
        if ($orderExpress->method === 'api') {
            error_stop('该发货单已被推送第三方平台，请取消后重新发货');
        }
        // 1.2 如果不是则手动变更运单信息（快递公司、运单号）
        $express = $params['express'] ?? null;
        if (empty($express['name']) || empty($express['code']) || empty($express['no'])) {
            error_stop('请输入正确的快递信息');
        }

        $orderExpress->save([
            'express_name' => $express['name'],
            'express_code' => $express['code'],
            'express_no' => $express['no'],
            'method' => 'input'
        ]);

        OrderAction::add($this->order, null, $admin, 'admin', "变更发货信息");
        $this->subscribeExpressInfo($orderExpress);
        // 修改发货信息
        $data = [
            'order' => $this->order,
            'express' => $orderExpress,
            'dispatch_type' => 'express',
        ];
        \think\Hook::listen('order_dispatch_change', $data);
        return $express;
    }

    // 解析批量发货信息,筛选出能发货的订单
    public function multiple($params)  
    {
        // 上传发货模板
        if (!empty($params['file'])) {
            $express = $params['express'];
            $file = $params['file']->getPathname();
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $PHPExcel = $reader->load($file);
            $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
            $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
            if ($allRow <= 2) {
                error_stop('您的发货列表为空');
            }

            $orderExpressMap = [];
            $orderId = 0;
            $orderSn = "";
            for ($currentRow = 2; $currentRow <= $allRow - 1; $currentRow++) {

                $orderId = $currentSheet->getCellByColumnAndRow(1, $currentRow)->getValue() ?? $orderId;
                $orderSn = $currentSheet->getCellByColumnAndRow(2, $currentRow)->getValue() ?? $orderSn;

                $orderItemId = $currentSheet->getCellByColumnAndRow(8, $currentRow)->getValue();
                if (empty($orderItemId)) {
                    error_stop("发货单格式不正确");
                }
                $orderExpressNo = $currentSheet->getCellByColumnAndRow(15, $currentRow)->getValue();
                if (empty($orderExpressNo)) {
                    error_stop("请填写 订单ID-{$orderId} 的运单号");
                }
                $orderExpressMap["$orderId.$orderSn"][$orderExpressNo][] = $orderItemId;
            }

            $list = [];
            foreach ($orderExpressMap as $orderFlag => $orderExpress) {
                foreach ($orderExpress as $expressNo => $orderItemIds) {
                    $order = explode('.', $orderFlag);
                    $list[] = [
                        'order_id' => $order[0],
                        'order_sn' => $order[1],
                        'order_item_ids' => $orderItemIds,
                        'express' => [
                            'name' => $express['name'],
                            'code' => $express['code'],
                            'no' => $expressNo
                        ]
                    ];
                }
            }
            return $list;
        } else {
            $list = [];
            $orderIds = $params['order_ids'] ?? [];
            if (empty($orderIds)) {
                error_stop('请选择发货订单');
            }
            foreach ($orderIds as $orderId) {
                $list[] = [
                    'order_id' => $orderId,
                    'order_sn' => Order::where('id', $orderId)->value('order_sn'),
                    'order_item_ids' => $this->getDispatchOrderItemIds($orderId)
                ];
            }
            return $list;
        }
    }

    // 获取可发货的订单商品
    private function getDispatchOrderItemIds($orderId)
    {
        $whereCanDispatch = [
            'order_id' => $orderId,
            'dispatch_status' => OrderItem::DISPATCH_STATUS_NOSEND,
            'aftersale_status' => ['neq', OrderItem::AFTERSALE_STATUS_ING],
            'refund_status' => OrderItem::REFUND_STATUS_NOREFUND,
            'dispatch_type' => 'express'
        ];

        $orderItems = OrderItem::where($whereCanDispatch)->column('id');

        return $orderItems;
    }

    // 订阅物流追踪
    private function subscribeExpressInfo($orderExpress)
    {
        try {
            $expressLib = new ExpressLib();

            $a = $expressLib->subscribe([
                'express_code' => $orderExpress['express_code'],
                'express_no' => $orderExpress['express_no']
            ]);
        } catch (\Exception $e) {
            // Nothing TODO
            return;
        }
    }



    /**
     * 手动发货
     *
     * @param array $params
     * @return void
     */
    public function customDispatch($params)
    {
        $admin = auth_admin();

        $custom_type = $params['custom_type'] ?? 'text';
        $custom_content = $params['custom_content'] ?? ($custom_type == 'text' ? '' : []);

        if ($this->order->status !== Order::STATUS_PAID && !$this->order->isOffline($this->order)) {
            error_stop("该订单{$this->order->status_text},不能发货");
        }

        if ($this->order->apply_refund_status === Order::APPLY_REFUND_STATUS_APPLY) {
            error_stop("该订单已申请退款,暂不能发货");
        }

        // 获取手动发货的 items
        $orderItems = $this->getDispatchOrderItems($params, 'custom');

        $customExt = [      // 手动发货信息
            'dispatch_content_type' => $custom_type,
            'dispatch_content' => $custom_content 
        ];

        // 修改订单商品发货状态
        foreach ($orderItems as $orderItem) {
            $orderItem->dispatch_status = OrderItem::DISPATCH_STATUS_SENDED;
            $orderItem->ext = array_merge($orderItem->ext, $customExt, ['send_time' => time()]);    // item 发货时间
            $orderItem->save();
            OrderAction::add($this->order, $orderItem, $admin, 'admin', "商品{$orderItem->goods_title}已发货");
        }
        // 订单发货后
        $data = [
            'order' => $this->order,
            'items' => $orderItems,
            'express' => null,
            'dispatch_type' => 'custom',
        ];
        \think\Hook::listen('order_dispatch_after', $data);
    }


    /**
     * 拼团完成时触发检测自动发货
     *
     * @return bool
     */
    public function grouponCheckDispatchAndSend()
    {
        $this->systemCheckAutoSend();

        return true;
    }


    /**
     * 普通商品自动发货
     *
     * @return bool
     */
    public function checkDispatchAndSend()
    {
        // 拼团不自动发货，等成团完成才发货
        $orderExt = $this->order['ext'];
        $buy_type = ($orderExt && isset($orderExt['buy_type'])) ? $orderExt['buy_type'] : '';
        if ($this->order['activity_type'] && strpos($this->order['activity_type'], 'groupon') !== false && $buy_type == 'groupon') {
            return true;        // 这里不对拼团的订单进行自动发货，等拼团成功在检测
        }

        // 检测需要自动发货的 item
        $this->systemCheckAutoSend();

        return true;
    }



    /**
     * 系统检测自动发货
     */
    private function systemCheckAutoSend()
    {
        $autosendItems = [];

        // 判断订单是否有需要发货的商品，并进行自动发货（autosend）
        foreach ($this->order->items as $key => $item) {
            // 判断不是未发货状态，或者退款完成，continue
            if (
                $item['dispatch_status'] == OrderItem::DISPATCH_STATUS_NOSEND
                && $item['aftersale_status'] != OrderItem::AFTERSALE_STATUS_ING
                && $item['refund_status'] == OrderItem::REFUND_STATUS_NOREFUND
            ) {
                // 订单可以发货
                switch ($item['dispatch_type']) {
                    case 'autosend':
                        // 自动发货
                        $autosendItems[] = $item;
                    }
            }
        }
        
        if ($autosendItems) {
            $this->autoSendItems($autosendItems, ['oper_type' => 'system']);
        }
    }


    /**
     * 当前订单需要自动发货的所有商品
     *
     * @param object|array $items
     * @return void
     */
    private function autoSendItems($items)
    {
        foreach ($items as $item) {
            $autosendExt = $this->getAutosendContent($item);

            $item->dispatch_status = OrderItem::DISPATCH_STATUS_SENDED;
            $item->ext = array_merge($item->ext, $autosendExt, ['send_time' => time()]);    // item 发货时间
            $item->save();
            OrderAction::add($this->order, $item, null, 'system', "商品{$item->goods_title}已发货");
        }

        $data = [
            'order' => $this->order,
            'items' => $items,
            'express' => null,
            'dispatch_type' => 'custom',
        ];

        // 发货后事件，消息通知
        \think\Hook::listen('order_dispatch_after', $data);
    }


    /**
     * 获取商品的自动发货模板数据
     *
     * @param object|array $item
     * @return array
     */
    private function getAutosendContent($item) 
    {
        // 获取配送模板
        $result = [];

        $dispatch = DispatchModel::with([$item['dispatch_type']])->show()->where('type', $item['dispatch_type'])->where('id', $item['dispatch_id'])->find();
        if ($dispatch && $dispatch->autosend) {
            $autosend = $dispatch->autosend;
            if (in_array($autosend['type'], ['text', 'params'])) {
                $result['dispatch_content_type'] = $autosend['type'];
                $result['dispatch_content'] = $autosend['content'];
            }
        }

        return $result;
    }
}
