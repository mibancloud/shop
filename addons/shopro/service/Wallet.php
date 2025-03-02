<?php

namespace addons\shopro\service;

use think\exception\HttpResponseException;
use app\admin\model\shopro\user\User as UserModel;
use app\admin\model\shopro\user\WalletLog;
use addons\shopro\library\Operator;
use app\common\model\MoneyLog;
use app\common\model\ScoreLog;

class Wallet
{
    /**
     * @name 变更会员资金
     * @param  int|object   $user       会员对象或会员ID
     * @param  string       $type       变更类型:money=余额,commission=佣金,score=积分
     * @param  float|string        $amount     变更数量
     * @param  array        $ext        扩展字段
     * @param  string       $memo       备注
     * @return boolean
     */
    public static function change($user, $type, $amount, $event, $ext = [], $memo = '')
    {
        // 判断用户
        if (is_numeric($user)) {
            $user = UserModel::getById($user);
        }
        if (!$user) {
            error_stop('未找到用户');
        }
        // 判断金额
        if ($amount == 0) {
            error_stop('请输入正确的金额');
        }
        if (!in_array($type, ['money', 'score', 'commission'])) {
            error_stop('参数错误');
        }

        $before = $user->$type;
        $after = bcadd((string)$user->$type, (string)$amount, 2);
        // 只有后台扣除用户余额、扣除用户积分、佣金退回，钱包才可以是负值
        if ($after < 0 && !in_array($event, ['admin_recharge', 'reward_back'])) {
            $walletTypeText = WalletLog::TYPE_MAP[$type];
            error_stop("可用{$walletTypeText}不足");
        }
        try {
            // 更新会员余额信息
            $user->setInc($type, $amount);
            // 获取操作人
            $oper = Operator::get();
            // 写入日志
            $walletLog = WalletLog::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => $type,
                'before' => $before,
                'after' => $after,
                'event' => $event,
                'memo' => $memo,
                'ext' => $ext,
                'oper_type' => $oper['type'],
                'oper_id' => $oper['id']
            ]);

            // 钱包和积分记录存到 fastadmin 钱包积分记录表
            if (in_array($type, ['money', 'score'])) {
                $eventMap = (new WalletLog)->getEventMap();
                $memo = $memo ?: $eventMap[$type][$event] ?? '';
                if ($type === 'money') {
                    MoneyLog::create(['user_id' => $user->id, 'money' => $amount, 'before' => $before, 'after' => $after, 'memo' => $memo]);
                } else if ($type === 'score') {
                    ScoreLog::create(['user_id' => $user->id, 'score' => $amount, 'before' => $before, 'after' => $after, 'memo' => $memo]);
                }
            }

            // 账户变动事件
            $data = ['walletLog' => $walletLog, 'type' => $type];
            \think\Hook::listen('user_wallet_change', $data);
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            error_stop($message);
        } catch (\Exception $e) {
            error_stop('您提交的数据不正确');
        }
        return true;
    }
}
