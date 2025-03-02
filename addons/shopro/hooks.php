<?php

$defaultHooks = [
  // 订单创建
  'order_create_before' => [       // 订单创建前
    'addons\\shopro\\listener\\Order'
  ],
  'order_create_after' => [        // 订单创建后
    'addons\\shopro\\listener\\Order'
  ],
  'order_paid_after' => [        // 订单支付成功
    'addons\\shopro\\listener\\Order'
  ],
  'order_offline_after' => [        // 订单选择线下支付(货到付款)付款
    'addons\\shopro\\listener\\Order'
  ],
  'order_offline_paid_after' => [        // 订单线下支付(货到付款)支付成功
    'addons\\shopro\\listener\\Order'
  ],

  // 订单失效
  'order_close_after' => [          // 订单关闭后
    'addons\\shopro\\listener\\Order'
  ],
  'order_cancel_after' => [         // 订单取消后
    'addons\\shopro\\listener\\Order'
  ],

  // 订单发货
  'order_dispatch_after' => [        // 订单发货后
    'addons\\shopro\\listener\\Order'
  ],
  'order_dispatch_change' => [        // 订单发货后
    'addons\\shopro\\listener\\Order'
  ],

  // 订单收货
  'order_confirm_after' => [        // 订单确认收货后
    'addons\\shopro\\listener\\Order'
  ],
  'order_confirm_finish' => [       // 订单确认收货完成
    'addons\\shopro\\listener\\Order'
  ],
  'order_refuse_after' => [       // 订单线下拒绝付款
    'addons\\shopro\\listener\\Order'
  ],

  // 订单完成事件
  'order_finish' => [
    'addons\\shopro\\listener\\Order'
  ],

  // 订单申请全额退款后
  'order_apply_refund_after' => [
    'addons\\shopro\\listener\\Order'
  ],

  // 订单评价
  'order_comment_after' => [        // 订单评价后
    'addons\\shopro\\listener\\Order'
  ],

  // 订单商品退款后
  'order_item_refund_after' => [
    'addons\\shopro\\listener\\Order'
  ],
  // 订单退款后
  'order_refund_after' => [
    'addons\\shopro\\listener\\Order'
  ],

  // 订单售后
  'order_aftersale_completed' => [        // 售后完成
    'addons\\shopro\\listener\\OrderAftersale'
  ],
  'order_aftersale_refuse' => [        // 售后完成
    'addons\\shopro\\listener\\OrderAftersale'
  ],
  'order_aftersale_change' => [        // 售后状态改变
    'addons\\shopro\\listener\\OrderAftersale'
  ],

  // 拼团
  'activity_groupon_finish' => [        // 拼团成功
    'addons\\shopro\\listener\\Activity'
  ],
  'activity_groupon_fail' => [        // 拼团失败，超时，后台手动解散等
    'addons\\shopro\\listener\\Activity'
  ],

  // 用户
  'user_wallet_change' => [           // 用户账户变动
    'addons\\shopro\\listener\\User'
  ],

  // 商品库存预警
  'goods_stock_warning' => [
    'addons\\shopro\\listener\\Goods'
  ],

  // 关注公众号
  'wechat_subscribe' => [],
  // 取消关注公众号
  'wechat_unsubscribe' => [],

  'upload_after' => [
    'addons\\shopro\listener\\Upload'
  ]
];


// -- commission code start --
// 分销相关钩子
$commissionHooks = [
  'user_register_after' => [    // 新用户注册成功
    'addons\\shopro\listener\\Commission'
  ],
  'user_share_after' => [               // 用户分享后
    'addons\\shopro\\listener\\Commission'
  ],
  'order_paid_after' => [        // 订单支付成功
    'addons\\shopro\\listener\\Commission'
  ],
  'order_offline_paid_after' => [        // 货到付款支付成功
    'addons\\shopro\\listener\\Commission'
  ],
  'order_confirm_finish' => [        // 订单确认收货后
    'addons\\shopro\\listener\\Commission'
  ],
  'order_item_refund_after' => [   // 订单商品退款后
    'addons\\shopro\\listener\\Commission'
  ],
  'order_finish' => [   // 订单完成事件
    'addons\\shopro\\listener\\Commission'
  ],
];

if (check_env('commission', false)) {
  $defaultHooks = array_merge_recursive($defaultHooks, $commissionHooks);
}

// -- commission code end --

return $defaultHooks;
