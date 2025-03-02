<?php

namespace addons\shopro\service\commission;

use addons\shopro\service\commission\Config;
use app\admin\model\shopro\commission\Order as OrderModel;
use app\admin\model\shopro\commission\Reward as RewardModel;
use app\admin\model\shopro\commission\Log as LogModel;


/**
 * 分佣业务
 */
class Order
{

    public $config;    // 分销设置
    public $item;       // 订单商品
    public $buyer;      // 购买人
    public $goods;      // 分销商品
    public $amount = 0;     // 订单商品核算价格
    public $commissionLevel = 0;    // 分销层级
    public $selfBuy;    // 是否自购

    // 分销状态
    const COMMISSION_CLOSE = 0;  // 分销功能已关闭

    /**
     * 构造函数
     * 
     * @param array      $item     分销商品
     */
    public function __construct($item)
    {
        $this->buyer = new Agent($item['user_id']);
        $this->item = $item;
        $this->config = new Config();
    }


    /**
     * 检测并设置分销商品规则
     *
     * @return void
     */
    public function checkAndSetCommission()
    {
        // 未找到订单商品或购买人
        if (!$this->item || !$this->buyer->user) {
            return false;
        }

        // 获取商品分销佣金规则
        $this->goods = new Goods($this->item['goods_id'], $this->item['goods_sku_price_id']);

        // 商品有独立分销设置，覆盖默认系统配置
        if ($commissionConfig = $this->goods->getCommissionConfig()) {
            $this->config->setConfig($commissionConfig);
        }

        // 获取系统设置分销层级
        $this->commissionLevel = $this->config->getCommissionLevel();

        // 分销中心已关闭
        if (self::COMMISSION_CLOSE === $this->commissionLevel) {
            return false;
        }

        // 商品不参与分销
        if (!$this->goods->getCommissionRules()) {
            return false;
        }

        // 是否自购分销订单
        $this->selfBuy = $this->buyer->isAgentAvaliable() && $this->config->isSelfBuy();

        // 未找到上级分销商且不是自购
        if (!$this->buyer->getParentUserId() && !$this->selfBuy) {
            return false;
        }

        // 获取商品结算价格（四舍五入精确到分）
        $this->amount = $this->getGoodsCommissionAmount();

        // 没有分佣的必要了
        if ($this->amount <= 0) {
            return false;
        }

        return true;
    }

    // 获取商品核算总金额
    public function getGoodsCommissionAmount()
    {
        $commissionType = $this->config->getRewardType();
        $amount = round(0, 2);
        switch ($commissionType) {
            case 'pay_price':
                $amount = $this->item['pay_fee'];
                break;
            case 'goods_price':
                $amount = round($this->item['goods_price'] * $this->item['goods_num'], 2);
                break;
        }
        return $amount;
    }

    /**
     * 创建分销订单
     */
    public function createCommissionOrder()
    {
        $agentId = $this->selfBuy ? $this->buyer->user->id : $this->buyer->getParentUserId();
        if (!$agentId) {
            return false;
        }
        $commissionOrder = OrderModel::where('order_item_id', $this->item['id'])->find();

        // 已添加过分销订单
        if ($commissionOrder) {
            return $commissionOrder;
        }

        $commissionOrderParams = [
            'self_buy' => intval($this->selfBuy),
            'order_id' => $this->item['order_id'],
            'order_item_id' => $this->item['id'],
            'buyer_id' => $this->buyer->user->id,
            'goods_id' => $this->item['goods_id'],
            'agent_id' => $agentId,
            'amount' => $this->amount,
            'reward_type' => $this->config->getRewardType(),
            'commission_rules' => $this->goods->getCommissionRules(),  // 记录当前设置的分佣规则，防止以后系统或者分销商设置有变导致更改历史规则
            'reward_event' => $this->config->getRewardEvent(),
            'commission_order_status' => $this->goods->commissionGoods->commission_order_status,  // 是否计入分销业绩
            'commission_reward_status' => RewardModel::COMMISSION_REWARD_STATUS_PENDING,  // 佣金状态
        ];

        $commissionOrder = OrderModel::create($commissionOrderParams);

        // 添加分销商推广订单业绩
        $orderAgent = new Agent($commissionOrder->agent_id);
        if ($orderAgent->isAgentAvaliable() && $commissionOrder->commission_order_status) {
            if($this->selfBuy) {
                // 添加自购业绩
                $orderAgent->agent->setInc('child_order_money_0', $commissionOrder->amount);
                $orderAgent->agent->setInc('child_order_count_0', 1);
            }else {
                // 添加一级业绩
                $orderAgent->agent->setInc('child_order_money_1', $commissionOrder->amount);
                $orderAgent->agent->setInc('child_order_count_1', 1);
            }
            LogModel::add($commissionOrder['agent_id'], 'order', [
                'type' => 'paid',
                'order' => $commissionOrder,
                'item' => $this->item,
                'buyer' => $this->buyer->user
            ], $this->buyer->user);
        }

        return $commissionOrder;
    }

    /**
     * 执行分销计划,递归往上分佣
     * 
     * @param object    $commissionOrder           分销订单 
     * @param object    $currentAgent              当前待分佣的分销商 默认自购开始算
     * @param int       $currentCommissionLevel    当前分佣层级 一级（自购）开始算
     */
    public function runCommissionPlan($commissionOrder, $currentAgent = null, $currentCommissionLevel = 1)
    {
        // 超出分佣层级
        if ($this->commissionLevel < $currentCommissionLevel) {
            return true;
        }
        // 当前层级为1且分销订单是自购订单 则当前分销商为购买人自己
        if ($currentCommissionLevel === 1) {
            $currentAgent = new Agent($commissionOrder->agent_id);
        }
        if ($currentAgent && !empty($currentAgent->user)) {
            // 防止重复添加佣金
            $commissionReward = RewardModel::where([
                'commission_order_id' => $commissionOrder->id,
                'agent_id' => $currentAgent->user->id,
                'commission_level' => $currentCommissionLevel,   // 分佣层级
            ])->find();

            if (!$commissionReward) {
                $currentAgentLevel = $currentAgent->getAgentLevel();
                $currentCommissionLevelRule = $this->goods->getCommissionLevelRule($currentAgentLevel, $currentCommissionLevel);
                if ($currentCommissionLevelRule) {
                    $commission = $this->goods->caculateGoodsCommission($currentCommissionLevelRule, $this->amount, $this->item['goods_num']);
                    if ($commission > 0) {
                        $commissionRewardParams = [
                            'order_id' => $commissionOrder->order_id,
                            'order_item_id' => $commissionOrder->order_item_id,
                            'buyer_id' => $commissionOrder->buyer_id,      // 购买人
                            'commission_order_id' => $commissionOrder->id,   // 分销订单ID
                            'agent_id' => $currentAgent->user->id,           // 待分佣分销商ID
                            'type' => 'commission',                               // 奖金类型
                            'commission' => $commission,                     // 佣金
                            'status' => 0,                              // 佣金状态
                            'original_commission' => $commission,            // 原始佣金
                            'commission_level' => $currentCommissionLevel,   // 分佣层级
                            'commission_rules' => $currentCommissionLevelRule,   // 分佣层级
                            'agent_level' => $currentAgentLevel              // 分佣时分销商等级
                        ];
                        $commissionReward = RewardModel::create($commissionRewardParams);
                        LogModel::add($commissionReward['agent_id'], 'reward', [
                            'type' => 'paid',
                            'reward' => $commissionReward,
                        ], $this->buyer->user);
                    }
                }
            }

            // 递归执行下一次
            $currentCommissionLevel++;
            // 超出分销层级
            if ($this->commissionLevel < $currentCommissionLevel) {
                return true;
            }
            $parentUserId = $currentAgent->getParentUserId();
            // 执行下一级分销任务
            if ($parentUserId) {
                $parentAgent = new Agent($parentUserId);
                $this->runCommissionPlan($commissionOrder, $parentAgent, $currentCommissionLevel);
            } else {
                return true;
            }
        }
    }
}
