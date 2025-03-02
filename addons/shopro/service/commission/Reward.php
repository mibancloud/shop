<?php

namespace addons\shopro\service\commission;

// use addons\shopro\library\Oper;
use app\admin\model\shopro\commission\Log as LogModel;
use app\admin\model\shopro\commission\Order as OrderModel;
use app\admin\model\shopro\commission\Reward as RewardModel;
use app\admin\model\shopro\user\User as UserModel;
use addons\shopro\service\Wallet as WalletService;


/**
 * 结算奖金
 */
class Reward
{

    protected $event = '';
    /**
     * 执行奖金计划,直接派发佣金
     * 
     * @param string    $event                     分佣的事件
     */
    public function __construct($event)
    {
        $this->event = $event;
    }
    /**
     * 执行奖金计划, 派发整单佣金
     * 
     * @param mixed      $commissionOrder|$commissionOrderId           分销订单 
     */
    public function runCommissionRewardByOrder($commissionOrder)
    {
        if (is_numeric($commissionOrder)) {
            $commissionOrder = OrderModel::find($commissionOrder);
        }

        // 未找到分销订单
        if (!$commissionOrder) {
            return false;
        }
        // 已经操作过了

        if ($commissionOrder['commission_reward_status'] !== RewardModel::COMMISSION_REWARD_STATUS_PENDING) {
            return false;
        }

        $rewardEvent = $commissionOrder['reward_event'];

        // 不满足分佣事件
        if ($rewardEvent !== $this->event && $this->event !== 'admin') {
            return false;
        }

        // 更新分销订单结算状态
        $commissionOrder->commission_reward_status = RewardModel::COMMISSION_REWARD_STATUS_ACCOUNTED;
        $commissionOrder->commission_time = time();
        $commissionOrder->save();

        // 防止重复添加佣金
        $commissionRewards = RewardModel::where([
            'commission_order_id' => $commissionOrder['id'],
            'status' => RewardModel::COMMISSION_REWARD_STATUS_PENDING,
        ])->select();

        // 添加分销商收益、余额添加钱包、更新分销佣金结算状态、提醒分销商到账
        if (count($commissionRewards) > 0) {
            foreach ($commissionRewards as $commissionReward) {
                $this->runCommissionReward($commissionReward);
            }
        }
        return true;
    }

    /**
     * 执行奖金计划,直接派发佣金
     * 
     * @param mixed     $commissionReward|$commissionRewardId           奖金记录 
     */
    public function runCommissionReward($commissionReward)
    {
        if (is_numeric($commissionReward)) {
            $commissionReward = RewardModel::find($commissionReward);
        }

        // 未找到奖金记录
        if (!$commissionReward) {
            return false;
        }

        if ($commissionReward->status == RewardModel::COMMISSION_REWARD_STATUS_PENDING) {
            $rewardAgent = new Agent($commissionReward->agent_id);
            if ($rewardAgent->isAgentAvaliable()) {
                $rewardAgent->agent->setInc('total_income', $commissionReward->commission);
                $commissionReward->status = RewardModel::COMMISSION_REWARD_STATUS_ACCOUNTED;
                $commissionReward->commission_time = time();
                $commissionReward->save();
                WalletService::change($rewardAgent->user, $commissionReward->type, $commissionReward->commission, 'reward_income', $commissionReward);

                LogModel::add($rewardAgent->user->id, 'reward', [
                    'type' => $this->event,
                    'reward' => $commissionReward
                ]);
            }
        }
        return true;
    }


    /**
     * 扣除/取消 分销订单
     * 
     * @param mixed    $commissionOrder|$commissionOrderId           分销订单 
     * @param array     $deductOrderMoney               扣除分销商的订单业绩  默认扣除
     * @param array     $deleteReward              扣除分销订单奖金     默认扣除
     */
    public function backCommissionRewardByOrder($commissionOrder, $deductOrderMoney = true, $deleteReward = true)
    {
        if ($this->event !== 'admin' && $this->event !== 'refund') {
            return false;
        }

        if ($this->event === 'refund') {
            $config = new Config();
            $deductOrderMoney = $config->getRefundCommissionOrder();
            $deleteReward = $config->getRefundCommissionReward();
        }

        if (!$deductOrderMoney && !$deleteReward) {
            return false;
        }

        if (is_numeric($commissionOrder)) {
            $commissionOrder = OrderModel::find($commissionOrder);
        }

        // 未找到分销订单
        if (!$commissionOrder) {
            return false;
        }

        // 扣除分销商的订单业绩
        if ($deductOrderMoney) {
            // 变更分销订单状态
            if ($commissionOrder->commission_order_status == OrderModel::COMMISSION_ORDER_STATUS_YES) {    // 扣除
                $commissionOrder->commission_order_status = OrderModel::COMMISSION_ORDER_STATUS_BACK;
                $commissionOrder->save();
                $orderAgent = new Agent($commissionOrder->agent_id);
                // 扣除分销订单业绩
                if($commissionOrder->self_buy) {
                    $orderAgent->agent->setDec('child_order_money_0', $commissionOrder->amount);
                    $orderAgent->agent->setDec('child_order_count_0', 1);
                }else {
                    $orderAgent->agent->setDec('child_order_money_1', $commissionOrder->amount);
                    $orderAgent->agent->setDec('child_order_count_1', 1);
                }

                // 重新计算分销链条业绩
                $orderAgent->createAsyncAgentUpgrade();

                LogModel::add($orderAgent->user->id, 'order', [
                    'type' => $this->event,
                    'order' => $commissionOrder,
                    'buyer' => UserModel::find($commissionOrder->buyer_id)
                ]);
            }

            if ($commissionOrder->commission_order_status == OrderModel::COMMISSION_ORDER_STATUS_NO) {    // 取消
                $commissionOrder->commission_order_status = OrderModel::COMMISSION_ORDER_STATUS_CANCEL;
                $commissionOrder->save();
            }
        }

        // 变更分销订单佣金执行状态
        if ($deleteReward) {
            if ($commissionOrder->commission_reward_status == RewardModel::COMMISSION_REWARD_STATUS_ACCOUNTED) { // 扣除
                $commissionOrder->commission_reward_status = RewardModel::COMMISSION_REWARD_STATUS_BACK;
                $commissionOrder->save();
                // 防止重复扣除佣金
                $commissionRewards = RewardModel::where([
                    'commission_order_id' => $commissionOrder->id,
                    'status' => RewardModel::COMMISSION_REWARD_STATUS_ACCOUNTED,
                ])->select();
                if (count($commissionRewards) > 0) {
                    // 扣除分销佣金
                    foreach ($commissionRewards as $commissionReward) {
                        $this->backCommissionReward($commissionReward);
                    }
                }
            }
        }

        if ($commissionOrder->commission_reward_status == RewardModel::COMMISSION_REWARD_STATUS_PENDING) {  // 取消
            $commissionOrder->commission_reward_status = RewardModel::COMMISSION_REWARD_STATUS_CANCEL;
            $commissionOrder->save();
            $commissionRewards = RewardModel::where([
                'commission_order_id' => $commissionOrder->id,
                'status' => RewardModel::COMMISSION_REWARD_STATUS_PENDING
            ])->select();
            // 取消分销佣金
            if (count($commissionRewards) > 0) {
                foreach ($commissionRewards as $commissionReward) {
                    $this->cancelCommissionReward($commissionReward);
                }
            }
        }
        return true;
    }

    /**
     * 扣除单笔分销佣金
     * 
     * @param mixed     $commissionReward|$commissionRewardId           奖金记录 
     */
    public function backCommissionReward($commissionReward)
    {
        if (is_numeric($commissionReward)) {
            $commissionReward = RewardModel::find($commissionReward);
        }

        // 未找到奖金记录
        if (!$commissionReward) {
            return false;
        }
        if ($commissionReward->status == RewardModel::COMMISSION_REWARD_STATUS_ACCOUNTED) {
            $rewardAgent = new Agent($commissionReward->agent_id);
            if ($rewardAgent->agent->total_income < $commissionReward->commission) {
                $rewardAgent->agent->total_income = 0;
                $rewardAgent->agent->save();
            } else {
                $rewardAgent->agent->setDec('total_income', $commissionReward->commission);
            }
            WalletService::change($rewardAgent->user, $commissionReward->type, -$commissionReward->commission, 'reward_back', $commissionReward);
            $commissionReward->status = RewardModel::COMMISSION_REWARD_STATUS_BACK;
            $commissionReward->save();
            LogModel::add($rewardAgent->user->id, 'reward', [
                'type' => $this->event,
                'reward' => $commissionReward,
            ]);
        }
        return true;
    }

    /**
     * 取消单笔分销佣金
     * 
     * @param mixed     $commissionReward|$commissionRewardId           奖金记录 
     */
    public function cancelCommissionReward($commissionReward)
    {
        if (is_numeric($commissionReward)) {
            $commissionReward = RewardModel::find($commissionReward);
        }

        // 未找到奖金记录
        if (!$commissionReward) {
            return false;
        }

        if ($commissionReward->status == RewardModel::COMMISSION_REWARD_STATUS_PENDING) {
            $commissionReward->status = RewardModel::COMMISSION_REWARD_STATUS_CANCEL;
            $commissionReward->save();

            $rewardAgent = new Agent($commissionReward->agent_id);
            LogModel::add($rewardAgent->user->id, 'reward', [
                'type' => $this->event,
                'reward' => $commissionReward,
            ]);
        }
        return true;
    }
}
