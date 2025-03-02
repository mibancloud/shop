<?php

namespace app\admin\controller\shopro\trade;

use app\admin\controller\shopro\Common;
use app\admin\model\shopro\trade\Order as TradeOrderModel;
use app\admin\model\shopro\Pay as PayModel;

class Order extends Common
{

    protected $noNeedRight = ['getType'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new TradeOrderModel;
    }

    /**
     * 订单列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            $exportConfig = (new \addons\shopro\library\Export())->getConfig();
            $this->assignconfig("save_type", $exportConfig['save_type'] ?? 'download');
            return $this->view->fetch();
        }

        $orders = $this->model->withTrashed()->sheepFilter()->with(['user'])
            ->paginate(request()->param('list_rows', 10))->each(function ($order) {
                $order->pay_type_text = $order->pay_type_text;
                $order->pay_type = $order->pay_type;
            })->toArray();

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
        $status = $this->model->searchStatusList();

        $result = [
            'type' => $type,
            'pay_type' => $payType,
            'platform' => $platform,
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

        $order = $this->model->withTrashed()->with(['user', 'pays'])->where('id', $id)->find();
        if (!$order) {
            $this->error(__('No Results were found'));
        }

        $order->pay_type = $order->pay_type;
        $order->pay_type_text = $order->pay_type_text;

        $this->success('获取成功', null, $order);
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
            'pay_type_text' => '支付类型',
            'remark' => '用户备注',
            'order_amount' => '订单总金额',
            'pay_fee' => '应付总金额',
            'real_pay_fee' => '实付总金额',
            'remain_pay_fee' => '剩余支付金额',
            'paid_time' => '支付完成时间',
            'platform_text' => '交易平台',
            'createtime' => '下单时间',
        ];

        // 数据总条数
        $total = $this->model->withTrashed()->sheepFilter()->count();
        if ($total <= 0) {
            $this->error('导出数据为空');
        }

        $export = new \addons\shopro\library\Export();
        $params = [
            'file_name' => '交易订单列表',
            'cell_titles' => $cellTitles,
            'total' => $total,
        ];

        $total_order_amount = 0;
        $total_pay_fee = 0;
        $total_real_pay_fee = 0;
        $result = $export->export($params, function ($pages) use (&$total_order_amount, &$total_pay_fee, &$total_real_pay_fee, $total) {
            $datas = $this->model->withTrashed()->sheepFilter()->with(['user'])
                ->limit((($pages['page'] - 1) * $pages['list_rows']), $pages['list_rows'])
                ->select();

            $datas = collection($datas);
            $datas = $datas->each(function ($order) {
                $order->pay_type_text = $order->pay_type_text;
            })->toArray();

            $newDatas = [];
            foreach ($datas as &$order) {
                $data = [
                    'order_id' => $order['id'],
                    'order_sn' => $order['order_sn'],
                    'type_text' => $order['type_text'],
                    'user_nickname' => $order['user'] ? $order['user']['nickname'] : '-',
                    'user_mobile' => $order['user'] ? $order['user']['mobile'] . ' ' : '-',
                    'status_text' => $order['status_text'],
                    'pay_type_text' => is_array($order['pay_type_text']) ? join(',', $order['pay_type_text']) : ($order['pay_type_text'] ?: ''),
                    'remark' => $order['remark'],
                    'order_amount' => $order['order_amount'],
                    'pay_fee' => $order['pay_fee'],
                    'real_pay_fee' => bcsub($order['pay_fee'], $order['remain_pay_fee'], 2),
                    'remain_pay_fee' => $order['remain_pay_fee'],
                    'paid_time' => $order['paid_time'],
                    'platform_text' => $order['platform_text'],
                    'createtime' => $order['createtime'],
                ];
                $newDatas[] = $data;
            }

            $total_order_amount += array_sum(array_column($newDatas, 'order_amount'));
            $total_pay_fee += array_sum(array_column($newDatas, 'pay_fee'));
            $total_real_pay_fee += array_sum(array_column($newDatas, 'real_pay_fee'));

            if ($pages['is_last_page']) {
                $newDatas[] = ['order_id' => "订单总数：" . $total . "；订单总金额：￥" . $total_order_amount . "；应付总金额：￥" . $total_pay_fee . "；实付总金额：￥" . $total_real_pay_fee];
            }
            return $newDatas;
        });

        $this->success('导出成功' . (isset($result['file_path']) && $result['file_path'] ? '，请在服务器: “' . $result['file_path'] . '” 查看' : ''), null, $result);
    }
}
