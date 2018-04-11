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
 * Service层代码规范检测
 * 
 * @author    liaochu<liaochu@eelly.net>
 * @since     2016-7-15
 * @version   1.0
 */
class ServiceVerify extends BaseVerify
{
    /**
     * process
     * 
     * 
     * @param SyntaxAnalyzer $sa
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public function process(SyntaxAnalyzer $sa)
    {
        parent::process($sa);
        
        $this->detectMethods($sa->getTokenizer());
    }
    
    /**
     * 检测类方法规范
     * 
     * 
     * @param unknown $tokens
     * @throws \Exception
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public function detectMethods($tokens)
    {

        $methods = $this->getMethods($tokens);
        
        foreach ($methods as $methodName => $methodTokens) {
           if (!$this->detectHasControl($methodTokens)) {
//                throw new \Exception('服务层不做传入参数校验, 类名：' . $this->className . ', 方法名：' . $methodName);
           }
        }

    }
    
    /**
     * 检测服务层是否做参数校验
     * 检测service类方法只存在return，没有if,&&,||,?等流程控制
     * 
     * 
     * @param array $tokens
     * @return boolean
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public function detectHasControl($tokens)
    {
        $hasReturn = 0;
        $hasControl = 0;
        foreach ($tokens as $token) {
            if ($token['type'] == 'T_RETURN') {
                $hasReturn = 1;
            } elseif (in_array($token['type'], ['T_IF', 'T_BOOLEAN_AND', 'T_BOOLEAN_OR', 'T_INLINE_THEN'])) {
                $hasControl = 1;
            }
        }   
        if ($hasReturn && !$hasControl) {
            return true;
        }     
        return false;
    }

}

