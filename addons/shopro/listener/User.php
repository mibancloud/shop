<?php

namespace addons\shopro\listener;

use app\admin\model\shopro\user\User as UserModel;

class User
{

    // 用户资金变动
    public function userWalletChange($payload)
    {
        $walletLog = $payload['walletLog'];
        $type = $payload['type'];

        // 通知用户账户变动
        $user = UserModel::where('id', $walletLog['user_id'])->find();
        switch ($type) {
            case 'money':
                $class_name = \addons\shopro\notification\wallet\MoneyChange::class;
                break;
            case 'score':
                $class_name = \addons\shopro\notification\wallet\ScoreChange::class;
                break;
            case 'commission':
                $class_name = \addons\shopro\notification\wallet\CommissionChange::class;
                break;
        }
        $user && $user->notify(
            new $class_name([
                'walletLog' => $walletLog,
                'type' => $type,
            ])
        );
    }

}
