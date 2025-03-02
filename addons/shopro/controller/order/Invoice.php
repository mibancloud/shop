<?php

namespace addons\shopro\controller\order;

use addons\shopro\controller\Common;
use app\admin\model\shopro\order\Invoice as OrderInvoiceModel;

class Invoice extends Common
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $user = auth_user();
        $params = $this->request->param();
        $type = $params['type'] ?? 'all';

        $invoices = OrderInvoiceModel::where('user_id', $user->id);

        switch ($type) {
            case 'cancel':
                $invoices = $invoices->cancel();
                break;
            case 'waiting':
                $invoices = $invoices->waiting();
                break;
            case 'finish':
                $invoices = $invoices->finish();
                break;
            default :
                $invoices = $invoices->show();  // 除了未支付的
                break;
        }

        $invoices = $invoices->order('id', 'desc')->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', $invoices);
    }


    public function detail()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $invoice = OrderInvoiceModel::with(['order', 'order_items'])->where('user_id', $user->id)->where('id', $id)->find();
        if (!$invoice) {
            $this->error(__('No Results were found'));
        }

        $invoice->append(['order_items']);     // 取消隐藏 order_items
        $this->success('获取成功', $invoice);
    }


    // 取消订单
    public function cancel()
    {
        $user = auth_user();
        $id = $this->request->param('id');

        $invoice = OrderInvoiceModel::where('user_id', $user->id)->waiting()->where('id', $id)->find();
        if (!$invoice) {
            $this->error(__('No Results were found'));
        }

        $invoice->status = 'cancel';
        $invoice->save();

        $this->success('取消成功', $invoice);
    }
}
