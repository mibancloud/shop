<?php

namespace addons\shopro\controller\commission;

use think\Request;
use app\admin\model\shopro\commission\Order as OrderModel;

class Order extends Commission
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    // 分销动态
    public function index()
    {
        $agentId = $this->service->user->id;

        $type = $this->request->param('type', "all");
        if (!in_array($type, ['all', 'back', 'cancel', 'yes'])) {
            $this->error("");
        }

        $query = OrderModel
            ::where('agent_id', $agentId)
            ->with([
                'buyer' => function ($query) {
                    return $query->field(['avatar', 'nickname']);
                },
                'order',
                'rewards' => function ($query) use ($agentId) {
                    return $query->where('agent_id', $agentId);
                },
                'order_item'
            ])
            ->order('id desc');

        if ($type !== 'all') {
            $query->$type();
        }
        $data = $query->paginate($this->request->param('list_rows', 10));
        $this->success("", $data);
    }
}
