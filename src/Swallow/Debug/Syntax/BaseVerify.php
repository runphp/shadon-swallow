<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Debug\Syntax;

/**
 * 验证基类
 * 
 * @author    liaochu<liaochu@eelly.net>
 * @since     2016-7-15
 * @version   1.0
 */
class BaseVerify
{
    /**
     * 类名
     * @var string
     */
    protected $className;
    
    /**
     * process
     * 
     * 
     * @param SyntaxAnalyzer $sa
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public function process(SyntaxAnalyzer $sa) {
        $this->className = $sa->getReflector()->name;
    }


    /**
     * 从源文件tokens获得方法或函数tokens
     * 
     *
     *
     * @param array $tokens
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public function getMethods($tokens)
    {
        $return = array();//结果数组
        $tmp = array();//函数token数组
        $funcName = '';//函数名
        $status = 0;//状态：1获得tokens， 2方法体
        $nests = 0;//嵌套层数
        $getMothodName = 0;//获取方法名
    
        foreach ($tokens as $token) {
            if ($token['type'] == 'T_FUNCTION') {
                //方法节点，开始状态
                $status = 1;
                $getMothodName = 1;
            } elseif ($status == 0 || $token['type'] == 'T_COMMENT') {
                continue;
            }
    
            if ($token['type'] == 'T_OPEN_CURLY_BRACKET') {
                $status = 2;
                $nests += 1;
            } elseif ($token['type'] == 'T_CLOSE_CURLY_BRACKET') {
                $nests -= 1;
            } elseif ($getMothodName && $token['type'] == 'T_STRING') {
                //获得方法名, T_FUNCTION 后的第一个 T_STRING 为函数名
                $getMothodName = 0;
                $funcName = $token['content'];
            } elseif ($status == 2 && $nests === 0) {
                //花括号嵌套深度为0，重置
                $return[$funcName] = $tmp;
                 
                $tmp = array();
                $status = 0;
                $funcName = '';
                continue;
            }
            $tmp[] = $token;
        }
        return $return;
    }
}

