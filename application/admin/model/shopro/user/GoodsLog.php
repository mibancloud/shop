<?php

namespace app\admin\model\shopro\user;

use app\admin\model\shopro\Common;
use app\admin\model\shopro\goods\Goods;

class GoodsLog extends Common
{
    protected $name = 'shopro_user_goods_log';

    // 追加属性
    protected $append = [

    ];


    /**
     * 添加浏览记录
     *
     * @param object $user
     * @param object $goods
     * @return void
     */
    public static function addView($user, $goods) 
    {
        if ($user) {
            $view = self::views()->where('user_id', $user->id)->where('goods_id', $goods->id)->find();
            if ($view) {
                $view->updatetime = time();
                $view->save();
            } else {
                $view = new self();
                $data = [
                    'goods_id' => $goods->id,
                    'user_id' => $user->id,
                    'type' => 'views'
                ];
                $view->save($data);
            }
        }
        // 增加商品浏览量
        Goods::where('id', $goods['id'])->update(['views' => \think\Db::raw('views+1')]);
    }

    public function scopeFavorite($query)
    {
        return $query->where('type', 'favorite');
    }


    public function scopeViews($query)      // view 和底层方法冲突了
    {
        return $query->where('type', 'views');
    }


    public function goods() 
    {
        return $this->belongsTo(Goods::class, 'goods_id');
    }
}
