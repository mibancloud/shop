<?php

namespace addons\shopro\filter\goods;

use addons\shopro\filter\BaseFilter;
use think\db\Query;

/**
 * 服务保障筛选
 */
class ServiceFilter extends BaseFilter
{
    protected $keywordFields = ['name', 'description'];
}
