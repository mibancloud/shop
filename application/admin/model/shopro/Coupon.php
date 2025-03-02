<?php

namespace app\admin\model\shopro;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\user\Coupon as UserCouponModel;
use app\admin\model\shopro\goods\Goods;
use app\admin\model\shopro\Category;
use traits\model\SoftDelete;

class Coupon extends Common
{
    use SoftDelete;
    protected $deleteTime = 'deletetime';
    
    protected $name = 'shopro_coupon';

    protected $type = [
        'get_start_time' => 'timestamp',
        'get_end_time' => 'timestamp',
        // 'use_start_time' => 'timestamp',
        // 'use_end_time' => 'timestamp',
    ];

    // 追加属性
    protected $append = [
        'type_text',
        'status_text',
        'use_scope_text',
        'amount_text',
        'get_time_status',
        'get_time_text'
    ];


    /**
     * 默认类型列表
     *
     * @return array
     */
    public function typeList()
    {
        return [
            'reduce' => '满减券',
            'discount' => '折扣券'
        ];
    }
    /**
     * 可用范围列表
     *
     * @return array
     */
    public function useScopeList()
    {
        return [
            'all_use' => '全场通用',
            'goods' => '指定商品可用',
            'disabled_goods' => '指定商品不可用',
            'category' => '指定分类可用',
        ];
    }
    /**
     * 默认状态列表
     *
     * @return array
     */
    public function statusList()
    {
        return [
            'normal' => '公开发放',
            'hidden' => '后台发放',
            'disabled' => '禁止使用',
        ];
    }


    /**
     * 优惠券领取状态
     *
     * @return void
     */
    public function getStatusList()
    {
        return [
            'can_get' => '立即领取',
            'cannot_get' => '已领取',
            'get_over' => '已领完',

            // 用户优惠券的状态
            'used' => '已使用',
            'can_use' => '立即使用',
            'expired' => '已过期',
            'cannot_use' => '暂不可用'
        ];
    }


    public function scopeCanGet($query)
    {
        return $query->where('get_start_time', '<=', time())
            ->where('get_end_time', '>=', time());
    }


    /**
     * 查询指定商品满足的优惠券
     *
     * @param [type] $query
     * @param [type] $goods
     * @return void
     */
    public function scopeGoods($query, $goods)
    {
        $goods_id = $goods['id'];
        $category_ids = $goods['category_ids'];

        // 查询符合商品的优惠券
        return $query->where(function ($query) use ($goods_id, $category_ids) {
            $query->where('use_scope', 'all_use')
            ->whereOr(function ($query) use ($goods_id) {
                $query->where('use_scope', 'goods')->whereRaw("find_in_set($goods_id, items)");
            })
                ->whereOr(function ($query) use ($goods_id) {
                    $query->where('use_scope', 'disabled_goods')->whereRaw("not find_in_set($goods_id, items)");
                })
                ->whereOr(function ($query) use ($goods_id, $category_ids) {
                    $query->where('use_scope', 'category')->where(function ($query) use ($category_ids) {
                        $category_ids = array_filter(explode(',', $category_ids));
                        foreach ($category_ids as $key => $category_id) {
                            $query->whereOrRaw("find_in_set($category_id, items)");
                        }
                    });
                });
        });
    }



        /**
     * 开始使用时间获取器
     *
     * @param string $value
     * @param array $data
     * @return int
     */
    public function setUseStartTimeAttr($value, $data)
    {
        return $value ? strtotime($value) : (isset($data['use_start_time']) ? strtotime($data['use_start_time']) : 0);
    }


    /**
     * 结束使用时间获取器
     *
     * @param string $value
     * @param array $data
     * @return int
     */
    public function setUseEndTimeAttr($value, $data)
    {
        return $value ? strtotime($value) : (isset($data['use_end_time']) ? strtotime($data['use_end_time']) : 0);
    }


    /**
     * 可用范围获取器
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getUseScopeTextAttr($value, $data)
    {
        $value = $value ?: ($data['use_scope'] ?? null);

        $list = $this->useScopeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getAmountTextAttr($value, $data)
    {
        return '满' . $data['enough'] . '元,' . ($data['type'] == 'reduce' ? '减' . floatval($data['amount']) . '元' : '打' . floatval($data['amount']) . '折');
    }

    public function getItemsValueAttr($value, $data)
    {
        if (in_array($data['use_scope'], ['goods', 'disabled_goods'])) {
            $items_value = Goods::whereIn('id', $data['items'])->select();
            $items_value = collection($items_value);
            $items_value = $items_value->each(function ($goods) {
                // 前端要显示活动标签
                $goods->promos = $goods->promos;
            });
        } else {
            $items_value = Category::whereIn('id', $data['items'])->select();
        }

        return $items_value ?? [];
    }

    public function getGetStatusAttr($value, $data)
    {
        $limit_num = $data['limit_num'] ?? 0;
        // 不限制领取次数，或者限制次数，领取数量还没达到最大值
        $get_status = (!$limit_num || ($limit_num && $limit_num > count($this->user_coupons))) ? 'can_get' : 'cannot_get';

        if ($get_status == 'can_get' && $data['stock'] <= 0) {
            $get_status = 'get_over';       // 已领完
        }

        $user_coupon_id = request()->param('user_coupon_id', 0);
        if ($user_coupon_id) {
            // 从我领取的优惠券进详情,覆盖 状态
            $user = auth_user();
            $userCoupon = UserCouponModel::where('user_id', ($user ? $user->id : 0))->find($user_coupon_id);
            if ($userCoupon) {
                $get_status = $userCoupon->status;
            }
        }
        return $get_status;
    }


    public function getGetStatusTextAttr($value, $data)
    {
        $list = $this->getStatusList();
        return isset($list[$this->get_status]) ? $list[$this->get_status] : '';
    }


    /**
     * 后端发放状态
     *
     * @return string
     */
    public function getGetTimeStatusAttr($value, $data) {
        if ($data['get_start_time'] > time()) {
            $time_text = 'nostart';        // 未开始
        } else if ($data['get_start_time'] <= time() && $data['get_end_time'] >= time()) {
            $time_text = 'ing';
        } else if ($data['get_end_time'] < time()) {
            $time_text = 'ended';
        }

        return $time_text;
    }


    /**
     * 后端发放状态
     *
     * @return string
     */
    public function getGetTimeTextAttr($value, $data)
    {
        if ($this->get_time_status == 'nostart') {
            $time_text = '未开始';        // 未开始
        } else if ($this->get_time_status == 'ing') {
            $time_text = '发放中';
        } else if ($this->get_time_status == 'ended') {
            $time_text = '已结束';
        }

        return $time_text;
    }


    public function getGetNumAttr($value, $data)
    {
        return UserCouponModel::where('coupon_id', $data['id'])->count();
    }


    public function getUseNumAttr($value, $data)
    {
        return UserCouponModel::where('coupon_id', $data['id'])->whereNotNull('use_time')->count();
    }



    public function getUseStartTimeAttr($value, $data)
    {
        $use_start_time = $value ? date('Y-m-d H:i:s', $value) : null;
        $user_coupon_id = request()->param('user_coupon_id', 0);
        if ($user_coupon_id && $data['use_time_type'] == 'days') {
            // 从我领取的优惠券进详情,覆盖 状态
            $user = auth_user();
            $userCoupon = UserCouponModel::cache(60)->where('user_id', ($user ? $user->id : 0))->find($user_coupon_id);
            if ($userCoupon) {
                $use_start_time = date('Y-m-d H:i:s', $userCoupon->getData('createtime') + ($this->start_days * 86400));
            }
        }

        return $use_start_time;
    }

    public function getUseEndTimeAttr($value, $data)
    {
        $use_end_time = $value ? date('Y-m-d H:i:s', $value) : null;
        $user_coupon_id = request()->param('user_coupon_id', 0);
        if ($user_coupon_id && $data['use_time_type'] == 'days') {
            // 从我领取的优惠券进详情,覆盖 状态
            $user = auth_user();
            $userCoupon = UserCouponModel::cache(60)->where('user_id', ($user ? $user->id : 0))->find($user_coupon_id);
            if ($userCoupon) {
                $use_end_time = date('Y-m-d H:i:s', $userCoupon->getData('createtime') + (($this->start_days + $this->days) * 86400));
            }
        }

        return $use_end_time;
    }


    public function userCoupons()
    {
        $user = auth_user();
        return $this->hasMany(UserCouponModel::class, 'coupon_id')->where('user_id', ($user ? $user->id : 0));
    }
}
