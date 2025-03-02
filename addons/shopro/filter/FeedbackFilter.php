<?php

namespace addons\shopro\filter;

use think\db\Query;

/**
 * 反馈筛选
 */
class FeedbackFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'type', 'content'];
}
