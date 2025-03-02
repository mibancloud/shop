<?php

namespace addons\shopro\listener;

use addons\shopro\library\notify\Notify;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\Admin;

class OrderAftersale
{

    public function orderAftersaleChange($params)
    {
        $aftersale = $params['aftersale'];
        $order = $params['order'];
        $aftersaleLog = $params['aftersaleLog'];

        // 通知用户售后处理过程
        $user = User::where('id', $aftersale['user_id'])->find();
        $user && $user->notify(
            new \addons\shopro\notification\order\aftersale\OrderAftersaleChange([
                'aftersale' => $aftersale,
                'order' => $order,
                'aftersaleLog' => $aftersaleLog,
            ])
        );

        // 通知管理员售后变动
        $admins = collection(Admin::select())->filter(function ($admin) {
            return $admin->hasAccess($admin, [      // 售后所有权限
                'shopro/order/afersale/index',
                'shopro/order/aftersale/detail',
                'shopro/order/aftersale/completed',
                'shopro/order/aftersale/refuse',
                'shopro/order/aftersale/refund',
                'shopro/order/aftersale/addlog',
            ]);
        });
        if (!$admins->isEmpty()) {
            Notify::send(
                $admins,
                new \addons\shopro\notification\order\aftersale\OrderAdminAftersaleChange([
                    'aftersale' => $aftersale,
                    'order' => $order,
                    'aftersaleLog' => $aftersaleLog,
                ])
            );
        }
    }


    public function orderAftersaleCompleted()
    {
    }

    public function orderAftersaleRefuse()
    {
    }
}
