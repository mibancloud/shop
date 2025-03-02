<?php

namespace app\admin\controller\shopro;

use app\admin\model\shopro\order\Order;
use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\goods\Goods;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\Share;
use addons\shopro\service\SearchHistory;

class Dashboard extends Common
{

    public function index()
    {
        $cardList = [
            [
                'name' => 'total',
                'card' => 'shopro/dashboard/total',
                'status' => $this->auth->check('shopro/dashboard/total')
            ],
            [
                'name' => 'chart',
                'card' => 'shopro/dashboard/chart',
                'status' => $this->auth->check('shopro/dashboard/chart')
            ],
            [
                'name' => 'ranking',
                'card' => 'shopro/dashboard/ranking',
                'status' => $this->auth->check('shopro/dashboard/ranking')
            ],
        ];
        $this->assignconfig('cardList', $cardList);
        return $this->view->fetch();
    }


    public function total()
    {
        // 用户数据
        $userData = [
            'total' => User::count(),
            'today' => User::whereTime('createtime', 'today')->count(),
            'week' => User::whereTime('createtime', 'week')->count(),
            'array' => collection(User::field('id,createtime')->whereTime('createtime', 'today')->select())->each(function ($user) {
                $user->counter = 1;
                $user->createtime_unix = $user->getData('createtime') * 1000;
            })
        ];

        // -- commission code start --
        $agentData = [
            'total' => \app\admin\model\shopro\commission\Agent::count(),
            'today' => \app\admin\model\shopro\commission\Agent::whereTime('createtime', 'today')->count(),
            'week' => \app\admin\model\shopro\commission\Agent::whereTime('createtime', 'week')->count(),
            'array' => collection(\app\admin\model\shopro\commission\Agent::field('user_id,createtime')->whereTime('createtime', 'today')->select())->each(function ($agent) {
                $agent->counter = 1;
                $agent->createtime_unix = $agent->getData('createtime') * 1000;
            })
        ];
        // -- commission code end --

        // 分享数据
        $shareData = [
            'total' => Share::count(),
            'today' => Share::whereTime('createtime', 'today')->count(),
            'week' => Share::whereTime('createtime', 'week')->count(),
            'array' => collection(Share::field('id,createtime')->whereTime('createtime', 'today')->select())->each(function ($share) {
                $share->counter = 1;
                $share->createtime_unix = $share->getData('createtime') * 1000;
            })
        ];

        $this->success('获取成功', null, [
            'user_data' => $userData,
            'agent_data' => $agentData ?? null,
            'share_data' => $shareData
        ]);
    }



    public function chart()
    {
        $date = $this->request->param('date', '');
        $date = array_values(array_filter(explode(' - ', $date)));

        $orders = Order::with(['items'])->whereTime('createtime', 'between', $date)
            ->order('id', 'asc')->select();

        // 订单数
        $data['orderNum'] = count($orders);
        $data['orderArr'] = [];

        // 支付订单(包含退款的订单, 不包含货到付款还未收货(未支付)订单)
        $data['payOrderNum'] = 0;
        $data['payOrderArr'] = [];
        //支付金额(包含退款的订单, 不包含货到付款还未收货(未支付)订单)
        $data['payAmountNum'] = 0;
        $data['payAmountArr'] = [];

        // 支付用户（一个人不管下多少单，都算一个，包含退款的订单, 不包含货到付款还未收货(未支付)订单）
        $userIds = [];
        $data['payUserNum'] = 0;
        $data['payUserArr'] = [];

        // 代发货(包含货到付款)
        $data['noSendNum'] = 0;
        $data['noSendArr'] = [];
        //售后维权
        $data['aftersaleNum'] = 0;
        $data['aftersaleArr'] = [];
        //退款订单
        $data['refundNum'] = 0;
        $data['refundArr'] = [];

        foreach ($orders as $key => $order) {
            $data['orderArr'][] = [
                'counter' => 1,
                'createtime' => $order->getData('createtime') * 1000,
                'user_id' => $order->user_id
            ];

            // 已支付的，不包含，货到付款未支付的
            if (in_array($order->status, [Order::STATUS_PAID, Order::STATUS_COMPLETED])) {
                // 支付订单数
                $data['payOrderNum']++;

                $data['payOrderArr'][] = [
                    'counter' => 1,
                    'createtime' => $order->getData('createtime') * 1000,
                    'user_id' => $order->user_id
                ];

                // 支付金额
                $data['payAmountNum'] = bcadd((string)$data['payAmountNum'], $order->pay_fee, 2);

                $data['payAmountArr'][] = [
                    'counter' => $order->pay_fee,
                    'createtime' => $order->getData('createtime') * 1000,
                ];

                // 下单用户
                if (!in_array($order->user_id, $userIds)) {
                    $data['payUserNum']++;
                    $data['payUserArr'][] = [
                        'counter' => 1,
                        'createtime' => $order->getData('createtime') * 1000,
                        'user_id' => $order->user_id
                    ];
                }
            }

            // 已支付的，和 货到付款未支付的
            if (in_array($order->status, [Order::STATUS_PAID, Order::STATUS_COMPLETED]) || $order->isOffline($order)) {
                $flagnoSend = false;
                $flagaftersale = false;
                $flagrefund = false;
                $aftersaleIng = false;
                foreach ($order->items as $k => $item) {
                    if (
                        !$flagnoSend
                        && $item->dispatch_status == OrderItem::DISPATCH_STATUS_NOSEND
                        && $item->refund_status == OrderItem::REFUND_STATUS_NOREFUND
                        && in_array($order->apply_refund_status, [
                            Order::APPLY_REFUND_STATUS_NOAPPLY,
                            Order::APPLY_REFUND_STATUS_REFUSE
                        ])
                    ) {
                        $flagnoSend = true;
                    }

                    if (
                        $item->aftersale_status == OrderItem::AFTERSALE_STATUS_ING
                        && $item->dispatch_status == OrderItem::DISPATCH_STATUS_NOSEND
                        && $item->refund_status == OrderItem::REFUND_STATUS_NOREFUND
                    ) {
                        $aftersaleIng = true;
                    }

                    if (!$flagaftersale && $item->aftersale_status != OrderItem::AFTERSALE_STATUS_NOAFTER) {
                        $data['aftersaleNum']++;
                        // $data['aftersaleArr'][] = [
                        //     'counter' => 1,
                        //     'createtime' => $order->getData('createtime') * 1000,
                        // ];
                        $flagaftersale = true;
                    }

                    if (!$flagrefund && $item->refund_status > OrderItem::REFUND_STATUS_NOREFUND) {
                        $data['refundNum']++;
                        // $data['refundArr'][] = [
                        //     'counter' => 1,
                        //     'createtime' => $order->getData('createtime') * 1000,
                        // ];
                        $flagrefund = true;
                    }
                }

                if (!$aftersaleIng && $flagnoSend) {
                    // 存在正在售后中的订单，不算待发货（和订单列表保持一直）
                    $data['noSendNum']++;
                    // $data['noSendArr'][] = [
                    //     'counter' => 1,
                    //     'createtime' => $order->getData('createtime') * 1000,
                    // ];
                }
            }
        }

        $this->success('获取成功', null, $data);
    }



    public function ranking()
    {
        $goods = Goods::limit(5)->order('sales', 'desc')->select();
        foreach ($goods as $key => $gd) {
            $gd->append(['real_sales']);
            $result = OrderItem::field('sum(goods_num * goods_price) as sale_total_money')->where('goods_id', $gd['id'])
                ->whereExists(function ($query) use ($gd) {
                    $order_table_name = (new Order())->getQuery()->getTable();
                    $table_name = (new OrderItem())->getQuery()->getTable();

                    $query->table($order_table_name)->where($table_name . '.order_id=' . $order_table_name . '.id')
                        ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_COMPLETED]);       // 已支付的订单
                })->find();

            $gd['sale_total_money'] = $result['sale_total_money'] ?: 0;
        }

        $searchHistory = new SearchHistory();

        $this->success('获取成功', null, [
            'goods' => $goods,
            'hot_search' => $searchHistory->hotSearch()
        ]);
    }
}
