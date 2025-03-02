<?php

namespace addons\shopro\filter\data;

use addons\shopro\filter\BaseFilter;
use think\db\Query;

/**
 * 快递公司筛选
 */
class ExpressFilter extends BaseFilter
{
    protected $keywordFields = ['id','name','code'];
}
