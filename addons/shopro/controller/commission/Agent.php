<?php

namespace addons\shopro\controller\commission;

use think\Db;
use app\admin\model\shopro\user\User as UserModel;
use app\admin\model\shopro\commission\Agent as AgentModel;
use app\admin\model\shopro\goods\Goods as GoodsModel;
use addons\shopro\service\Wallet;


class Agent extends Commission
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    // 分销商详情
    public function index()
    {
        $status = $this->service->getAgentStatus(true);

        $condition = [
            'type' => '',
            'value' => ''
        ];

        switch ($status) {
            case AgentModel::AGENT_STATUS_NULL:
                $condition = $this->service->config->getBecomeAgentEvent();
                if ($condition['type'] === 'goods') {
                    $condition['value'] = GoodsModel::show()->whereIn('id', $condition['value'])->select();
                }
                $this->error('', $condition, 100);
                break;
            case AgentModel::AGENT_STATUS_NEEDINFO:
                $this->error('待完善信息,请补充您的资料后提交审核', $condition, 103);
                break;
            case AgentModel::AGENT_STATUS_PENDING:
                $this->error('正在审核中,请耐心等候结果', $condition, 104);
                break;
            case AgentModel::AGENT_STATUS_REJECT:
                $agentFormStatus = $this->service->config->isAgentApplyForm();
                if ($agentFormStatus) {
                    $this->error('抱歉!您的申请信息未通过,请尝试修改后重新提交', $condition, 105);
                } else {
                    $this->error('抱歉!您的申请未通过,请尝试重新申请', $condition, 106);
                }
                break;
            case AgentModel::AGENT_STATUS_FREEZE:
                $this->error('抱歉!您的账户已被冻结,如有疑问请联系客服', $condition, 107);
                break;
        }
        $data = $this->service->agent;

        $this->success('分销商信息', $data);
    }

    // 分销商完善个人信息
    public function form()
    {
        if (!$this->service->config->isAgentApplyForm()) {
            $this->error('未开启分销商申请');
        }

        $agentForm = $this->service->config->getAgentForm();
        $protocol = $this->service->config->getApplyProtocol();
        $applyInfo = $this->service->agent->apply_info ?? [];

        $config = [
            'form' => $agentForm['content'],
            'status' => $this->service->getAgentStatus(true),
            'background' => $agentForm['background_image'],
            'protocol' => $protocol,
            'applyInfo' => $applyInfo
        ];

        $this->success("", $config);
    }

    // 申请分销商/完善资料
    public function apply()
    {
        if (!$this->service->config->isAgentApplyForm()) {
            $this->error('未开启分销商申请');
        }
        $status = $this->service->getAgentStatus(true);

        if (!in_array($status, [AgentModel::AGENT_STATUS_NEEDINFO, AgentModel::AGENT_STATUS_REJECT, AgentModel::AGENT_STATUS_NORMAL])) {
            $this->error('您暂时不能申请');
        }
        Db::transaction(function () use ($status) {
            $data = $this->request->param('data/a');
            // 过滤无效表单字段数据
            $config = $this->service->config->getAgentForm();
            $form = (array)$config['content'];
            $data = array_column($data, 'value', 'name');

            foreach ($form as &$item) {
                if (!empty($data[$item['name']])) {
                    $item['value'] = $data[$item['name']];
                } else {
                    $this->error($item['name'] . '不能为空');
                }
            }
            if ($status === AgentModel::AGENT_STATUS_NEEDINFO) {
                $this->service->createNewAgent('', $form);
            } else {
                // 重置为审核中
                if ($status === AgentModel::AGENT_STATUS_REJECT) {
                    $this->service->agent->status = AgentModel::AGENT_STATUS_PENDING;
                }
                // 更新分销商信息
                $this->service->agent->apply_info = $form;
                $this->service->agent->save();
            }
        });


        $this->success('提交成功');
    }

    // 我的团队
    public function team()
    {
        $agentId = $this->service->user->id;

        $data = UserModel::where('parent_user_id', $agentId)
            ->where('status', 'normal')
            ->with(['agent' => function ($query) {
                return $query->with('level_info');
            }])
            ->paginate($this->request->param('list_rows', 8));

        $this->success("", $data);
    }

    // 佣金转余额
    public function transfer()
    {
        $amount = $this->request->param('amount');
        if ($amount <= 0) {
            $this->error('请输入正确的金额');
        }
        Db::transaction(function () use ($amount) {
            $user = auth_user();
            Wallet::change($user, 'commission', -$amount, 'transfer_to_money');
            Wallet::change($user, 'money', $amount, 'transfer_by_commission');
        });
        $this->success('');
    }
}
