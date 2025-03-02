<?php

namespace addons\shopro\controller\user;

use addons\shopro\controller\Common;
use app\admin\model\shopro\user\GoodsLog as UserGoodsLogModel;
use app\admin\model\shopro\goods\Goods;

class GoodsLog extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $user = auth_user();
        $type = $this->request->param('type');

        // 首先删除商品不存在的记录
        UserGoodsLogModel::whereNotExists(function ($query) {
            $goodsTableName = (new Goods())->getQuery()->getTable();
            $tableName = (new UserGoodsLogModel())->getQuery()->getTable();
            $query = $query->table($goodsTableName)->where($goodsTableName . '.id=' . $tableName . '.goods_id')->whereNull($goodsTableName . '.deletetime');   // 不查软删除的商品

            return $query;
        })->where('user_id', $user->id)->delete();

        $logs = UserGoodsLogModel::with('goods')->{$type}()->where('user_id', $user->id);

        $logs = $logs->order('updatetime', 'desc')->paginate($this->request->param('list_rows', 10));       // 按照更新时间排序

        $this->success('获取成功', $logs);
    }



    /**
     * 收藏/取消收藏
     *
     * @param Request $request
     * @return void
     */
    public function favorite()
    {
        $user = auth_user();
        $goods_id = $this->request->param('goods_id');
        $goods_ids = $this->request->param('goods_ids');

        if (!$goods_id && !$goods_ids) {
            $this->error('缺少参数');
        }

        if ($goods_ids) {
            // 个人中心批量取消收藏
            $log = UserGoodsLogModel::favorite()->whereIn('goods_id', $goods_ids)
                ->where('user_id', $user->id)->delete();

            $this->success('取消收藏成功');
        }

        $log = UserGoodsLogModel::favorite()->where('goods_id', $goods_id)
                ->where('user_id', $user->id)->find();

        $favorite = false;      // 取消收藏
        if ($log) {
            // 取消收藏
            $log->delete();
        } else {
            $favorite = true;       // 收藏
            $log = new UserGoodsLogModel();
            $log->goods_id = $goods_id;
            $log->user_id = $user->id;
            $log->type = 'favorite';
            $log->save();
        }

        $this->success($favorite ? '收藏成功' : '取消收藏');
    }


    public function viewDel()
    {
        $goods_id = $this->request->param('goods_id');    // 支持 逗号分开
        $user = auth_user();

        UserGoodsLogModel::views()->whereIn('goods_id', $goods_id)
            ->where('user_id', $user->id)->delete();

        $this->success('删除成功');
    }
}
