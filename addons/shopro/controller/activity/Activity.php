<?php

namespace addons\shopro\controller\activity;

use addons\shopro\controller\Common;
use addons\shopro\facade\Activity as ActivityFacade;
use app\admin\model\shopro\activity\Activity as ActivityModel;

class Activity extends Common
{

    protected $noNeedLogin = ['detail'];
    protected $noNeedRight = ['*'];

    // 活动详情
    public function detail()
    {
        $id = $this->request->get('id');
        $activity = ActivityModel::where('id', $id)->find();
        if (!$activity) {
            $this->error(__('No Results were found'));
        }

        if ($activity->classify == 'promo') {
            $rules = $activity['rules'];
            $rules['simple'] = true;
            $tags = ActivityFacade::formatRuleTags($rules, $activity['type']);

            $activity['tag'] = $tags[0] ?? '';
            $activity['tags'] = $tags;
    
            $texts = ActivityFacade::formatRuleTexts($rules, $activity['type']);
            $activity['texts'] = $texts;
        }

        $this->success('获取成功', $activity);
    }
}
