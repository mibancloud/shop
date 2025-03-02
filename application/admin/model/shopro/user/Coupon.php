<?php

namespace app\admin\model\shopro\user;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\Coupon as CouponModel;
use app\admin\model\shopro\user\User as UserModel;
use app\admin\model\shopro\order\Order as OrderModel;

class Coupon extends Common
{
    protected $name = 'shopro_user_coupon';

    protected $type = [
        'use_time' => 'timestamp'
    ];

    // 追加属性
    protected $append = [
        'name',
        'type',
        'type_text',
        'use_scope',
        'use_scope_text',
        'items',
        'amount',
        'amount_text',
        'enough',
        'limit_num',
        'use_start_time',
        'use_end_time',
        'max_amount',
        'is_double_discount',
        'description',
        'status',
        'status_text',
    ];


    public function statusList()
    {
        return [
            'used' => '已使用',
            'can_use' => '立即使用',
            'expired' => '已过期',
            'cannot_use' => '暂不可用',

            'geted' => '未使用' // 包括 can_use 和 cannot_use
        ];
    }


    public function scopeGeted($query)
    {
        return $query->whereNull('use_time')->whereExists(function ($query) {
            $table_name = (new CouponModel)->getQuery()->getTable();
            $user_coupon_name = (new self)->getQuery()->getTable();

            $query->table($table_name)->whereNull('deletetime')->whereIn('status', ['normal', 'hidden'])
            ->where($user_coupon_name . '.coupon_id=' . $table_name . '.id')
                ->where(function ($query) use ($user_coupon_name) {
                    $query->where(function ($query) {
                        $query->where('use_time_type', 'range')->where('use_end_time', '>=', time());           // 可用结束时间，大于当前时间，已经可用，或者暂不可用都算
                    })->whereOr(function ($query) use ($user_coupon_name) {
                        $query->where('use_time_type', 'days')
                        ->whereRaw($user_coupon_name . '.createtime + ((start_days + days) * 86400) >= ' . time());       // 可用结束结束时间大于当前时间
                    });
                });
        });
    }



    // 可以使用
    public function scopeCanUse($query)
    {
        return $query->whereNull('use_time')->whereExists(function ($query) {
            $table_name = (new CouponModel)->getQuery()->getTable();
            $user_coupon_name = (new self)->getQuery()->getTable();

            $query->table($table_name)->whereNull('deletetime')->whereIn('status', ['normal', 'hidden'])
            ->where($user_coupon_name . '.coupon_id=' . $table_name . '.id')
                ->where(function ($query) use ($user_coupon_name) {
                    $query->where(function ($query) {
                        $query->where('use_time_type', 'range')->where('use_start_time', '<=', time())->where('use_end_time', '>=', time());
                    })->whereOr(function ($query) use ($user_coupon_name) {
                        $query->where('use_time_type', 'days')
                        ->whereRaw($user_coupon_name . '.createtime + (start_days * 86400) <= ' . time())
                            ->whereRaw($user_coupon_name . '.createtime + ((start_days + days) * 86400) >= ' . time());
                    });
                });
        });
    }


    // 暂不可用，还没到可使用日期
    public function scopeCannotUse($query)
    {
        return $query->whereNull('use_time')->whereExists(function ($query) {
            $table_name = (new CouponModel)->getQuery()->getTable();
            $user_coupon_name = (new self)->getQuery()->getTable();

            $query->table($table_name)->whereNull('deletetime')->whereIn('status', ['normal', 'hidden'])
            ->where($user_coupon_name . '.coupon_id=' . $table_name . '.id')
                ->where(function ($query) use ($user_coupon_name) {
                    $query->where(function ($query) {
                        $query->where('use_time_type', 'range')->where('use_start_time', '>', time());
                    })->whereOr(function ($query) use ($user_coupon_name) {
                        $query->where('use_time_type', 'days')
                        ->whereRaw($user_coupon_name . '.createtime + (start_days * 86400) > ' . time());
                    });
                });
        });
    }


    // 已使用
    public function scopeUsed($query)
    {
        return $query->whereNotNull('use_time');
    }

    // 未使用，但已过期
    public function scopeExpired($query)
    {
        return $query->whereNull('use_time')->whereExists(function ($query) {
            $table_name = (new CouponModel)->getQuery()->getTable();
            $user_coupon_name = (new self)->getQuery()->getTable();

            $query->table($table_name)->whereNull('deletetime')->whereIn('status', ['normal', 'hidden'])
            ->where($user_coupon_name . '.coupon_id=' . $table_name . '.id')
                ->where(function ($query) use ($user_coupon_name) {
                    $query->where(function ($query) {
                        $query->where('use_time_type', 'range')->where('use_end_time', '<', time());
                    })->whereOr(function ($query) use ($user_coupon_name) {
                        $query->where('use_time_type', 'days')
                        ->whereRaw($user_coupon_name . '.createtime + ((start_days + days) * 86400) < ' . time());
                    });
                });
        });
    }

    // 未使用，但已过期,或者已使用
    public function scopeInvalid($query)
    {
        return $query->where(function ($query) {
            $query->whereNotNull('use_time')->whereOr(function ($query) {
                $query->whereNull('use_time')->whereExists(function ($query) {
                    $table_name = (new CouponModel)->getQuery()->getTable();
                    $user_coupon_name = (new self)->getQuery()->getTable();

                    $query->table($table_name)->whereNull('deletetime')->whereIn('status', ['normal', 'hidden'])
                    ->where($user_coupon_name . '.coupon_id=' . $table_name . '.id')
                        ->where(function ($query) use ($user_coupon_name) {
                            $query->where(function ($query) {
                                $query->where('use_time_type', 'range')->where('use_end_time', '<', time());
                            })->whereOr(function ($query) use ($user_coupon_name) {
                                $query->where('use_time_type', 'days')
                                ->whereRaw($user_coupon_name . '.createtime + ((start_days + days) * 86400) < ' . time());
                            });
                        });
                });
            });
        });
    }


    public function getNameAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->name : '';
    }

    public function getTypeAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->type : '';
    }

    public function getUseScopeAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->use_scope : '';
    }

    public function getItemsAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->items : '';
    }

    public function getAmountAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->amount : '';
    }


    public function getEnoughAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->enough : '';
    }

    public function getLimitNumAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->limit_num : '';
    }

    public function getUseStartTimeAttr($value, $data)
    {
        if ($this->coupon) {
            if ($this->coupon->use_time_type == 'days') {
                return date('Y-m-d H:i:s', $data['createtime'] + ($this->coupon->start_days * 86400));
            }

            return $this->coupon->use_start_time;
        }
    }

    public function getUseEndTimeAttr($value, $data)
    {
        if ($this->coupon) {
            if ($this->coupon->use_time_type == 'days') {
                return date('Y-m-d H:i:s', $data['createtime'] + (($this->coupon->start_days + $this->coupon->days) * 86400));
            }

            return $this->coupon->use_end_time;
        }
    }

    public function getMaxAmountAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->max_amount : 0;
    }

    public function getIsDoubleDiscountAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->is_double_discount : 0;
    }

    public function getDescriptionAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->description : '';
    }

    public function getUseScopeTextAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->use_scope : 0;
    }

    public function getAmountTextAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->amount_text : 0;
    }

    public function getItemsValueAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->items_value : 0;
    }

    public function getTypeTextAttr($value, $data)
    {
        return $this->coupon ? $this->coupon->type_text : 0;
    }


    // 我的优惠券使用状态
    public function getStatusAttr($value, $data)
    {
        if ($data['use_time']) {
            $status = 'used';
        } else {
            if ($this->use_start_time <= date('Y-m-d H:i:s') && $this->use_end_time >= date('Y-m-d H:i:s')) {
                $status = 'can_use';
            } else if ($this->use_end_time <= date('Y-m-d H:i:s')) {
                $status = 'expired';
            } else {
                // 未到使用日期
                $status = 'cannot_use';
            }
        }

        return $status;
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($this->status ?? '');

        if (strpos(request()->url(),  'addons/shopro') === false) {
            $value = in_array($value, ['can_use', 'cannot_use']) ? 'geted' : $value;        // 后台，可以使用和咱不可用 合并
        }

        $list = $this->statusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function coupon()
    {
        return $this->belongsTo(CouponModel::class, 'coupon_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(OrderModel::class, 'use_order_id');
    }
}
