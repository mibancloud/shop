<?php

namespace addons\shopro\filter\data;

use addons\shopro\filter\BaseFilter;
use think\db\Query;

/**
 * 富文本筛选
 */
class RichtextFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'title'];
}
