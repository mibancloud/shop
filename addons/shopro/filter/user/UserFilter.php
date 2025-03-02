<?php

namespace addons\shopro\filter\user;

use addons\shopro\filter\BaseFilter;
use think\db\Query;

/**
 * 用户管理
 */
class UserFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'username', 'nickname', 'mobile', 'email'];
}
