<?php

namespace addons\shopro\filter;

use think\db\Query;

/**
 * 优惠券
 */
class CouponFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'name'];
}
