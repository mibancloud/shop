<?php

namespace addons\shopro\service\commission;

class Config
{
    protected $config;

    public function __construct()
    {
        $this->config = sheep_config('shop.commission');
    }

    // 覆盖默认分销设置
    public function setConfig($config)
    {
        foreach ($config as $name => $value) {
            $this->config[$name] = $value;
        }
    }

    // 获取绑定推广关系的事件节点
    public function getInviteLockEvent()
    {
        $becomeAgentEvent = $this->getBecomeAgentEvent();
        if($becomeAgentEvent === 'user') {
            return 'agent';
        }
        return $this->config['invite_lock'];
    }

    // 获取成为分销商的条件
    public function getBecomeAgentEvent()
    {
        return $this->config['become_agent'];
    }

    // 分销商是否需要审核
    public function isAgentCheck()
    {
        return boolval($this->config['agent_check']);
    }

    // 分销商升级审核
    public function isUpgradeCheck()
    {
        return boolval($this->config['upgrade_check']);
    }

    // 分销商允许越级升级
    public function isUpgradeJump()
    {
        return boolval($this->config['upgrade_jump']);
    }

    // 是否需要完善分销商表单信息
    public function isAgentApplyForm()
    {
        return boolval($this->config['agent_form']['status']);
    }

    // 获取申请资料表单信息
    public function getAgentForm()
    {
        return $this->config['agent_form'];
    }


    // 申请协议
    public function getApplyProtocol()
    {
        return $this->config['apply_protocol'];
    }

    // 分销层级
    public function getCommissionLevel()
    {
        return intval($this->config['level']);
    }

    // 是否允许分销自购
    public function isSelfBuy()
    {
        return boolval($this->config['self_buy']);
    }

    // 是否显示升级条件
    public function isUpgradeDisplay()
    {
        return boolval($this->config['upgrade_display']);
    }

    // 佣金结算价格类型 pay_price=支付金额  goods_price=商品价格 （都不含运费 因为运费对于平台和用户没有实际价值）
    public function getRewardType()
    {
        return $this->config['reward_type'];
    }

    // 佣金结算节点 payed=支付后
    public function getRewardEvent()
    {
        return $this->config['reward_event'];
    }

    // 退款是否扣除分销业绩
    public function getRefundCommissionOrder()
    {
        return boolval($this->config['refund_commission_order']);
    }

    // 退款是否扣除佣金
    public function getRefundCommissionReward()
    {
        return boolval($this->config['refund_commission_reward']);
    }
}
