<?php

namespace addons\shopro\service\order;

use app\admin\model\shopro\order\OrderItem;
use app\admin\model\shopro\order\Order;
use addons\shopro\service\StockSale;
use addons\shopro\facade\Activity as ActivityFacade;

class OrderThrough
{

    /**
     * 商品限购
     *
     * @param [type] $buyInfo
     * @param \Closure $next
     * @return void
     */
    public function limitBuy ($buyInfo, \Closure $next) 
    {
        $user = auth_user();
        $goods = $buyInfo['goods'];
        $goods_num = $buyInfo['goods_num'] ?? 1;
        $activity = $goods['activity'];

        if ($activity) {
            // 活动限购
            $rules = $activity['rules'] ?? [];
            $limit_type = 'activity';
            $limit_num = (isset($rules['limit_num']) && $rules['limit_num'] > 0) ? $rules['limit_num'] : 0;
        } else {
            // 普通商品限购
            $limit_type = $goods->limit_type;
            $limit_num = ($limit_type != 'none' && $goods->limit_num > 0) ? $goods->limit_num : 0;
        }

        if ($limit_num) {       // limit_num = 0; 不限购
            // 查询用户老订单，判断本次下单数量，判断是否超过购买限制, 未支付的或者已完成的都算
            $buy_num = OrderItem::where('user_id', $user['id'])->where('goods_id', $goods->id)
                ->where(function ($query) use ($limit_type, $goods, $activity) {
                    if ($limit_type == 'daily') {
                        // 按天限购
                        $daily_start = strtotime(date('Y-m-d'));
                        $daily_end = strtotime(date('Y-m-d', (time() + 86400))) - 1;
                        $query->where('createtime', 'between', [$daily_start, $daily_end]);
                    } else if ($limit_type == 'activity') {
                        $query->where('activity_id', $activity['id']);      // 这个活动下所有的购买记录
                    } else {
                        // all，不加任何条件
                    }
    
                    return $query;
                })
                ->whereExists(function ($query) use ($goods) {
                    $order_table_name = (new Order())->getQuery()->getTable();
                    $query->table($order_table_name)->where('order_id=' . $order_table_name . '.id')
                        ->whereNotIn('status', [Order::STATUS_CLOSED, Order::STATUS_CANCEL]);       // 除了交易关闭，和 取消的订单
                })->sum('goods_num');

            if (($buy_num + $goods_num) > $limit_num) {
                $msg = '该商品' . ($limit_type == 'daily' ? '每日' : ($limit_type == 'activity' ? '活动期间' : '')) . '限购 ' . $limit_num . ' 件';
    
                if ($buy_num < $limit_num) {
                    $msg .= '，当前还可购买 ' . ($limit_num - $buy_num) . ' 件';
                }
    
                error_stop($msg);
            }
        }

        return $next($buyInfo);
    }



    public function checkStock($buyInfo, \Closure $next) 
    {
        $goods = $buyInfo['goods'];
        $activity = $goods['activity'];

        if (!$activity) {
            $stockSale = new StockSale();
            $stockSale->stockLock($buyInfo);
        }

        return $next($buyInfo);
    }


    public function activity($buyInfo, \Closure $next)
    {
        $goods = $buyInfo['goods'];
        $activity = $goods['activity'];

        if ($activity) {
            $buyInfo = ActivityFacade::buy($buyInfo, $activity);
        }

        return $next($buyInfo);
    }



    public function through($throughs = [])
    {
        $throughs = is_array($throughs) ? $throughs : [$throughs];

        $pipes = [];
        foreach ($throughs as $through) {
            if (method_exists($this, $through)) {
                $pipes[] = function ($params, \Closure $next) use ($through) {
                    return $this->{$through}($params, $next);
                };
            }
        }

        return $pipes;
    }
}