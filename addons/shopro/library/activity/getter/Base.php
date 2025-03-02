<?php

namespace addons\shopro\library\activity\getter;

use addons\shopro\library\activity\contract\ActivityGetterInterface;
use addons\shopro\library\activity\Activity as ActivityManager;

abstract class Base implements ActivityGetterInterface
{
    protected $manager = null;
    protected $model = null;

    public function __construct(ActivityManager $activityManager) 
    {
        $this->manager = $activityManager;

        $this->model = $activityManager->model;
        $this->redis = $activityManager->redis;
    }
}