<?php

namespace app\admin\controller\shopro\app\mplive;

use app\admin\model\shopro\app\mplive\Goods as MpliveGoodsModel;

/**
 * 小程序直播商品管理
 */
class Goods extends Index
{

    // 直播间商品
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $model = new MpliveGoodsModel();
        $list = $model->sheepFilter()->paginate($this->request->param('list_rows', 10));

        // 批量更新直播商品状态
        // $this->updateAuditStatusByGoods($list);

        $this->success('获取成功', null, $list);
    }

    // 直播间商品详情
    public function detail($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $goods = (new MpliveGoodsModel)->findOrFail($id);

        $this->success('', null, $goods);
    }

    // 创建直播间商品并提交审核
    public function add()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->param();

        $data = [
            "coverImgUrl" => $this->uploadMedia($params['cover_img_url']),
            "name" => $params['name'],
            "priceType" => $params['price_type'],
            "price" => $params['price'],
            "price2" => $params['price_type'] === 1 ? "" : $params['price2'],   // priceType为2或3时必填
            "url" => $params['url'],
        ];

        $res = $this->wechat->broadcast->create($data);

        $this->catchLiveError($res);

        $params['id'] = $res['goodsId'];
        $params['audit_id'] = $res['auditId'];
        $params['audit_status'] = 1;

        (new MpliveGoodsModel)->save($params);

        $this->success("操作成功");
    }

    // 直播商品审核
    public function audit($id)
    {
        $id = intval($id);
        $act = $this->request->param('act');

        $goods = MpliveGoodsModel::where('id', $id)->find();
        if (!$goods) {
            error_stop('未找到该商品');
        }
        // 撤回审核
        if ($act === 'reset') {
            $auditId = $goods->audit_id;
            if ($auditId) {
                $res = $this->wechat->broadcast->resetAudit($auditId, $id);
                $this->catchLiveError($res);
            }
        }

        // 重新审核
        if ($act === 'resubmit') {
            $res = $this->wechat->broadcast->resubmitAudit($id);
            $this->catchLiveError($res);
            $goods->audit_id = $res['auditId'];
            $goods->save();
        }

        return $this->status($id);
    }

    // 删除直播商品
    public function delete($id)
    {
        $id = intval($id);
        $res = $this->wechat->broadcast->delete($id);

        $this->catchLiveError($res);

        MpliveGoodsModel::where('id', $id)->delete();
        $this->success('操作成功');
    }

    // 更新直播商品
    public function edit($id = null)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch('add');
        }

        $params = $this->request->param();

        $data = [
            'goodsId' => $id,
            "coverImgUrl" => $this->uploadMedia($params['cover_img_url']),
            "name" => $params['name'],
            "priceType" => $params['price_type'],
            "price" => $params['price'],
            "price2" => $params['price_type'] === 1 ? "" : $params['price2'],   // priceType为2或3时必填
            "url" => $params['url'],
        ];

        $res = $this->wechat->broadcast->update($data);

        $this->catchLiveError($res);

        $goods = MpliveGoodsModel::where('id', $id)->find();
        $goods->save($data);

        $this->success('操作成功');
    }

    // 更新直播商品状态
    public function status($id)
    {
        $res = $this->wechat->broadcast->getGoodsWarehouse([$id]);

        $this->catchLiveError($res);

        $list = $res['goods'];

        foreach ($list as $key => $goods) {
            $mpliveGoods = MpliveGoodsModel::where('id', $goods['goods_id'])->find();
            if ($mpliveGoods) {
                $mpliveGoods->audit_status = $goods['audit_status'];
                $mpliveGoods->third_party_tag = $goods['third_party_tag'];
                $mpliveGoods->save();
            }
        }

        $this->success('操作成功');
    }
}
