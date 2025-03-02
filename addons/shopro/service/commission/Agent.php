<?php

namespace addons\shopro\service\commission;

use addons\shopro\service\commission\Config;
use app\admin\model\shopro\user\User as UserModel;
use app\admin\model\shopro\commission\Agent as AgentModel;
use app\admin\model\shopro\commission\Level as LevelModel;
use app\admin\model\shopro\commission\Log as LogModel;
use app\admin\model\shopro\Share as ShareModel;

/**
 * 分销商业务
 */
class Agent
{

    public $user;     // 商城用户
    public $agent;    // 分销商
    public $config;    // 分销设置
    public $parentUserId;
    public $nextAgentTeam;
    public $nextUserTeam;


    /**
     * 构造函数
     * 
     * @param mixed      $user     用户ID/用户对象
     */
    public function __construct($user)
    {
        if (is_numeric($user)) {
            $this->user = UserModel::get($user);
        } else {
            $this->user = UserModel::get($user->id);
        }
        if (!empty($this->user->id)) {
            $this->agent = AgentModel::with(['level_info'])->find($this->user->id);
        }

        $this->config = new Config();
    }

    /**
     * 获取分销商实时状态
     */
    public function getAgentStatus($autoCreate = false)
    {
        if (empty($this->agent)) {
            // 自动创建分销商  
            if ($autoCreate) {
                return $this->createNewAgent();
            }
            return NULL;
        }
        return $this->agent->status;
    }

    /**
     * 获取分销商可参与状态 正常和冻结都可正常浏览并统计业绩
     */
    public function isAgentAvaliable()
    {
        $status = $this->getAgentStatus();
        if (in_array($status, [AgentModel::AGENT_STATUS_NORMAL, AgentModel::AGENT_STATUS_FREEZE])) {
            return true;
        }
        return false;
    }

    /**
     * 获取分销商等级
     */
    public function getAgentLevel()
    {
        if (empty($this->agent)) {
            return 0;
        }
        if (empty($this->agent->level_info)) {
            return 1;
        }
        return $this->agent->level_info->level;
    }

    /**
     * 分销商升级是否锁定
     */
    public function getAgentUpgradeLock()
    {
        if (empty($this->agent)) {
            return true;
        }
        if ($this->agent->upgrade_lock == AgentModel::UPGRADE_LOCK_OPEN) {
            return true;
        }

        return false;
    }

    /**
     * 实时获取上级推荐人
     */
    public function getParentUserId()
    {
        if (empty($this->parentUserId)) {

            $this->parentUserId = 0;

            $parent_user_id = $this->user->parent_user_id;
            // 未直接绑定分销商,从分享记录查找最近的分销商
            if ($parent_user_id === NULL) {
                $shareLog = ShareModel::hasWhere(
                    'agent',
                    function ($query) {
                        return $query->where('status', 'in', [AgentModel::AGENT_STATUS_NORMAL, AgentModel::AGENT_STATUS_FREEZE]);
                    }
                )->where('Share.user_id', $this->user->id)->order('id desc')->find();

                if ($shareLog) {
                    $parent_user_id = $shareLog['share_id'];
                }
            }
            // 再次检查上级分销商是否可用
            if ($parent_user_id > 0) {
                $parentUser = UserModel::where('id', $parent_user_id)->find();
                $parentAgent = AgentModel::avaliable()->where(['user_id' => $parent_user_id])->find();
                if ($parentUser && $parentAgent) {
                    $this->parentUserId = $parentAgent->user_id;
                }
            }
        }

        return $this->parentUserId;
    }

    /**
     * 创建分销商
     */
    public function createNewAgent($event = '', $applyInfo = [])
    {
        // 已经是分销商
        if (!empty($this->agent)) {
            return $this->getAgentStatus();
        }

        $agentStatus = AgentModel::AGENT_STATUS_NULL;

        $condition = $this->config->getBecomeAgentEvent();

        $check = false;  // 是否满足条件
        $needAgentApplyForm = $this->config->isAgentApplyForm();

        if ($event !== '' && $condition['type'] !== $event) {
            return $agentStatus;
        }

        switch ($condition['type']) {
            case 'apply':   // 直接自助申请
                $check = true;
                $needAgentApplyForm = true;
                break;
            case 'goods':   // 需购买指定产品
                $isBuy = \app\admin\model\shopro\order\Order::hasWhere('items', function ($query) use ($condition) {
                    return $query->where('goods_id', 'in', $condition['value'])->where('refund_status', 0);
                })->where('Order.user_id', $this->user->id)->paid()->find();
                if ($isBuy) $check = true;
                break;
            case 'consume': // 消费累计
                if ($this->user->total_consume >= $condition['value']) {
                    $check = true;
                }
                break;
            case 'user': // 新会员注册
                $check = true;
                $needAgentApplyForm = false;
                break;
        }

        // 可以成为分销商 检查系统设置
        if ($check) {
            // 需后台审核
            if ($this->config->isAgentCheck()) {
                $agentStatus = AgentModel::AGENT_STATUS_PENDING;
            } else {
                $agentStatus = AgentModel::AGENT_STATUS_NORMAL;
            }
            // 需要提交资料
            if ($needAgentApplyForm && empty($applyInfo)) {
                $agentStatus = AgentModel::AGENT_STATUS_NEEDINFO;  // 需要主动提交资料,暂时不加分销商信息
            }
        }

        // 可以直接添加分销商信息
        if ($agentStatus === AgentModel::AGENT_STATUS_NORMAL || $agentStatus === AgentModel::AGENT_STATUS_PENDING) {
            AgentModel::create([
                'user_id' => $this->user->id,
                'level' => 1,  // 默认分销商等级
                'status' => $agentStatus,
                'apply_info' => $applyInfo,
                'apply_num' => 1,
                'become_time' => time()
            ]);

            // 绑定上级推荐人
            if ($this->user->parent_user_id === NULL) {
                if ($this->bindUserRelation('agent') && $agentStatus !== AgentModel::AGENT_STATUS_NORMAL) {
                    $this->createAsyncAgentUpgrade($this->user->id);    // 防止真正成为分销商时重新触发升级任务造成冗余
                }
            }
            // 绑定为平台直推
            if ($this->user->parent_user_id === NULL) {
                $this->user->parent_user_id = 0;
                $this->user->save();
            }

            $this->agent = AgentModel::with(['level_info'])->find($this->user->id);

            // 添加分销商状态记录
            LogModel::add($this->user->id, 'agent', ['type' => 'status', 'value' => $agentStatus]);

            // 统计分销层级单链业绩
            if ($agentStatus === AgentModel::AGENT_STATUS_NORMAL) {
                $this->createAsyncAgentUpgrade($this->user->id);
            }
        }
        return $agentStatus;
    }

    /**
     * 绑定用户关系
     * 
     * @param string   $event          事件标识(share=点击分享链接, pay=首次支付, agent=成为子分销商)
     * @param int      $bindAgentId    可指定需绑定的分销商用户ID 默认从分享记录中去查
     */
    public function bindUserRelation($event, $bindAgentId = NULL)
    {
        $bindCheck = false;   // 默认不绑定

        // 该用户已经有上级
        if ($this->user->parent_user_id !== NULL) {
            return false;
        }

        // 不满足绑定下级事件
        if ($this->config->getInviteLockEvent() !== $event) {
            return false;
        }

        switch ($this->config->getInviteLockEvent()) {
            case 'share':
                $bindCheck = true;
                break;
            case 'pay':
                if ($this->user->total_consume > 0) {
                    $bindCheck = true;
                }
                break;
            case 'agent':
                $bindCheck = true;
                break;
        }

        if (!$bindCheck) {
            return false;
        }

        if ($bindAgentId === NULL) {
            $bindAgentId = $this->getParentUserId();
        }

        if (!$bindAgentId) {
            return false;
        }

        $bindAgent = new Agent($bindAgentId);

        if (!$bindAgent->isAgentAvaliable()) {
            return false;
        }

        // 允许绑定用户
        $this->user->parent_user_id = $bindAgent->user->id;
        $this->user->save();

        // 添加推广记录
        LogModel::add($bindAgent->user->id, 'share', ['user' => $this->user]);
        return true;
    }


    /**
     * 创建[分销商升级&统计业绩]异步队列任务
     * 为了防止计算量大而引起阻塞,使用异步递归
     */
    public function createAsyncAgentUpgrade($user_id = 0)
    {
        if ($user_id === 0) {
            $user_id = $this->user->id;
        }
        \think\Queue::push('\addons\shopro\job\Commission@agentUpgrade', [
            'user_id' => $user_id
        ], 'shopro');
    }

    /**
     * 执行用户统计、分销商信息统计、分销商等级升级计划 (递归往上升级)
     * 
     * @param bool      $upgrade                   执行分销商等级升级
     */
    public function runAgentUpgradePlan($upgrade = true)
    {
        if ($this->isAgentAvaliable()) {
            // 获取下级直推团队用户信息
            $nextUserTeam = $this->getNextUserTeam();

            $nextAgentTeam = $this->getNextAgentTeam();

            // 一级用户人数
            $this->agent->child_user_count_1 = count($nextUserTeam);

            // 二级用户人数 = 一级分销商的一级用户人数
            $this->agent->child_user_count_2 = array_sum(array_column($nextAgentTeam, 'child_user_count_1'));

            // 团队用户人数 = 一级用户人数 + 一级用户的团队用户人数
            $this->agent->child_user_count_all = $this->agent->child_user_count_1 + array_sum(array_column($nextAgentTeam, 'child_user_count_all'));

            // 一级分销商人数
            $this->agent->child_agent_count_1 = count($nextAgentTeam);

            // 二级分销商人数 = 一级分销商的一级分销商人数
            $this->agent->child_agent_count_2 = array_sum(array_column($nextAgentTeam, 'child_agent_count_1'));

            // 团队分销商人数 = 一级分销商人数 + 一级分销商的团队分销商人数
            $this->agent->child_agent_count_all = $this->agent->child_agent_count_1 + array_sum(array_column($nextAgentTeam, 'child_agent_count_all'));

            // 二级分销订单金额 = 一级分销商的一级分销订单金额 + 一级分销商的自购订单金额
            $this->agent->child_order_money_2 = array_sum(array_column($nextAgentTeam, 'child_order_money_1')) + array_sum(array_column($nextAgentTeam, 'child_order_money_0'));

            // 团队分销订单金额 = 自购分销订单金额 + 一级分销订单金额 + 一级所有分销商的团队分销订单总金额
            $this->agent->child_order_money_all = $this->agent->child_order_money_0 + $this->agent->child_order_money_1 + array_sum(array_column($nextAgentTeam, 'child_order_money_all'));

            // 二级分销订单数量 = 一级分销商的一级分销订单数量 + 一级分销商的自购订单数量
            $this->agent->child_order_count_2 = array_sum(array_column($nextAgentTeam, 'child_order_count_1')) + array_sum(array_column($nextAgentTeam, 'child_order_count_0'));

            // 团队分销订单数量 = 自购分销订单数量 + 一级分销订单数量 + 一级所有分销商的团队分销订单总数量
            $this->agent->child_order_count_all = $this->agent->child_order_count_0 + $this->agent->child_order_count_1 + array_sum(array_column($nextAgentTeam, 'child_order_count_all'));

            // 一级分销商等级统计
            $child_agent_level_1 = array_count_values(array_column($nextAgentTeam, 'level'));
            ksort($child_agent_level_1);
            $this->agent->child_agent_level_1 = $child_agent_level_1;

            // 团队分销商等级统计 = 一级分销商等级 + 一级分销商的团队分销商等级
            $child_agent_level_all = $this->childAgentLevelCount(array_column($nextAgentTeam, 'child_agent_level_all'), $this->agent->child_agent_level_1);
            ksort($child_agent_level_all);
            $this->agent->child_agent_level_all = $child_agent_level_all;

            $this->agent->save();

            // 分销商自动升级
            if (!$this->getAgentUpgradeLock() && $upgrade) {
                $canUpgradeLevel = $this->checkAgentUpgradeLevel();
                if ($canUpgradeLevel) {
                    if ($this->config->isUpgradeCheck()) {
                        $this->agent->level_status = $canUpgradeLevel;
                    } else {
                        $this->agent->level = $canUpgradeLevel;
                        LogModel::add($this->user->id, 'agent', ['type' => 'level', 'level' =>  LevelModel::find($canUpgradeLevel)]);
                    }
                    $this->agent->save();
                }
            }
            \think\Log::info('统计分销商业绩[ID=' . $this->user->id . '&nickname=' . $this->user->nickname . '] ---Ended');
        }

        $parentUserId = $this->getParentUserId();
        if ($parentUserId) {
            $this->createAsyncAgentUpgrade($parentUserId);
        }
    }

    /**
     * 统计团队分销商等级排布
     */
    private function childAgentLevelCount($childAgentLevelArray, $childAgentLevel1Array)
    {
        $childAgentLevelCount = [];
        foreach ($childAgentLevelArray as &$agentLevel) {
            if (!empty($agentLevel)) {
                $agentLevel = json_decode($agentLevel, true);
                array_walk($agentLevel, function ($count, $level) use (&$childAgentLevelCount) {
                    if (isset($childAgentLevelCount[$level])) {
                        $childAgentLevelCount[$level] += $count;
                    } else {
                        $childAgentLevelCount[$level] = $count;
                    }
                });
            }
        }
        array_walk($childAgentLevel1Array, function ($count, $level) use (&$childAgentLevelCount) {
            if (isset($childAgentLevelCount[$level])) {
                $childAgentLevelCount[$level] += $count;
            } else {
                $childAgentLevelCount[$level] = $count;
            }
        });
        return $childAgentLevelCount;
    }

    /**
     * 获取下级分销商团队
     */
    public function getNextAgentTeam()
    {
        if (!$this->isAgentAvaliable()) {
            return [];
        }
        if (empty($this->nextAgentTeam)) {
            $this->nextAgentTeam = AgentModel::hasWhere('user', function ($query) {
                return $query->where('parent_user_id', $this->user->id);
            })->column('user_id, Agent.level, child_user_count_1, child_user_count_all,child_agent_count_1, child_agent_count_all, child_order_money_0, child_order_money_1, child_order_money_all, child_order_count_0, child_order_count_1, child_order_count_all, child_agent_level_1, child_agent_level_all');
        }
        return $this->nextAgentTeam;
    }

    /**
     * 获取下级直推团队用户
     */
    public function getNextUserTeam()
    {
        if (!$this->isAgentAvaliable()) {
            return [];
        }
        if (empty($this->nextUserTeam)) {
            $this->nextUserTeam = UserModel::where(['parent_user_id' => $this->user->id, 'status' => 'normal'])->column('id');
        }
        return $this->nextUserTeam;
    }

    /**
     * 获取可升级的分销商等级
     */
    private function getNextAgentLevel()
    {
        $nextAgentLevel = [];
        $agentLevel = $this->getAgentLevel();
        if ($agentLevel) {
            $nextAgentLevel = LevelModel::where('level', '>', $agentLevel)->order('level asc')->select();
        }
        return $nextAgentLevel;
    }

    /**
     * 比对当前分销商条件是否满足升级规则
     */
    private function checkAgentUpgradeLevel()
    {
        $nextAgentLevel = $this->getNextAgentLevel();

        if (count($nextAgentLevel)) {
            foreach ($nextAgentLevel as $level) {
                $checkLevel[$level->level] = $this->isMatchUpgradeLevelRule($level);
                // 不允许越级升级
                if (!$this->config->isUpgradeJump()) break;
            }
            $checkLevel = array_reverse($checkLevel, true);
            $canUpgradeLevel = array_search(true, $checkLevel);
            if ($canUpgradeLevel) {
                return $canUpgradeLevel;
            }
        }
        return 0;
    }

    /**
     * 分销商升级规则检查
     */
    public function isMatchUpgradeLevelRule($level)
    {
        foreach ($level->upgrade_rules as $name => $value) {
            $match[$name] = false;
            switch ($name) {
                case 'total_consume':   // 用户消费金额
                    $match[$name] = $this->user->$name >= $value;
                    break;

                case 'child_user_count_all':    // 团队用户人数
                case 'child_user_count_1':  // 一级用户人数
                case 'child_user_count_2':  // 二级用户人数
                case 'child_order_money_0': // 自购分销订单金额
                case 'child_order_money_1': // 一级分销订单金额
                case 'child_order_money_2': // 二级分销订单金额
                case 'child_order_money_all': // 团队分销订单金额

                case 'child_order_count_0': // 自购分销订单数量
                case 'child_order_count_1': // 一级分销订单数量
                case 'child_order_count_2': // 二级分销订单数量
                case 'child_order_count_all': // 团队分销订单数量

                case 'child_agent_count_1': // 一级分销商人数
                case 'child_agent_count_2': // 二级分销商人数
                case 'child_agent_count_all': // 团队分销商人数
                    $match[$name] = $this->agent->$name >= $value;
                    break;

                case 'child_agent_level_1': // 一级分销商等级统计
                case 'child_agent_level_all':   // 团队分销商等级统计
                    $match[$name] = true;
                    if (count($value) > 0) {
                        if (empty($this->agent->$name)) {
                            $match[$name] = false;
                        } else {
                            foreach ($value as $k => $row) {
                                if (!isset(($this->agent->$name)[$row['level']]) || ($this->agent->$name)[$row['level']] < $row['count']) {
                                    $match[$name] = false;
                                    break;
                                }
                            }
                        }
                    }
                    break;
            }

            // ①满足任意一种条件:只要有一种符合立即返回可以升级状态
            if (!$level->upgrade_type && $match[$name]) {
                return true;
                break;
            }

            // ②满足所有条件:不满足任意一种条件立即返回不可升级状态
            if ($level->upgrade_type && !$match[$name]) {
                return false;
                break;
            }
        }

        // 循环完所有的 如果是①的情况则代表都不符合条件，如果是②则代表都符合条件 返回对应状态即可
        return boolval($level->upgrade_type);
    }
}
