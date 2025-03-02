<?php

namespace app\admin\model\shopro\commission;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\user\User;

class Agent extends Common
{
    protected $pk = 'user_id';

    protected $name = 'shopro_commission_agent';

    protected $type = [
        'become_time' => 'timestamp',
        'apply_info' => 'json',
        'child_agent_level_1' => 'json',
        'child_agent_level_all' => 'json',
    ];
    protected $append = [
        'status_text',
        'pending_reward'
    ];

    // 分销商状态 AGENT_STATUS
    const AGENT_STATUS_NORMAL = 'normal';       // 正常 
    const AGENT_STATUS_PENDING = 'pending';     // 审核中 不分佣、不打款、没有团队信息
    const AGENT_STATUS_FREEZE = 'freeze';       // 冻结 正常记录分佣、不打款，记录业绩和团队信息 冻结解除后立即打款
    const AGENT_STATUS_FORBIDDEN = 'forbidden'; // 禁用 不分佣、不记录业绩和团队信息
    const AGENT_STATUS_NEEDINFO = 'needinfo';   // 需要完善表单资料 临时状态
    const AGENT_STATUS_REJECT = 'reject';       // 审核驳回, 重新修改   临时状态
    const AGENT_STATUS_NULL = NULL;             // 未满足成为分销商条件


    // 分销商升级锁 UPGRADE_LOCK
    const UPGRADE_LOCK_OPEN = 1;  // 禁止分销商升级
    const UPGRADE_LOCK_CLOSE = 0;  // 允许分销商升级

    public function statusList()
    {
        return [
            'normal' => '正常',
            'pending' => '审核中',
            'freeze' => '冻结',
            'forbidden' => '禁用',
            'reject' => '拒绝'
        ];
    }

    /**
     * 可用分销商
     */
    public function scopeAvaliable($query)
    {
        return $query->where('status', 'in', [self::AGENT_STATUS_NORMAL, self::AGENT_STATUS_FREEZE]);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->field('id, nickname, avatar, mobile, total_consume, parent_user_id');
    }

    public function levelInfo()
    {
        return $this->belongsTo(Level::class, 'level', 'level')->field(['level', 'name', 'image', 'commission_rules']);
    }

    public function getPendingRewardAttr($value, $data)
    {
        $amount = Reward::pending()->where('agent_id', $data['user_id'])->sum('commission');
        return number_format($amount, 2, '.', '');
    }

    public function levelStatusInfo()
    {
        return $this->belongsTo(Level::class, 'level_status', 'level');
    }

    public function upgradeLevel()
    {
        return $this->belongsTo(Level::class, 'level_status', 'level');
    }
}
