<?php

namespace addons\shopro\filter;

use think\db\Query;

/**
 * 分类搜索
 */
class CategoryFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'name'];
}
