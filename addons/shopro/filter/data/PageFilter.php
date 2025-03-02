<?php

namespace addons\shopro\filter\data;

use addons\shopro\filter\BaseFilter;
use think\db\Query;

/**
 * 页面筛选
 */
class PageFilter extends BaseFilter
{
    protected $keywordFields = ['name', 'path'];
}
