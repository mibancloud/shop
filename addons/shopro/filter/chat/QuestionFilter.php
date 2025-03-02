<?php

namespace addons\shopro\filter\chat;

use addons\shopro\filter\BaseFilter;

/**
 * 猜你想问筛选
 */
class QuestionFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'title'];
}
