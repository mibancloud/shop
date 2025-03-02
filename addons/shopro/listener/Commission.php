<?php

namespace addons\shopro\listener;

use app\admin\model\shopro\commission\Order as CommissionOrderModel;
use addons\shopro\service\commission\Order as OrderService;
use addons\shopro\service\commission\Reward as RewardService;
use addons\shopro\service\commission\Agent as AgentService;
use app\admin\model\shopro\Share as ShareModel;

class Commission
{

    /**
     * 用户注册成功
     */
    public function userRegisterAfter($payload)
    {
        $shareInfo = request()->param('shareInfo/a');

        if ($shareInfo) {
            // 注册后添加分享信息
            ShareModel::log($payload['user'], $shareInfo);
        }
        $agent = new AgentService($payload['user']);

        $agent->createNewAgent('user');
    }

    /**
     * 用户分享行为后
     */
    public function userShareAfter($payload)
    {
        $shareInfo = $payload['shareInfo'];

        if ($shareInfo) {

            $user_id = intval($shareInfo->user_id); // 受邀用户

            $share_id = intval($shareInfo->share_id); // 邀请人

            $agent = new AgentService($user_id);

            $bindCheck = $agent->bindUserRelation('share', $share_id);

            // 统计单链业绩
            if ($bindCheck) {
                $agent->createAsyncAgentUpgrade($user_id);
            }
        }
    }

    /**
     * 订单支付后
     */
    public function orderPaidAfter($params)
    {
        // 订单支付成功
        $order = $params['order'];

        // 积分商品订单不参与分销
        if ($order['type'] === 'score') return;

        $user = $params['user'];

        $agent = new AgentService($user);

        // 绑定用户关系
        $agent->bindUserRelation('pay');

        // 先记录分佣 再处理记录业绩、升级等情况
        $items = $order ? $order['items'] : [];

        foreach ($items as $item) {
            if (isset($item['ext']['is_commission']) && !$item['ext']['is_commission']) continue;

            $commission = new OrderService($item);
            // 检查能否分销
            if (!$commission->checkAndSetCommission()) continue;
            // 添加分销订单
            $commissionOrder = $commission->createCommissionOrder();

            if (!$commissionOrder) continue;

            // 执行佣金计划
            $commission->runCommissionPlan($commissionOrder);
            // 支付后拨款
            (new RewardService('paid'))->runCommissionRewardByOrder($commissionOrder);
        }

        // 创建分销商
        $agent->createNewAgent();
        // 分销商数据统计&升级(异步执行) 
        $agent->createAsyncAgentUpgrade();
    }

    // 线下付款后
    public function orderOfflinePaidAfter($params)
    {
        $this->orderPaidAfter($params);
    }

    /**
     * 订单确认收货
     */
    public function orderConfirmFinish($params)
    {
        $order = $params['order'];

        $service = new RewardService('confirm');
        $commissionOrders = collection(CommissionOrderModel::where('order_id', $order->id)->select());
        $commissionOrders->each(function ($commissionOrder) use ($service) {
            $service->runCommissionRewardByOrder($commissionOrder);
        });
    }

    /**
     * 订单完成
     */
    public function orderFinish($params)
    {
        $order = $params['order'];

        $service = new RewardService('finish');
        $commissionOrders = collection(CommissionOrderModel::where('order_id', $order->id)->select());
        $commissionOrders->each(function ($commissionOrder) use ($service) {
            $service->runCommissionRewardByOrder($commissionOrder);
        });
    }

    /**
     * 订单退款后
     */
    public function orderItemRefundAfter($params)
    {
        $order = $params['order'];
        $item = $params['item'];

        $commissionOrder = CommissionOrderModel::where('order_id', $order->id)->where('order_item_id', $item->id)->find();

        if (!$commissionOrder) return;

        (new RewardService('refund'))->backCommissionRewardByOrder($commissionOrder);
    }
}
