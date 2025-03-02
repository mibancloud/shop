<?php

namespace app\admin\controller\shopro\order;

use think\Db;
use think\exception\HttpResponseException;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\order\Order as OrderModel;
use app\admin\model\shopro\order\Address as OrderAddressModel;
use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\Admin;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\order\Action as OrderActionModel;
use app\admin\model\shopro\order\Express as OrderExpressModel;
use addons\shopro\service\pay\PayOper;
use addons\shopro\service\order\OrderOper;
use addons\shopro\service\order\OrderRefund;
use app\admin\model\shopro\Pay as PayModel;
use addons\shopro\service\order\OrderDispatch as OrderDispatchService;
use addons\shopro\library\express\Express as ExpressLib;
use addons\shopro\library\Operator;
use addons\shopro\facade\Wechat;

class Order extends Common
{

    protected $noNeedRight = ['getType', 'dispatchList'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new OrderModel;
    }

    /**
     * 订单列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            if (sheep_config('shop.platform.WechatMiniProgram.status')) {       // 如果开启了小程序平台
                // 设置小程序发货信息管理，消息通知跳转地址，有缓存， 不会每次都设置
                $uploadshoppingInfo = new \addons\shopro\library\easywechatPlus\WechatMiniProgramShop(Wechat::miniProgram());
                $uploadshoppingInfo->checkAndSetMessageJumpPath();
            }

            $exportConfig = (new \addons\shopro\library\Export())->getConfig();
            $this->assignconfig("save_type", $exportConfig['save_type'] ?? 'download');
            return $this->view->fetch();
        }

        $orders = $this->model->withTrashed()->sheepFilter()->with(['user', 'items', 'address', 'activity_orders'])
            ->paginate(request()->param('list_rows', 10))->each(function ($order) {
                $order->pay_types = $order->pay_types;
                $order->pay_types_text = $order->pay_types_text;
                $order->items = collection($order->items);
                $order->items->each(function ($item) use ($order) {
                    // 处理每个商品的 activity_order
                    $item->activity_orders = new \think\Collection;
                    foreach ($order->activity_orders as $activityOrder) {
                        if ($activityOrder->goods_ids && in_array($item->goods_id, $activityOrder->goods_ids)) {
                            $item->activity_orders->push($activityOrder);
                        }
                    }

                    return $item;
                });
            })->toArray();

        foreach ($orders['data'] as &$order) {
            $order = $this->model->setOrderItemStatusByOrder($order);
        }

        $result = [
            'orders' => $orders,
        ];

        // 查询各个状态下的订单数量
        $searchStatus = $this->model->searchStatusList();
        // 所有的数量
        $result['all'] = $this->model->withTrashed()->sheepFilter(true, function ($filters) {
            unset($filters['status']);
            return $filters;
        })->count();
        foreach ($searchStatus as $status => $text) {
            $result[$status] = $this->model->withTrashed()->sheepFilter(true, function ($filters) use ($status) {
                $filters['status'] = $status;
                return $filters;
            })->count();
        }

        $this->success('获取成功', null, $result);
    }


    // 获取数据类型
    public function getType()
    {
        $type = $this->model->typeList();
        $payType = (new PayModel)->payTypeList();
        $platform = $this->model->platformList();
        $classify = (new \app\admin\model\shopro\activity\Activity)->classifies();
        $activityType = $classify['activity'];
        $promoType = $classify['promo'];
        $applyRefundStatus = $this->model->applyRefundStatusList();
        $status = $this->model->searchStatusList();

        $result = [
            'type' => $type,
            'pay_type' => $payType,
            'platform' => $platform,
            'activity_type' => $activityType,
            'promo_types' => $promoType,
            'apply_refund_status' => $applyRefundStatus,
            'status' => $status
        ];

        $data = [];
        foreach ($result as $key => $list) {
            $data[$key][] = ['name' => '全部', 'type' => 'all'];

            foreach ($list as $k => $v) {
                $data[$key][] = [
                    'name' => $v,
                    'type' => $k
                ];
            }
        }

        $this->success('获取成功', null, $data);
    }





    /**
     * 订单详情
     *
     * @param  $id
     * @return \think\Response
     */
    public function detail($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        // 更新包裹信息（5分钟缓存）
        (new ExpressLib)->updateOrderExpress($id);

        $order = $this->model->withTrashed()->with(['user', 'items', 'address', 'activity_orders', 'pays', 'invoice'])->where('id', $id)->find();
        if (!$order) {
            $this->error(__('No Results were found'));
        }
        $order->express = $order->express;
        if ($order->invoice) {
            $order->invoice->order_status = $order->invoice->order_status;
            $order->invoice->order_status_text = $order->invoice->order_status_text;
            $order->invoice->order_fee = $order->invoice->order_fee;
        }

        foreach ($order->activity_orders as $activityOrder) {
            // 处理每个活动中参与的商品
            $activityOrder->items = new \think\Collection();
            foreach ($order->items as $item) {
                if ($activityOrder->goods_ids && in_array($item->goods_id, $activityOrder->goods_ids)) {
                    $activityOrder->items->push($item);
                }
            }
        }

        foreach ($order->items as $item) {
            // 处理 order_item 建议退款金额
            $item->suggest_refund_fee = $item->suggest_refund_fee;
        }

        // 处理未支付订单 item status_code
        $order = $order->setOrderItemStatusByOrder($order);

        $this->success('获取成功', null, $order);
    }


    /**
     * 批量发货渲染模板
     *
     * @return void
     */
    public function batchDispatch()
    {
        return $this->view->fetch();
    }


    /**
     * 批量发货渲染模板
     *
     * @return void
     */
    public function dispatchList()
    {
        return $this->view->fetch();
    }



    /**
     * 手动发货
     *
     * @return void
     */
    public function customDispatch()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only(['order_id', 'order_item_ids', 'custom_type', 'custom_content']);
        $this->svalidate($params, '.custom_dispatch');

        Db::transaction(function () use ($params) {
            $service = new OrderDispatchService($params);
            $service->customDispatch($params);
        });

        $this->success('发货成功');
    }


    /**
     * 发货
     * 
     * @description 
     * 支持分包裹发货
     * 支持手动发货
     * 支持上传发货单发货
     * 支持推送api运单发货 默认使用配置项
     * 支持修改发货信息
     * 支持取消发货
     * 
     * @remark 此处接口设计如此复杂是因为考虑到权限的问题，订单发货权限可以完成所有发货行为
     * 
     * @param  array    $action     发货行为（默认:confirm=确认发货, cancel=取消发货, change=修改运单, multiple=解析批量发货单）
     * @param  int      $order_id   订单id
     * @param  array    $order_item_ids   订单商品id
     * @param  string   $method     发货方式（input=手动发货, api=推送运单, upload=上传发货单）
     * @param  array    $sender     发货人信息
     * @param  array    $express    物流信息
     * 
     * @return \think\Response
     */
    public function dispatch()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only(['action', 'order_id', 'order_ids', 'order_item_ids', 'method', 'sender', 'express', 'order_express_id']);

        $action = $params['action'] ?? 'confirm';
        if (!in_array($action, ['confirm', 'cancel', 'change', 'multiple'])) {
            $this->error('发货参数错误');
        }

        $service = new OrderDispatchService($params);
        switch ($action) {
            case 'confirm':
                $express = $service->confirm($params);
                $this->success('发货成功', null, $express);
                break;
            case 'cancel':
                $result = $service->cancel($params);
                if ($result) {
                    $this->success('取消发货成功');
                }
                break;
            case 'change':
                $express = $service->change($params);
                $this->success('修改成功', null, $express);
                break;
            case 'multiple':
                $params['file'] = $this->request->file('file');
                $result = $service->multiple($params);
                $this->success('待发货列表', null, $result);
                break;
        }

        $this->error('操作失败');
    }


    /**
     * 获取物流快递信息
     */
    public function updateExpress($order_express_id = 0)
    {
        $type = $this->request->param('type');

        // 获取包裹
        $orderExpress = OrderExpressModel::where('id', $order_express_id)->find();
        if (!$orderExpress) {
            $this->error('包裹不存在');
        }

        $expressLib = new ExpressLib();

        try {
            if ($type == 'subscribe') {
                // 重新订阅
                $expressLib->subscribe([
                    'express_code' => $orderExpress['express_code'],
                    'express_no' => $orderExpress['express_no']
                ]);
            } else {
                // 手动查询
                $result = $expressLib->search([
                    'order_id' => $orderExpress['order_id'],
                    'express_code' => $orderExpress['express_code'],
                    'express_no' => $orderExpress['express_no']
                ], $orderExpress);
            }
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            $this->error($message);
        } catch (\Exception $e) {
            $this->error(($type == 'subscribe' ? '订阅失败:' : '刷新失败:') . $e->getMessage());
        }

        $this->success(($type == 'subscribe' ? '订阅成功' : '刷新成功'));
    }


    /**
     * 线下付款，确认收货
     */
    public function offlineConfirm($id)
    {
        $admin = $this->auth->getUserInfo();

        $order = OrderModel::offline()->where('id', $id)->find();
        if (!$order) {
            $this->error(__('No Results were found'));
        }

        $order = Db::transaction(function () use ($order, $admin) {
            // 加锁读订单
            $order = $order->lock(true)->find($order->id);
            $user = User::get($order->user_id);

            $payOper = new PayOper($user);
            $order = $payOper->offline($order, $order->remain_pay_fee, 'order');        // 订单会变为已支付 paid

            // 触发订单支付完成事件
            $data = ['order' => $order, 'user' => $user];
            \think\Hook::listen('order_offline_paid_after', $data);

            $orderOper = new OrderOper();
            // 确认收货
            $order = $orderOper->confirm($order, [], $admin, 'admin');

            return $order;
        });

        $this->success('收货成功', $order);
    }



    /**
     * 线下付款，拒收
     */
    public function offlineRefuse($id)
    {
        $admin = $this->auth->getUserInfo();

        $order = OrderModel::offline()->where('id', $id)->find();
        if (!$order) {
            $this->error(__('No Results were found'));
        }

        $order = Db::transaction(function () use ($order, $admin) {
            // 加锁读订单
            $order = $order->lock(true)->find($order->id);

            // 拒收
            $orderOper = new OrderOper();
            $order = $orderOper->refuse($order, $admin, 'admin');

            // 交易关闭
            $order = $orderOper->close($order, $admin, 'admin', '用户拒绝收货，管理员关闭订单', ['closed_type' => 'refuse']);
            return $order;
        });

        $this->success('收货成功', $order);
    }


    /**
     * 订单改价，当剩余应支付金额为 0 时，订单将自动支付
     *
     * @param int $id
     * @return void
     */
    public function changeFee($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $admin = $this->auth->getUserInfo();

        $params = $this->request->param();
        $this->svalidate($params, '.change_fee');

        $order = $this->model->unpaid()->where('id', $id)->find();
        if (!$order) {
            $this->error('订单不可改价');
        }

        Db::transaction(function () use ($order, $admin, $params) {
            $pay_fee = $params['pay_fee'];
            $change_msg = $params['change_msg'];

            $payOper = new PayOper($order->user_id);
            $payed_fee = $payOper->getPayedFee($order, 'order');

            if ($pay_fee < $payed_fee) {
                $this->error('改价金额不能低于订单已支付金额');
            }

            // 记录原始值
            $last_pay_fee = $order->pay_fee;     // 上次pay_fee,非原始 pay_fee
            $order->pay_fee = $pay_fee;
            $order->save();

            OrderActionModel::add($order, null, $admin, 'admin', "应支付金额由 ￥" . $last_pay_fee . " 改为 ￥" . $pay_fee . "，改价原因：" . $change_msg);

            // 检查订单支付状态, 改价可以直接将订单变为已支付
            $order = $payOper->checkAndPaid($order, 'order');
        });

        $this->success('改价成功');
    }



    /**
     * 修改收货人信息
     *
     * @param Request $request
     * @param integer $id
     * @return void
     */
    public function editConsignee($id)
    {
        $admin = $this->auth->getUserInfo();

        $params = $this->request->param();
        $this->svalidate($params, '.edit_consignee');

        $order = $this->model->withTrashed()->where('id', $id)->find();
        if (!$order) {
            $this->error(__('No Results were found'));
        }

        Db::transaction(function () use ($admin, $order, $params) {
            $orderAddress = OrderAddressModel::where('order_id', $order->id)->find();
            if (!$orderAddress) {
                $this->error(__('No Results were found'));
            }
            $orderAddress->consignee = $params['consignee'];
            $orderAddress->mobile = $params['mobile'];
            $orderAddress->province_name = $params['province_name'];
            $orderAddress->city_name = $params['city_name'];
            $orderAddress->district_name = $params['district_name'];
            $orderAddress->address = $params['address'];
            $orderAddress->province_id = $params['province_id'];
            $orderAddress->city_id = $params['city_id'];
            $orderAddress->district_id = $params['district_id'];
            $orderAddress->save();

            OrderActionModel::add($order, null, $admin, 'admin', "修改订单收货人信息");
        });

        $this->success('收货人信息修改成功');
    }



    /**
     * 编辑商家备注
     *
     * @param Request $request
     * @param integer $id
     * @return void
     */
    public function editMemo($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $admin = $this->auth->getUserInfo();

        $params = $this->request->param();
        $this->svalidate($params, '.edit_memo');

        $order = $this->model->withTrashed()->where('id', $id)->find();
        if (!$order) {
            $this->error(__('No Results were found'));
        }
        Db::transaction(function () use ($admin, $order, $params) {
            $order->memo = $params['memo'];
            $order->save();

            OrderActionModel::add($order, null, $admin, 'admin', "修改卖家备注：" . $params['memo']);
        });

        $this->success('卖家备注修改成功');
    }


    /**
     * 拒绝用户全额退款申请
     *
     * @param Request $request
     * @param integer $id
     * @return void
     */
    public function applyRefundRefuse($id)
    {
        $admin = $this->auth->getUserInfo();

        $params = $this->request->param();
        // $this->svalidate($params, '.apply_refund_refuse');

        $order = $this->model->withTrashed()->paid()->applyRefundIng()->where('id', $id)->find();
        if (!$order) {
            $this->error('订单未找到或不可拒绝申请');
        }
        Db::transaction(function () use ($admin, $order, $params) {
            $order->apply_refund_status = OrderModel::APPLY_REFUND_STATUS_REFUSE;
            $order->save();

            OrderActionModel::add($order, null, $admin, 'admin', "拒绝用户申请全额退款");
        });

        $this->success('拒绝申请成功');
    }


    /**
     * 全额退款 (必须没有进行过任何退款才能使用)
     *
     * @param Request $request
     * @param integer $id
     * @return void
     */
    public function fullRefund($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $admin = $this->auth->getUserInfo();
        $admin = Admin::where('id', $admin['id'])->find();

        $params = $this->request->param();
        $refund_type = $params['refund_type'] ?? 'back';

        Db::transaction(function () use ($admin, $id, $refund_type) {
            $order = $this->model->paid()->where('id', $id)->lock(true)->find();
            if (!$order) {
                $this->error('订单不存在或不可退款');
            }

            $orderRefund = new OrderRefund($order);
            $orderRefund->fullRefund($admin, [
                'refund_type' => $refund_type,
                'remark' => '平台主动全额退款'
            ]);
        });

        $this->success('全额退款成功');
    }



    /**
     * 订单单商品退款
     *
     * @param Request $request
     * @param integer $id
     * @param integer $item_id
     * @return void
     */
    public function refund($id, $item_id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $admin = $this->auth->getUserInfo();

        $params = $this->request->param();
        $this->svalidate($params, '.refund');

        $refund_money = round(floatval($params['refund_money']), 2);
        $refund_type = $params['refund_type'] ?? 'back';
        if ($refund_money <= 0) {
            $this->error('请输入正确的退款金额');
        }

        $order = $this->model->paid()->where('id', $id)->find();
        if (!$order) {
            $this->error('订单不存在或不可退款');
        }

        $item = OrderItem::where('order_id', $order->id)->where('id', $item_id)->find();
        if (!$item) {
            $this->error(__('No Results were found'));
        }

        if (in_array($item['refund_status'], [
            OrderItem::REFUND_STATUS_AGREE,
            OrderItem::REFUND_STATUS_COMPLETED,
        ])) {
            $this->error('订单商品已退款，不能重复退款');
        }

        $payOper = new PayOper($order->user_id);
        // 获取订单最大可退款金额（不含积分抵扣金额）
        $remain_max_refund_money = $payOper->getRemainRefundMoney($order->id);

        // 如果退款金额大于订单支付总金额
        if (bccomp((string)$refund_money, $remain_max_refund_money, 2) === 1) {
            $this->error('退款总金额不能大于实际支付金额');
        }

        Db::transaction(function () use ($admin, $order, $item, $refund_money, $refund_type) {
            // 重新锁定读查询 orderItem
            $item = OrderItem::where('order_id', $order->id)->lock(true)->where('id', $item->id)->find();
            if (!$item) {
                $this->error('订单不存在或不可退款');
            }

            $orderRefund = new OrderRefund($order);
            $orderRefund->refund($item, $refund_money, $admin, [
                'refund_type' => $refund_type,
                'remark' => '平台主动退款'
            ]);
        });

        $this->success('退款成功');
    }


    /**
     * 获取订单操作记录
     *
     * @param integer $id
     * @return void
     */
    public function action($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $actions = OrderActionModel::where('order_id', $id)->order('id', 'desc')->select();

        $morphs = [
            'user' => \app\admin\model\shopro\user\User::class,
            'admin' => \app\admin\model\Admin::class,
            'system' => \app\admin\model\Admin::class,
        ];
        $actions = morph_to($actions, $morphs, ['oper_type', 'oper_id']);

        foreach ($actions as &$action) {
            $action['oper'] = Operator::info($action['oper_type'], $action['oper'] ?? null);
        }

        $this->success('获取成功', null, $actions);
    }




    public function export()
    {
        $cellTitles = [
            // 订单表字段
            'order_id' => 'Id',
            'order_sn' => '订单号',
            'type_text' => '订单类型',
            'user_nickname' => '下单用户',
            'user_mobile' => '手机号',
            'status_text' => '订单状态',
            'pay_text' => '支付状态',
            'pay_types_text' => '支付类型',
            'remark' => '用户备注',
            'memo' => '卖家备注',
            'order_amount' => '订单总金额',
            'score_amount' => '积分支付数量',
            'dispatch_amount' => '运费',
            'pay_fee' => '应付总金额',
            'real_pay_fee' => '实付总金额',
            'remain_pay_fee' => '剩余支付金额',
            'total_discount_fee' => '总优惠金额',
            'coupon_discount_fee' => '优惠券金额',
            'promo_discount_fee' => '营销优惠金额',
            'paid_time' => '支付完成时间',
            'platform_text' => '交易平台',
            'consignee_info' => '收货信息',
            'createtime' => '下单时间',

            // 订单商品表字段
            'activity_type_text' => '活动',
            'promo_types' => '促销',
            'goods_title' => '商品名称',
            'goods_sku_text' => '商品规格',
            'goods_num' => '购买数量',
            'goods_original_price' => '商品原价',
            'goods_price' => '商品价格',
            'goods_weight' => '商品重量',
            'discount_fee' => '优惠金额',
            'goods_pay_fee' => '商品支付金额',
            'dispatch_type_text' => '发货方式',
            'dispatch_status_text' => '发货状态',
            'aftersale_refund' => '售后/退款',
            'comment_status_text' => '评价状态',
            'refund_fee' => '退款金额',
            'refund_msg' => '退款原因',
            'express_name' => '快递公司',
            'express_no' => '快递单号',
        ];

        // 数据总条数
        $total = $this->model->withTrashed()->sheepFilter()->count();
        if ($total <= 0) {
            $this->error('导出数据为空');
        }

        $export = new \addons\shopro\library\Export();
        $params = [
            'file_name' => '订单列表',
            'cell_titles' => $cellTitles,
            'total' => $total,
            'is_sub_cell' => true,
            'sub_start_cell' => 'activity_type_text',
            'sub_field' => 'items'
        ];

        $total_order_amount = 0;
        $total_pay_fee = 0;
        $total_real_pay_fee = 0;
        $total_discount_fee = 0;
        $total_score_amount = 0;
        $result = $export->export($params, function ($pages) use (&$total_order_amount, &$total_pay_fee, &$total_real_pay_fee, &$total_discount_fee, &$total_score_amount, $total) {
            $datas = $this->model->withTrashed()->sheepFilter()->with(['user', 'items' => function ($query) {
                $query->with(['express']);
            }, 'address'])
                ->limit((($pages['page'] - 1) * $pages['list_rows']), $pages['list_rows'])
                ->select();

            $datas = collection($datas);
            $datas = $datas->each(function ($order) {
                $order->pay_types_text = $order->pay_types_text;
            })->toArray();

            $newDatas = [];
            foreach ($datas as &$order) {
                $order = $this->model->setOrderItemStatusByOrder($order);

                // 收货人信息
                $consignee_info = '';
                if ($order['address']) {
                    $address = $order['address'];
                    $consignee_info = ($address['consignee'] ? ($address['consignee'] . ':' . $address['mobile'] . '-') : '') . ($address['province_name'] . '-' . $address['city_name'] . '-' . $address['district_name']) . ' ' . $address['address'];
                }

                $data = [
                    'order_id' => $order['id'],
                    'order_sn' => $order['order_sn'],
                    'type_text' => $order['type_text'],
                    'user_nickname' => $order['user'] ? $order['user']['nickname'] : '-',
                    'user_mobile' => $order['user'] ? $order['user']['mobile'] . ' ' : '-',
                    'status_text' => $order['status_text'],
                    'pay_text' => in_array($order['status'], [OrderModel::STATUS_PAID, OrderModel::STATUS_COMPLETED]) ? '已支付' : '未支付',
                    'pay_types_text' => is_array($order['pay_types_text']) ? join(',', $order['pay_types_text']) : ($order['pay_types_text'] ?: ''),
                    'remark' => $order['remark'],
                    'memo' => $order['memo'],
                    'order_amount' => $order['order_amount'],
                    'score_amount' => $order['score_amount'],
                    'dispatch_amount' => $order['dispatch_amount'],
                    'pay_fee' => $order['pay_fee'],
                    'real_pay_fee' => bcsub($order['pay_fee'], $order['remain_pay_fee'], 2),
                    'remain_pay_fee' => $order['remain_pay_fee'],
                    'total_discount_fee' => $order['total_discount_fee'],
                    'coupon_discount_fee' => $order['coupon_discount_fee'],
                    'promo_discount_fee' => $order['promo_discount_fee'],
                    'paid_time' => $order['paid_time'],
                    'platform_text' => $order['platform_text'],
                    'consignee_info' => $consignee_info,
                    'createtime' => $order['createtime'],
                ];

                $items = [];
                foreach ($order['items'] as $item) {
                    $items[] = [
                        'activity_type_text' => $item['activity_type_text'],
                        'promo_types_text' => is_array($item['promo_types_text']) ? join(',', $item['promo_types_text']) : ($item['promo_types_text'] ?: '-'),
                        'goods_title' => $item['goods_title'],
                        'goods_sku_text' => $item['goods_sku_text'],
                        'goods_num' => $item['goods_num'],
                        'goods_original_price' => $item['goods_original_price'],
                        'goods_price' => $item['goods_price'],
                        'goods_weight' => $item['goods_weight'],
                        'discount_fee' => $item['discount_fee'],
                        'goods_pay_fee' => $item['pay_fee'],
                        'dispatch_type_text' => $item['dispatch_type_text'],
                        'dispatch_status_text' => $item['dispatch_status_text'],
                        'aftersale_refund' => $item['aftersale_status_text'] . '/' . $item['refund_status_text'],
                        'comment_status_text' => $item['comment_status_text'],
                        'refund_fee' => $item['refund_fee'],
                        'refund_msg' => $item['refund_msg'],
                        'express_name' => $item['express'] ? $item['express']['express_name'] : '-',
                        'express_no' => $item['express'] ? $item['express']['express_no'] . ' ' : '-',
                    ];
                }

                $data['items'] = $items;

                $newDatas[] = $data;
            }

            $total_order_amount += array_sum(array_column($newDatas, 'order_amount'));
            $total_score_amount += array_sum(array_column($newDatas, 'score_amount'));
            $total_pay_fee += array_sum(array_column($newDatas, 'pay_fee'));
            $total_real_pay_fee += array_sum(array_column($newDatas, 'real_pay_fee'));
            $total_discount_fee += array_sum(array_column($newDatas, 'discount_fee'));

            if ($pages['is_last_page']) {
                $newDatas[] = ['order_id' => "订单总数：" . $total . "；订单总金额：￥" . $total_order_amount .  "；优惠总金额：￥" . $total_discount_fee . "；应付总金额：￥" . $total_pay_fee . "；实付总金额：￥" . $total_real_pay_fee . "；支付总积分：" . $total_score_amount];
            }
            return $newDatas;
        });

        $this->success('导出成功' . (isset($result['file_path']) && $result['file_path'] ? '，请在服务器: “' . $result['file_path'] . '” 查看' : ''), null, $result);
    }


    public function exportDelivery()
    {
        $cellTitles = [
            // 订单表字段
            'order_id' => 'Id',
            'order_sn' => '订单号',
            'type_text' => '订单类型',
            'consignee_info' => '收货信息',
            'remark' => '用户备注',
            'memo' => '商家备注',
            'createtime' => '下单时间',

            // 订单商品表字段
            'order_item_id' => '子订单Id',
            'goods_title' => '商品名称',
            'goods_sku_text' => '商品规格',
            'goods_num' => '购买数量',
            // 'dispatch_fee' => '发货费用',
            'dispatch_type_text' => '发货方式',
            'dispatch_status_text' => '发货状态',
            'aftersale_refund' => '售后/退款',
            'express_no' => '快递单号',
        ];

        // 数据总条数
        $total = $this->model->sheepFilter()->count();      // nosend 加了 noApplyRefund
        if ($total <= 0) {
            $this->error('导出数据为空');
        }

        $export = new \addons\shopro\library\Export();
        $params = [
            'file_name' => '订单发货单列表',
            'cell_titles' => $cellTitles,
            'total' => $total,
            'is_sub_cell' => true,
            'sub_start_cell' => 'order_item_id',
            'sub_field' => 'items'
        ];

        $result = $export->export($params, function ($pages) use (&$total) {
            // 未申请全额退款的
            $datas = $this->model->sheepFilter()->with(['user', 'items' => function ($query) {      // nosend 加了 noApplyRefund
                $query->with(['express']);
            }, 'address'])
                ->limit((($pages['page'] - 1) * $pages['list_rows']), $pages['list_rows'])
                ->select();
            $datas = collection($datas)->toArray();

            $newDatas = [];
            foreach ($datas as &$order) {
                $order = $this->model->setOrderItemStatusByOrder($order);

                if (in_array($order['status_code'], ['groupon_ing', 'groupon_invalid'])) {
                    // 拼团正在进行中，不发货
                    $total--;       // total 减少 1
                    continue;
                }

                // 收货人信息
                $consignee_info = '';
                if ($order['address']) {
                    $address = $order['address'];
                    $consignee_info = ($address['consignee'] ? ($address['consignee'] . ':' . $address['mobile'] . '-') : '') . ($address['province_name'] . '-' . $address['city_name'] . '-' . $address['district_name']) . ' ' . $address['address'];
                }

                $data = [
                    'order_id' => $order['id'],
                    'order_sn' => $order['order_sn'],
                    'type_text' => $order['type_text'],
                    'consignee_info' => $consignee_info,
                    'remark' => $order['remark'],
                    'memo' => $order['memo'],
                    'createtime' => $order['createtime']
                ];

                $items = [];
                foreach ($order['items'] as $k => $item) {
                    // 未发货，并且未退款，并且未在申请售后中,并且是快递物流的
                    if (
                        $item['dispatch_status'] == OrderItem::DISPATCH_STATUS_NOSEND
                        && !in_array($item['refund_status'], [OrderItem::REFUND_STATUS_AGREE, OrderItem::REFUND_STATUS_COMPLETED])
                        && $item['aftersale_status'] != OrderItem::AFTERSALE_STATUS_ING
                        && $item['dispatch_type'] == 'express'
                    ) {
                        $items[] = [
                            'order_item_id' => $item['id'],
                            'goods_title' => strpos($item['goods_title'], '=') === 0 ? ' ' . $item['goods_title'] : $item['goods_title'],
                            'goods_sku_text' => $item['goods_sku_text'],
                            'goods_num' => $item['goods_num'],
                            // 'dispatch_fee' => $item['dispatch_fee'],
                            'dispatch_type_text' => $item['dispatch_type_text'],
                            'dispatch_status_text' => $item['dispatch_status_text'],
                            'aftersale_refund' => $item['aftersale_status_text'] . '/' . $item['refund_status_text'],
                            'express_no' => $item['express'] ? $item['express']['express_no'] . ' ' : '',
                        ];
                    }
                }

                $data['items'] = $items;
                $newDatas[] = $data;
            };

            if ($pages['is_last_page']) {
                $newDatas[] = ['order_id' => "订单总数（仅快递物流的待发货订单）：" . $total . "；备注:订单中同一包裹请填写相同运单号"];
            }
            return $newDatas;
        });

        $this->success('导出成功' . (isset($result['file_path']) && $result['file_path'] ? '，请在服务器: “' . $result['file_path'] . '” 查看' : ''), null, $result);
    }
}
