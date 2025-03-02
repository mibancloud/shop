<?php

namespace app\admin\controller\shopro\commission;

use think\Db;
use think\exception\HttpResponseException;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\commission\Order as OrderModel;
use app\admin\model\shopro\commission\Reward as RewardModel;
use addons\shopro\service\commission\Reward as RewardService;

class Order extends Common
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new OrderModel();
    }

    /**
     * 查看
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            $exportConfig = (new \addons\shopro\library\Export())->getConfig();
            $this->assignconfig("save_type", $exportConfig['save_type'] ?? 'download');
            return $this->view->fetch();
        }

        $list = $this->model->sheepFilter()->with(['buyer', 'agent', 'order', 'rewards.agent', 'order_item'])->paginate($this->request->param('list_rows', 10));
        $list = $list->toArray();

        // 统计数据
        $count = [
            'total' => $list['total'],
            'total_amount' => 0,
            'total_commission' => 0,
            'total_commission_cancel' => 0,
            'total_commission_accounted' => 0,
            'total_commission_back' => 0,
            'total_commission_pending' => 0
        ];

        $orders = $this->model->sheepFilter()->with(['rewards'])->select();
        collection($orders)->each(function ($order) use (&$count) {
            $count['total_amount'] += $order['amount'];
            foreach ($order['rewards'] as $reward) {
                $count['total_commission'] += $reward['commission'];
                switch ($reward['status']) {
                    case RewardModel::COMMISSION_REWARD_STATUS_ACCOUNTED:
                        $count['total_commission_accounted'] += $reward['commission'];
                        break;
                    case RewardModel::COMMISSION_REWARD_STATUS_BACK:
                        $count['total_commission_back'] += $reward['commission'];
                        break;
                    case RewardModel::COMMISSION_REWARD_STATUS_PENDING:
                        $count['total_commission_pending'] += $reward['commission'];
                        break;
                    case RewardModel::COMMISSION_REWARD_STATUS_CANCEL:
                        $count['total_commission_cancel'] += $reward['commission'];
                        break;
                }
            }
        });

        $this->success('', null, [
            'list' => $list,
            'count' => $count
        ]);
    }

    /**
     * 结算佣金
     *
     * @return Response
     */
    public function confirm()
    {
        $params = $this->request->only(['commission_reward_id', 'commission_order_id']);

        try {
            Db::transaction(function () use ($params) {
                $rewardService = new RewardService('admin');
                if (isset($params['commission_reward_id'])) {
                    return $rewardService->runCommissionReward($params['commission_reward_id']);
                } elseif (isset($params['commission_order_id'])) {
                    return $rewardService->runCommissionRewardByOrder($params['commission_order_id']);
                }
            });
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            $this->error($message);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success('操作成功');
    }

    /**
     * 取消结算
     *
     * @return Response
     */
    public function cancel()
    {
        $params = $this->request->only(['commission_reward_id', 'commission_order_id']);

        try {

            Db::transaction(function () use ($params) {
                $rewardService = new RewardService('admin');
                if (isset($params['commission_reward_id'])) {
                    return $rewardService->cancelCommissionReward($params['commission_reward_id']);
                } elseif (isset($params['commission_order_id'])) {
                    return $rewardService->backCommissionRewardByOrder($params['commission_order_id']);
                }
            });
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            $this->error($message);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('操作成功');
    }

    /**
     * 退回已结算佣金
     */
    public function back()
    {
        $params = $this->request->only(['commission_reward_id', 'commission_order_id', 'deduct_order_money']);

        try {
            Db::transaction(function () use ($params) {
                $rewardService = new RewardService('admin');
                if (isset($params['commission_reward_id'])) {
                    return $rewardService->backCommissionReward($params['commission_reward_id']);
                } elseif (isset($params['commission_order_id'])) {
                    return $rewardService->backCommissionRewardByOrder($params['commission_order_id'], $params['deduct_order_money']);
                }
            });
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            $this->error($message);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('操作成功');
    }

    /**
     * 修改待结算佣金
     */
    public function edit($id = null)
    {
        $params = $this->request->only(['commission_reward_id', 'commission']);
        $reward = RewardModel::get($params['commission_reward_id']);
        if (!$reward) {
            $this->error(__('No Results were found'));
        }
        
        $reward->commission = $params['commission'];
        $result = $reward->save();
        if ($result) {
            $this->success('操作成功');
        }
        $this->error('操作失败');
    }




    public function export()
    {
        $cellTitles = [
            // 主要字段
            'commission_order_id' => 'Id',
            'order_sn' => '订单号',
            'goods_title' => '商品名称',
            'goods_sku_text' => '商品规格',
            'goods_price' => '商品价格',
            'goods_num' => '购买数量',
            'refund_status_text' => '退款状态',
            'buyer_nickname' => '下单用户',
            'buyer_mobile' => '手机号',
            'share_nickname' => '推广分销商',
            'share_mobile' => '手机号',
            'commission_reward_status_text' => '佣金状态',
            'reward_event_text' => '结算方式',
            'commission_time' => '结算时间',
            'reward_type_text' => '商品结算方式',
            'amount' => '商品结算金额',
            'commission_order_status_text' => '分销商业绩',
            'total_commission' => '分销总金额',
            'total_commissioned' => '到账金额',
            // 佣金明细
            'reward_agent_nickname' => '分佣用户',
            'reward_agent_mobile' => '分佣手机号',
            'reward_commission' => '分佣金额',
            'reward_status_text' => '分佣状态',
            'reward_type_text' => '入账方式',
            'reward_commission_time' => '结算时间',
        ];

        // 数据总条数
        $total = $this->model->sheepFilter()->count();
        if ($total <= 0) {
            $this->error('导出数据为空');
        }

        $export = new \addons\shopro\library\Export();
        $params = [
            'file_name' => '分销订单列表',
            'cell_titles' => $cellTitles,
            'total' => $total,
            'is_sub_cell' => true,
            'sub_start_cell' => 'reward_agent_nickname',
            'sub_field' => 'rewards'
        ];

        $total_amount = 0;
        $total_commission = 0;
        $total_commission_cancel = 0;
        $total_commission_accounted = 0;
        $total_commission_back = 0;
        $total_commission_pending = 0;
        $result = $export->export($params, function ($pages) use (&$total_amount, &$total_commission, &$total_commission_cancel, &$total_commission_accounted, &$total_commission_back, &$total_commission_pending, $total) {
            $datas = $this->model->sheepFilter()->with(['buyer', 'agent', 'order', 'rewards.agent', 'order_item'])
                ->limit((($pages['page'] - 1) * $pages['list_rows']), $pages['list_rows'])
                ->select();

            $datas = collection($datas);
            $datas->each(function ($commissionOrder) use (&$total_amount, &$total_commission, &$total_commission_cancel, &$total_commission_accounted, &$total_commission_back, &$total_commission_pending, $total) {
                $total_amount += $commissionOrder['amount'];
                foreach ($commissionOrder['rewards'] as $reward) {
                    $total_commission += $reward['commission'];
                    switch ($reward['status']) {
                        case RewardModel::COMMISSION_REWARD_STATUS_ACCOUNTED:
                            $total_commission_accounted += $reward['commission'];
                            break;
                        case RewardModel::COMMISSION_REWARD_STATUS_BACK:
                            $total_commission_back += $reward['commission'];
                            break;
                        case RewardModel::COMMISSION_REWARD_STATUS_PENDING:
                            $total_commission_pending += $reward['commission'];
                            break;
                        case RewardModel::COMMISSION_REWARD_STATUS_CANCEL:
                            $total_commission_cancel += $reward['commission'];
                            break;
                    }
                }
            })->toArray();

            $newDatas = [];
            foreach ($datas as $commissionOrder) {
                $commission = 0;
                $commissioned = 0;
                foreach ($commissionOrder['rewards'] as $reward) {
                    if ($reward['status'] == 1) {
                        $commissioned += $reward['commission'];
                    }
                    $commission += $reward['commission'];
                }

                $data = [
                    'commission_order_id' => $commissionOrder['id'],
                    'order_sn' => $commissionOrder['order'] ? $commissionOrder['order']['order_sn'] : '',
                    'goods_title' => $commissionOrder['order_item'] ? '#' . $commissionOrder['order_item']['goods_id'] . ' ' . $commissionOrder['order_item']['goods_title'] : '',
                    'goods_sku_text' => $commissionOrder['order_item'] ? $commissionOrder['order_item']['goods_sku_text'] : '',
                    'goods_price' => $commissionOrder['order_item'] ? $commissionOrder['order_item']['goods_price'] : '',
                    'goods_num' => $commissionOrder['order_item'] ? $commissionOrder['order_item']['goods_num'] : '',
                    'refund_status_text' => $commissionOrder['order_item'] ? $commissionOrder['order_item']['refund_status_text'] : '',
                    'buyer_nickname' => $commissionOrder['buyer'] ? $commissionOrder['buyer']['nickname'] : '-',
                    'buyer_mobile' => $commissionOrder['buyer'] ? $commissionOrder['buyer']['mobile'] . ' ' : '-',
                    'share_nickname' => $commissionOrder['agent'] ? $commissionOrder['agent']['nickname'] : '-',
                    'share_mobile' => $commissionOrder['agent'] ? $commissionOrder['agent']['mobile'] . ' ' : '-',

                    // 这里循环 rewards 佣金详情

                    'commission_reward_status_text' => $commissionOrder['commission_reward_status_text'],
                    'reward_event_text' => $commissionOrder['reward_event_text'],
                    'commission_time' => $commissionOrder['commission_time'],
                    'reward_type_text' => $commissionOrder['reward_type_text'],
                    'amount' => $commissionOrder['amount'],
                    'commission_order_status_text' => $commissionOrder['commission_order_status_text'],
                    'total_commission' => $commission,
                    'total_commissioned' => $commissioned,
                ];

                $rewardsItems = [];
                foreach ($commissionOrder['rewards'] as $reward) {
                    $rewardsItems[] = [
                        'reward_agent_nickname' => $reward['agent'] ? $reward['agent']['nickname'] : '',
                        'reward_agent_mobile' => $reward['agent'] ? $reward['agent']['mobile'] : '',
                        'reward_commission' => $reward['commission'],
                        'reward_status_text' => $reward['status_text'],
                        'reward_type_text' => $reward['type_text'],
                        'reward_commission_time' => $reward['commission_time']
                    ];
                }

                $data['rewards'] = $rewardsItems;

                $newDatas[] = $data;
            }

            if ($pages['is_last_page']) {
                $newDatas[] = ['order_id' => "商品总订单数：" . $total . "；商品结算总金额：￥" . $total_amount .  "；分佣总金额：￥" . $total_commission . "；已取消佣金：￥" . $total_commission_cancel . "；已退回佣金：￥" . $total_commission_back . "；未结算佣金：" . $total_commission_pending . "；已结算佣金：" . $total_commission_accounted];
            }
            return $newDatas;
        });

        $this->success('导出成功' . (isset($result['file_path']) && $result['file_path'] ? '，请在服务器: “' . $result['file_path'] . '” 查看' : ''), null, $result);
    }
}
