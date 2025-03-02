<?php

namespace addons\shopro\exception;

use Exception;

/**
 * 抛出正常业务错误 不记录日志，开发环境/生产环境都显示错误信息
 */
class ShoproException extends Exception
{
}
