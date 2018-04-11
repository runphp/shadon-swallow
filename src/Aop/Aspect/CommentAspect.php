<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Aop\Aspect;

use Swallow\Aop\Joinpoint;
use Swallow\Core\Conf;
use Swallow\Core\Reflection;

/**
 * 拦截器 自动生成服务注释
 *
 * @author     SpiritTeam
 * @since      2015年3月6日
 * @version    1.0
 */
class CommentAspect
{

    /**
     * 标签名
     * @var string
     */
    const TAG = 'comment';

    /**
     * 接入类型
     * @var string
     */
    const TYPE = 'around';

    /**
     * 自动生成服务注释
     *
     * @param  Joinpoint $joinpoit
     */
    public static function run(Joinpoint $joinpoit)
    {
        $debug = Conf::get('System/inc/MODULE_DEBUG');
        if (! $debug) {
            return true;
        }
        $retval = $joinpoit->getReturnValue();
        if (! $joinpoit->isCalled()) {
            $joinpoit->process();
            $retval = $joinpoit->getReturnValue();
        }
        $joinpoit->setReturnValue($retval);
        $args = $joinpoit->getArgs();

        $argsTxt = self::argsComment($args);
        $retvalTxt = self::retvalComment($retval);

        $text = $argsTxt . $retvalTxt . "     *";

        $methodName = $joinpoit->getMethodName();
        $className = $joinpoit->getClassName();
        $methodObj = Reflection::getMethod($className, $methodName);
        $comment = $methodObj->getDocComment();
        $commentNew = str_replace('* @comment', $text, $comment);
        //Trace::dump($commentNew);

        $fileName = $methodObj->getFileName();
        if (file_exists($fileName)) {
            $classText = file_get_contents($fileName);
            $classText = str_replace($comment, $commentNew, $classText);
            $classText = str_replace("\r\n", "\n", $classText);
            $classText = file_put_contents($fileName, $classText);
        }
        //Trace::dump($classText);
    }

    /**
     * 生成参数注释
     *
     * @param array $args
     * @return string
     */
    public static function argsComment($args)
    {
        $argsTxt = "";
        return $argsTxt;
        if (! empty($args) && is_array($args)) {
            $i = 0;
            foreach ($args as $key => $val) {
                $t = $i > 0 ? '     ' : '';
                $argsTxt .= $t . '* @param ' . gettype($val) . ' $' . $key . "\n";
                $i ++;
            }
        }
        return $argsTxt . "     * \n";
    }

    /**
     * 生成返回值注释
     *
     * @param array $retval
     * @return string
     */
    public static function retvalComment($retval, $tree = 0)
    {
        $retvalTxt = $returnTxt = $tmp = $tmpt = '';
        $e = str_repeat("-", 20);
        $line = "     *   " . $e . "|" . $e . "\n";
        if ($tree == 0) {
            $type = isset($retval['retval']) ? gettype($retval['retval']) : gettype($retval);
            if (self::isCatch($retval)) {
                $retvalTxt .= "     * @service\n";
                $returnTxt = "     *\n     * @return " . $type . "\n";
            } else {
                $retvalTxt .= "* @return " . $type . "\n";
                $type == 'array' && $retvalTxt .= "     *\n";
            }
            if ($type == 'array') {
                $retvalTxt .= "     * > 数据说明\n     *   key | value\n";
                $retvalTxt .= $line;
            }
        }
        if (! empty($retval) && is_array($retval)) {
            foreach ($retval as $key => $val) {
                $begin = '     *' . substr_replace(str_repeat(" ", 23) . '|' . str_repeat(" ", 4), $key, 3, strlen($key));
                if (is_array($val)) {
                    if (! is_numeric($key)) {
                        $gettype = gettype($val);
                        $gettype = $gettype == 'array' ? '$' . $key : $gettype;
                        $tmp .= $begin . $gettype . " \n" . $returnTxt;
                        $tmpt .= "     *\n     * > \$" . $key . " 数组说明\n     *   key | value\n" . $line;
                    }
                    $tree ++;
                    if (is_numeric($key)) {
                        $tmpt .= self::retvalComment(current($retval), $tree);
                        break;
                    } else {
                        $tmpt .= self::retvalComment($val, $tree);
                    }
                } else {
                    $str = '';
                    if (self::isCatch($retval)) {
                        switch ($key) {
                            case 'status':
                                $str = '状态码:200';
                                break;
                            case 'info':
                                $str = "提示信息\n     *" . str_repeat(" ", 23) . '|' . str_repeat(" ", 4) . '200: 成功';
                                break;
                            default:
                                $str = '$retval';
                        }
                    }
                    $gettype = $str ? $str : gettype($val);
                    $retvalTxt .= $begin . $gettype . " \n";
                }
            }
        }
        $retvalTxt .= $tmp . $tmpt;
        return $retvalTxt;
    }

    /**
     * 判断是否是Catch
     *
     * @param array|string $retval
     * @return boolean
     */
    public static function isCatch($retval)
    {
        if (! is_array($retval)) {
            return false;
        }
        return count($retval) == 3 && array_keys($retval) == array('status', 'info', 'retval');
    }
}
