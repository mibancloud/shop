<?php

namespace addons\shopro\job;

use think\queue\Job;
use think\Db;
use think\exception\HttpResponseException;
use addons\shopro\service\commission\Agent as AgentService;

/**
 * 分销任务
 */
class Commission extends BaseJob
{

    /**
     * 分销商升级
     */
    public function agentUpgrade(Job $job, $payload)
    {
        try {
            $userId = $payload['user_id'];
            $agent = new AgentService($userId);

            if ($agent->user) {
                Db::transaction(function () use ($agent) {
                    $agent->runAgentUpgradePlan();
                });
            }
            $job->delete();
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'AgentUpgrade.HttpResponseException', $message);
        } catch (\Exception $e) {
            format_log_error($e, 'AgentUpgrade');
        }
    }
}
