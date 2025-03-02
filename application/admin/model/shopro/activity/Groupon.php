<?php

namespace app\admin\model\shopro\activity;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\user\User;
use app\admin\model\shopro\goods\Goods;
use app\admin\model\shopro\order\OrderItem;

class Groupon extends Common
{
    protected $name = 'shopro_activity_groupon';

    protected $type = [
        'finish_time' => 'timestamp',
        'expire_time' => 'timestamp'
    ];

    // 追加属性
    protected $append = [
        'status_text',
    ];


    public function statusList()
    {
        return [
            'invalid' => '拼团失败',
            'ing' => '进行中',
            'finish' => '已成团',
            'finish_fictitious' => '虚拟成团',
        ];
    }

    /**
     * 查询正在进行中的团
     */
    public function scopeIng($query)
    {
        return $query->where('status', 'ing');
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');

        $list = $this->statusList();
        $value = ($value == 'finish_fictitious' && strpos(request()->url(),  'addons/shopro') !== false) ? 'finish' : $value;
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getExpireTimeUnixAttr($value, $data)
    {
        return isset($data['expire_time']) ? $this->getData('expire_time') : 0;
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id', 'id');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'id');
    }

    public function grouponLogs()
    {
        return $this->hasMany(GrouponLog::class, 'groupon_id', 'id');
    }


    public function leader()
    {
        return $this->hasOne(GrouponLog::class, 'groupon_id', 'id')->where('is_leader', 1);
    }


    /**
     * 前端拼团详情用
     *
     * @return void
     */
    public function my()
    {
        $user = auth_user();
        return $this->hasOne(GrouponLog::class, 'groupon_id', 'id')->where('user_id', ($user ? $user->id : 0))->where('is_fictitious', 0);
    }
}
