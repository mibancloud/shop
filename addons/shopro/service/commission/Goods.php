<?php

namespace addons\shopro\service\commission;


use app\admin\model\shopro\commission\Level as LevelModel;
use app\admin\model\shopro\commission\CommissionGoods as CommissionGoodsModel;



/**
 * 分销商品
 */
class Goods
{

    public $commissionGoods;     // 分销商品
    protected $commissionConfig = NULL;    // 独立分销设置 默认无
    protected $commissionRules;     // 分销规则
    protected $skuPriceId;          // 商品规格ID

    /**
     * 获取商品实时分佣规则
     * 
     * @param int $goods   商品
     * @param int $skuId   商品规格ID
     */
    public function __construct($goods, $skuPriceId = 0)
    {
        $commissionRules = CommissionGoodsModel::GOODS_COMMISSION_STATUS_OFF;

        if(is_numeric($goods)) {
            $this->commissionGoods = CommissionGoodsModel::where(['goods_id' => $goods, 'status' => CommissionGoodsModel::GOODS_COMMISSION_STATUS_ON])->find();
        }else {
            $this->commissionGoods = $goods;
        }
        $this->skuPriceId = $skuPriceId;

        if ($this->commissionGoods) {
            $commission_config = $this->commissionGoods->commission_config;
            if ($commission_config['status']) {
                $this->commissionConfig = $commission_config;
            }
            switch ($this->commissionGoods->self_rules) {
                    // 默认分销规则
                case CommissionGoodsModel::GOODS_COMMISSION_RULES_DEFAULT:
                    $commissionRules = $this->getDefaultCommissionRules();
                    break;

                    // 独立分销规则
                case CommissionGoodsModel::GOODS_COMMISSION_RULES_SELF:
                    $commissionRules = $this->getSelfCommissionRules();
                    break;

                    // 批量分销规则
                case CommissionGoodsModel::GOODS_COMMISSION_RULES_BATCH:
                    $commissionRules = $this->getBatchCommissionRules();
                    break;
            }
        }
        $this->commissionRules = $commissionRules;
    }

    public function getCommissionConfig()
    {
        return $this->commissionConfig;
    }

    public function getCommissionRules()
    {
        return $this->commissionRules;
    }

    /**
     * 获取对应分销商等级、对应层级的商品佣金规则
     * 
     * @param int $agentLevel      分销商等级(不是id)
     * @param int $commissionLevel 分销商层级(默认一级)
     */
    public function getCommissionLevelRule($agentLevel, $commissionLevel = 1)
    {
        if (isset($this->commissionRules[$agentLevel]) && isset($this->commissionRules[$agentLevel][$commissionLevel])) {
            $commissionRule = $this->commissionRules[$agentLevel][$commissionLevel];
            return $commissionRule;
        }
        return false;
    }

    /**
     * 计算对应规则分销佣金
     * 
     * @param int $commissionRule      分销规则
     * @param int $amount              结算价格
     * @param int $goodsNum            购买数量
     */
    public function caculateGoodsCommission($commissionRule, $amount, $goodsNum = 1)
    {
        $commission = 0;
        if (!empty($commissionRule['rate']) && $commissionRule['rate'] > 0) {
            $commission = round($amount * $commissionRule['rate'] * 0.01, 2);
        }
        if (!empty($commissionRule['money']) && $commissionRule['money'] > 0) {
            $commission = $commissionRule['money'] * $goodsNum;
        }
        return number_format($commission, 2, '.', '');
    }

    // 获取分销商等级默认规则
    private function getDefaultCommissionRules()
    {
        $agentLevelRules = LevelModel::order('level asc')->column('commission_rules', 'level');
        $commissionRules = [];
        foreach ($agentLevelRules as $agentLevel => $rule) {
            $rule = json_decode($rule, true);
            foreach ($rule as $commission_level => $percent) {
                $commission_level = explode('_', $commission_level);
                $level = intval($commission_level[1]);
                $commissionRules[$agentLevel][$level] = ['rate' => $percent];
            }
        }
        return $commissionRules;
    }

    // 获取分销商品独立分佣规则
    private function getSelfCommissionRules()
    {
        $commissionRules = [];
        if (isset($this->commissionGoods->commission_rules[$this->skuPriceId])) {
            $commissionRules = $this->commissionGoods->commission_rules[$this->skuPriceId];
        }

        return $commissionRules;
    }


    // 获取商品批量分佣规则
    private function getBatchCommissionRules()
    {
        return $this->commissionGoods->commission_rules;
    }
}
