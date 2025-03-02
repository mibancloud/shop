<?php

namespace app\admin\model\shopro\goods;

use app\admin\model\shopro\Common;
use traits\model\SoftDelete;

class Comment extends Common
{

    use SoftDelete;

    protected $deleteTime = 'deletetime';

    // 表名
    protected $name = 'shopro_goods_comment';

    protected $type = [
        'images' => 'json',
        'reply_time' => 'timestamp',
    ];

    protected $append = [
        'status_text'
    ];

    protected $hidden = [
        'user_type'
    ];

    public static $typeAll = [
        'all' => ['code' => 'all', 'name' => '全部'],
        'images' => ['code' => 'images', 'name' => '有图'],
        'good' => ['code' => 'good', 'name' => '好评'],
        'moderate' => ['code' => 'moderate', 'name' => '中评'],
        'bad' => ['code' => 'bad', 'name' => '差评'],
    ];

    public function scopeImages($query)
    {
        return $query->whereNotNull('images')->where('images', '<>', '')->where('images', '<>', '[]');
    }

    public function scopeGood($query)
    {
        return $query->where('level', 'in', [5, 4]);
    }

    public function scopeModerate($query)
    {
        return $query->where('level', 'in', [3, 2]);
    }

    public function scopeBad($query)
    {
        return $query->where('level', 1);
    }

    public function scopeNoReply($query)
    {
        return $query->whereNull('reply_time');
    }


    public function getUserNicknameAttr($value, $data)
    {
        $value = $value ?: ($data['user_nickname'] ?? '');

        return $value ? string_hide($value, 2) : $value;
    }



    public function admin()
    {
        return $this->belongsTo(\app\admin\model\Admin::class, 'admin_id', 'id')->field('id,username,nickname,avatar');
    }

    public function goods()
    {
        return $this->belongsTo(\app\admin\model\shopro\goods\Goods::class, 'goods_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(\app\admin\model\shopro\order\Order::class, 'order_id', 'id');
    }


    public function orderItem()
    {
        return $this->belongsTo(\app\admin\model\shopro\order\OrderItem::class, 'order_item_id', 'id');
    }
}
