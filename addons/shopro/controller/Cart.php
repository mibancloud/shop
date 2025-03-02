<?php

namespace addons\shopro\controller;

use app\admin\model\shopro\Cart as CartModel;
use app\admin\model\shopro\goods\Goods;
use app\admin\model\shopro\goods\SkuPrice;

class Cart extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $user = auth_user();

        // 被物理删除的商品直接删掉购物车，只删除自己的
        CartModel::whereNotExists(function ($query) {
            $goodsTableName = (new Goods())->db()->getTable();
            $query = $query->table($goodsTableName)->where($goodsTableName . '.id=goods_id');        // 软删除的商品购物车暂时不删，标记为失效
            return $query;
        })->where('user_id', $user->id)->delete();

        $carts = CartModel::with([
            'goods' => function ($query) {
                $query->removeOption('soft_delete');
            }, 'sku_price'
        ])->where('user_id', $user->id)->order('id', 'desc')->select();
        
        $carts = collection($carts)->each(function ($cart) {
            $cart->tags = $cart->tags;      // 标签
            $cart->status = $cart->status;  // 状态
        });

        $this->success('获取成功', $carts);
    }



    public function update()
    {
        $user = auth_user();
        $params = $this->request->only(['goods_id', 'goods_sku_price_id', 'goods_num', 'type']);
        $goods_num = $params['goods_num'] ?? 1;
        $type = $params['type'] ?? 'inc';

        $cart = CartModel::where('user_id', $user->id)
            ->where('goods_id', $params['goods_id'])
            ->where('goods_sku_price_id', $params['goods_sku_price_id'])
            ->find();
            
        $skuPrice = SkuPrice::where('goods_id', $params['goods_id'])->where('id', $params['goods_sku_price_id'])->find();
        if (!$skuPrice) {
            $this->error('商品规格未找到');
        }

        if ($cart) {
            if ($type == 'dec') {
                // 减
                $cart->snapshot_price = $skuPrice->price;
                $cart->save();
                $cart->setDec('goods_num', $goods_num);
            } else if ($type == 'cover') {
                $cart->goods_num = $goods_num;
                $cart->snapshot_price = $skuPrice->price;
                $cart->save();
            } else {
                // 加
                $cart->snapshot_price = $skuPrice->price;
                $cart->save();
                $cart->setInc('goods_num', $goods_num);
            }
        } else {
            $cart = new CartModel();
            $cart->user_id = $user->id;
            $cart->goods_id = $params['goods_id'];
            $cart->goods_sku_price_id = $params['goods_sku_price_id'];
            $cart->goods_num = $goods_num;
            $cart->snapshot_price = $skuPrice->price;
            $cart->save();
        }

        $this->success('更新成功', $cart);
    }



    public function delete()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        CartModel::where('user_id', $user->id)->whereIn('id', $id)->delete();

        $this->success('删除成功');
    }
}
