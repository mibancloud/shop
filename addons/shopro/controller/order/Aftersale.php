<?php

namespace addons\shopro\controller\order;

use think\Db;
use addons\shopro\controller\Common;
use app\admin\model\shopro\order\Order as OrderModel;
use app\admin\model\shopro\order\OrderItem as OrderItemModel;
use app\admin\model\shopro\order\Aftersale as AftersaleModel;
use app\admin\model\shopro\order\AftersaleLog as AftersaleLogModel;
use app\admin\model\shopro\order\Action as OrderActionModel;

class Aftersale extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];
    
    public function index() {
        $user = auth_user();

        $params = $this->request->param();
        $type = $params['type'] ?? 'all';

        $aftersales = AftersaleModel::where('user_id', $user->id);

        if ($type != 'all') {
            $aftersales = $aftersales->{$type}();
        }

        $aftersales = $aftersales->order('id', 'desc')->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', $aftersales);
    }



    public function detail()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $aftersale = AftersaleModel::where('user_id', $user->id)->with('logs')->where('id', $id)->find();
        if (!$aftersale) {
            $this->error(__('No Results were found'));
        }

        $this->success('获取成功', $aftersale);
    }


    public function add() 
    {
        $user = auth_user();
        $params = $this->request->param();

        $this->svalidate($params, ".add");

        $aftersale = Db::transaction(function () use ($user, $params) {
            $type = $params['type'];
            $order_id = $params['order_id'];
            $order_item_id = $params['order_item_id'];
            $mobile = $params['mobile'] ?? '';
            $reason = $params['reason'] ?? '用户申请售后';
            $content = $params['content'] ?? '';
            $images = $params['images'] ?? [];

            $order = OrderModel::canAftersale()->where('user_id', $user->id)->lock(true)->where('id', $order_id)->find();
            if (!$order) {
                error_stop('订单不存在或不可售后');
            }

            $item = OrderItemModel::where('user_id', $user->id)->where('id', $order_item_id)->find();

            if (!$item) {
                error_stop('参数错误');
            }

            if (!in_array($item->aftersale_status, [
                OrderItemModel::AFTERSALE_STATUS_REFUSE,
                OrderItemModel::AFTERSALE_STATUS_NOAFTER
            ])) {
                error_stop('当前订单商品不可申请售后');
            }

            $aftersale = new AftersaleModel();
            $aftersale->aftersale_sn = get_sn($user->id, 'A');
            $aftersale->user_id = $user->id;
            $aftersale->type = $type;
            $aftersale->mobile = $mobile;
            $aftersale->activity_id = $item['activity_id'];
            $aftersale->activity_type = $item['activity_type'];
            $aftersale->order_id = $order_id;
            $aftersale->order_item_id = $order_item_id;
            $aftersale->goods_id = $item['goods_id'];
            $aftersale->goods_sku_price_id = $item['goods_sku_price_id'];
            $aftersale->goods_sku_text = $item['goods_sku_text'];
            $aftersale->goods_title = $item['goods_title'];
            $aftersale->goods_image = $item['goods_image'];
            $aftersale->goods_original_price = $item['goods_original_price'];
            $aftersale->discount_fee = $item['discount_fee'];
            $aftersale->goods_price = $item['goods_price'];
            $aftersale->goods_num = $item['goods_num'];
            $aftersale->dispatch_status = $item['dispatch_status'];
            $aftersale->dispatch_fee = $item['dispatch_fee'];
            $aftersale->aftersale_status = AftersaleModel::AFTERSALE_STATUS_NOOPER;
            $aftersale->refund_status = AftersaleModel::REFUND_STATUS_NOREFUND;      // 未退款
            $aftersale->refund_fee = 0;
            $aftersale->reason = $reason;
            $aftersale->content = $content;
            $aftersale->save();

            // 增加售后单变动记录、
            AftersaleLogModel::add($order, $aftersale, $user, 'user', [
                'log_type' => 'apply_aftersale',
                'content' => "申请原因：$reason <br>相关描述： $content",
                'images' => $images
            ]);

            $ext = $item->ext ?: [];
            $ext['aftersale_id'] = $aftersale->id;
            // 修改订单 item 状态，申请售后
            $item->aftersale_status = OrderItemModel::AFTERSALE_STATUS_ING;
            $item->ext = $ext;
            $item->save();
            OrderActionModel::add($order, $item, $user, 'user', '用户申请售后');

            return $aftersale;
        });

        $this->success('申请成功', $aftersale);
    }


    public function cancel()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $aftersale = AftersaleModel::canCancel()->where('user_id', $user->id)->where('id', $id)->find();

        if (!$aftersale) {
            $this->error('售后单不存在或不可取消');
        }

        $order = OrderModel::where('user_id', $user->id)->find($aftersale['order_id']);
        if (!$order) {
            $this->error(__('No Results were found'));
        }

        $orderItem = OrderItemModel::find($aftersale['order_item_id']);
        if (!$orderItem || in_array($orderItem['refund_status'], [OrderItemModel::REFUND_STATUS_AGREE, OrderItemModel::REFUND_STATUS_COMPLETED])) {
            // 不存在， 或者已经退款
            $this->error('退款商品不存在或已退款');
        }

        $aftersale = Db::transaction(function () use ($aftersale, $order, $orderItem, $user) {
            $aftersale->aftersale_status = AftersaleModel::AFTERSALE_STATUS_CANCEL;        // 取消售后单
            $aftersale->save();

            AftersaleLogModel::add($order, $aftersale, $user, 'user', [
                'log_type' => 'cancel',
                'content' => '用户取消申请售后',
                'images' => []
            ]);

            // 修改订单 item 为未申请售后
            $orderItem->aftersale_status = OrderItemModel::AFTERSALE_STATUS_NOAFTER;
            $orderItem->refund_status = OrderItemModel::REFUND_STATUS_NOREFUND;
            $orderItem->save();

            OrderActionModel::add($order, $orderItem, $user, 'user', '用户取消申请售后');

            return $aftersale;
        });

        $this->success('取消成功', $aftersale);
    }


    public function delete()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $aftersale = AftersaleModel::canDelete()->where('user_id', $user->id)->where('id', $id)->find();
        if (!$aftersale) {
            $this->error('售后单不存在或不可删除');
        }

        $order = OrderModel::withTrashed()->where('id', $aftersale['order_id'])->find();
        Db::transaction(function () use ($aftersale, $order, $user) {
            AftersaleLogModel::add($order, $aftersale, $user, 'user', [
                'log_type' => 'delete',
                'content' => '用户删除售后单',
                'images' => []
            ]);

            $aftersale->delete();        // 删除售后单(后删除，否则添加记录时 $aftersale 没数据)
        });

        $this->success('删除成功');
    }
}
