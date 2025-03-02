<?php

namespace app\admin\model\shopro\commission;

use app\admin\model\shopro\Common;
use addons\shopro\library\Operator;
use app\admin\model\shopro\user\User as UserModel;

class Log extends Common
{
    protected $name = 'shopro_commission_log';

    protected $updateTime = false;

    protected $append = [
        'event_text',
        'oper_type_text'
    ];

    /**
     * 添加分销记录
     * 
     * @param object      $agentId   分销商ID
     * @param string      $event     事件类型
     * @param array       $ext       扩展信息
     * @param object      $oper      操作人
     * @param string      $remark    自定义备注
     * 
     */
    public static function add($agentId, $event, $ext = [], $oper = NULL, $remark = '')
    {
        if ($remark === '') {
            switch ($event) {
                case 'agent':
                    $remark = self::setAgentEvent($ext);
                    break;
                case 'share':
                    $remark = self::setShareEvent($ext);
                    break;
                case 'bind':
                    $remark = self::setBindEvent($ext);
                    break;
                case 'order':
                    $remark = self::setOrderEvent($ext);
                    break;
                case 'reward':
                    $remark = self::setRewardEvent($ext);
                    break;
            }
        }
        if ($remark !== '') {
            $oper = Operator::get($oper);
            $log = [
                'agent_id' => $agentId,
                'event' => $event,
                'remark' => $remark,
                'oper_type' => $oper['type'],
                'oper_id' => $oper['id'],
                'createtime' => time()
            ];
            return self::create($log);
        }
        return NULL;
    }

    public static function setAgentEvent($ext)
    {
        switch ($ext['type']) {
            case 'status':  // 变更状态
                switch ($ext['value']) {
                    case Agent::AGENT_STATUS_PENDING:
                        $remark = "您的资料已提交,等待管理员审核";
                        break;
                    case Agent::AGENT_STATUS_FORBIDDEN:
                        $remark = "您的账户已被禁用";
                        break;
                    case Agent::AGENT_STATUS_NORMAL:
                        $remark = "恭喜您成为分销商";
                        break;
                    case Agent::AGENT_STATUS_FREEZE:
                        $remark = "您的账户已被冻结";
                        break;
                    case Agent::AGENT_STATUS_REJECT:
                        $remark = "您的申请已被拒绝,请重新申请";
                        break;
                }
                break;
            case 'level': // 变更等级
                $remark = "您的等级已变更为[{$ext['level']['name']}]";
                break;
            case 'apply_info':
                $remark = '您的分销商资料信息已更新';
                break;
        }
        return $remark ?? "";
    }

    public static function setShareEvent($ext)
    {
        $remark = "您已成为用户[{$ext['user']['nickname']}]的推荐人";
        return $remark;
    }

    public static function setBindEvent($ext)
    {
        $remark = "";
        if ($ext['user']) {
            $remark = "用户[{$ext['user']['nickname']}]已绑定为您的推荐人";
        }
        return $remark;
    }

    public static function setOrderEvent($ext)
    {
        switch ($ext['type']) {
            case 'paid':
                $goodsName = $ext['item']['goods_title'];
                if (mb_strlen($goodsName) > 9) {
                    $goodsName = mb_substr($goodsName, 0, 5) . '...' . mb_substr($goodsName, -3);
                }
                if ($ext['order']['self_buy'] == 1) {
                    $remark = "您购买了{$goodsName},为您新增业绩{$ext['order']['amount']}元, +1分销订单";
                } else {
                    $remark = "用户{$ext['buyer']['nickname']}购买了{$goodsName},为您新增业绩{$ext['order']['amount']}元, +1分销订单";
                }
                break;
            case 'refund':
                $remark = "用户{$ext['buyer']['nickname']}已退款,扣除业绩{$ext['order']['amount']}元, -1分销订单";
                break;
            case 'admin':
                $remark = "扣除业绩{$ext['order']['amount']}元, -1分销订单";
                break;
        }
        return $remark;
    }

    public static function setRewardEvent($ext)
    {
        $actionStr = '';
        $remark = '';
        switch ($ext['type']) {
            case 'paid':
                $actionStr = '支付成功';
                break;
            case 'confirm':
                $actionStr = '已确认收货';
                break;
            case 'finish':
                $actionStr = '已完成订单';
                break;
        }
        if ($actionStr !== '') {
            $remark = "用户{$actionStr}, ";
        }
        switch ($ext['reward']['status']) {
            case Reward::COMMISSION_REWARD_STATUS_PENDING:
                $rewardStatus = '待入账';
                break;
            case Reward::COMMISSION_REWARD_STATUS_ACCOUNTED:
                $rewardStatus = '已入账';
                break;
            case Reward::COMMISSION_REWARD_STATUS_BACK:
                $rewardStatus = '已扣除';
                break;
            case Reward::COMMISSION_REWARD_STATUS_CANCEL:
                $rewardStatus = '已取消';
                break;
        }
        $remark .= "您有{$ext['reward']['commission']}元佣金{$rewardStatus}";

        return $remark;
    }


    public function eventList()
    {
        return [
            'agent' => '分销商',
            'order' => '订单',
            'reward' => '佣金',
            'share' => '推荐',
            'bind' => '绑定',
        ];
    }


    public function operTypeList()
    {
        return [
            'user' => '用户',
            'admin' => '管理员',
            'system' => '系统',
        ];
    }


    /**
     * 事件类型
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getEventTextAttr($value, $data)
    {
        $value = $value ?: ($data['event'] ?? null);

        $list = $this->eventList();
        return isset($list[$value]) ? $list[$value] : '-';
    }


    /**
     * 操作人类型
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getOperTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['oper_type'] ?? null);

        $list = $this->operTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function agent()
    {
        return $this->belongsTo(UserModel::class, 'agent_id', 'id')->field('id, username, nickname, avatar, gender');
    }
}
