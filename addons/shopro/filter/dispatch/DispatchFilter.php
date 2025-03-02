<?php

namespace addons\shopro\filter\dispatch;

use addons\shopro\filter\BaseFilter;
use think\db\Query;

/**
 * 配送方式筛选
 */
class DispatchFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'name'];
}
