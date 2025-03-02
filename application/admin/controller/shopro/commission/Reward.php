<?php

namespace app\admin\controller\shopro\commission;

use app\admin\controller\shopro\Common;
use app\admin\model\shopro\commission\Reward as RewardModel;

class Reward extends Common
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RewardModel();
    }

    /**
     * 查看
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            $exportConfig = (new \addons\shopro\library\Export())->getConfig();
            $this->assignconfig("save_type", $exportConfig['save_type'] ?? 'download');
            return $this->view->fetch();
        }

        $list = $this->model->sheepFilter()->with(['buyer', 'agent', 'order', 'order_item'])->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $list);
    }


    public function export()
    {
        $cellTitles = [
            'reward_id' => 'Id',
            'order_sn' => '订单号',
            'buyer_nickname' => '下单用户',
            'buyer_mobile' => '手机号',
            'agent_nickname' => '分销用户',
            'agent_mobile' => '分销手机号',
            'original_commission' => '原始佣金',
            'commission' => '分销佣金',
            'commission_level' => '执行层级',
            'agent_level' => '执行等级',
            'status_text' => '状态',
            'type_text' => '入账方式',
            'commission_time' => '结算时间'
        ];

        // 数据总条数
        $total = $this->model->sheepFilter()->count();
        if ($total <= 0) {
            $this->error('导出数据为空');
        }

        $export = new \addons\shopro\library\Export();
        $params = [
            'file_name' => '佣金明细列表',
            'cell_titles' => $cellTitles,
            'total' => $total,
            'is_sub_cell' => false,
        ];

        $total_commission = 0;
        $result = $export->export($params, function ($pages) use (&$total_commission, $total) {
            $datas = $this->model->sheepFilter()->with(['buyer', 'agent', 'order', 'order_item'])
            ->limit((($pages['page'] - 1) * $pages['list_rows']), $pages['list_rows'])
            ->select();

            $datas = collection($datas);
            $datas->each(function ($order) {
            })->toArray();

            $newDatas = [];
            foreach ($datas as &$reward) {
                $data = [
                    'reward_id' => $reward['id'],
                    'order_sn' => $reward['order'] ? $reward['order']['order_sn'] : '',
                    'buyer_nickname' => $reward['buyer'] ? $reward['buyer']['nickname'] : '-',
                    'buyer_mobile' => $reward['buyer'] ? $reward['buyer']['mobile'] . ' ' : '-',
                    'agent_nickname' => $reward['agent'] ? $reward['agent']['nickname'] : '-',
                    'agent_mobile' => $reward['agent'] ? $reward['agent']['mobile'] . ' ' : '-',
                    'original_commission' => $reward['original_commission'],
                    'commission' => $reward['commission'],
                    'commission_level' => $reward['commission_level'],
                    'agent_level' => $reward['agent_level'],
                    'status_text' => $reward['status_text'],
                    'type_text' => $reward['type_text'],
                    'commission_time' => $reward['commission_time'],
                ];

                $newDatas[] = $data;
            }

            $total_commission += array_sum(array_column($newDatas, 'commission'));

            if ($pages['is_last_page']) {
                $newDatas[] = ['reward_id' => "总数：" . $total . "；总佣金金额：￥" . $total_commission .  "；"];
            }
            return $newDatas;
        });

        $this->success('导出成功' . (isset($result['file_path']) && $result['file_path'] ? '，请在服务器: “' . $result['file_path'] . '” 查看' : ''), null, $result);
    }
}
