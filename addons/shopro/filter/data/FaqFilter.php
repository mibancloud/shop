<?php

namespace addons\shopro\filter\data;

use addons\shopro\filter\BaseFilter;
use think\db\Query;

/**
 * 常见问题筛选
 */
class FaqFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'title', 'content'];
}
