<?php

namespace app\admin\controller\shopro\order;

use think\Db;
use app\admin\controller\shopro\Common;
use app\admin\model\shopro\order\Invoice as OrderInvoiceModel;
use app\admin\model\shopro\order\Order as OrderModel;

class Invoice extends Common
{

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new OrderInvoiceModel;
    }

    /**
     * 发票列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $invoices = $this->model->sheepFilter()->with(['user', 'order', 'order_items'])
            ->paginate(request()->param('list_rows', 10))->each(function ($invoice) {
                $invoice->order_status = $invoice->order_status;
                $invoice->order_status_text = $invoice->order_status_text;
                $invoice->order_fee = $invoice->order_fee;
            });

        $this->success('获取成功', null, $invoices);
    }



    public function confirm($id) 
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->param();
        $invoice = $this->model->waiting()->whereIn('id', $id)->find();
        if (!$invoice) {
            $this->error(__('No Results were found'));
        }

        $invoice->download_urls = $params['download_urls'] ?? null;
        $invoice->invoice_amount = $params['invoice_amount'];
        $invoice->status = 'finish';
        $invoice->finish_time = time();
        $invoice->save();

        $this->success('开具成功', null, $invoice);
    }
}
