<?php

namespace app\admin\validate\shopro\traits;


/**
 * 自定义验证规则
 */
trait CustomRule
{

    /**
     * requireIf 的扩展， 可以同时验证多个值
     *
     * @param mixed $value
     * @param mixed $rule
     * @param array $data
     * @return bool
     */    
    protected function requireIfAll($value, $rule, $data = [])
    {
        $ruleArr = explode(',', $rule);
        $ifField = $ruleArr[0];
        unset($ruleArr[0]);
        if (in_array($data[$ifField], $ruleArr)) {
            return $value ? true : false;
        } else {
            return true;
        }
    }
}
