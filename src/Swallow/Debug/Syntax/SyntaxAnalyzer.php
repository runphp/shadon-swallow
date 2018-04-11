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
 * 代码规范检测
 * 
 * @author    liaochu<liaochu@eelly.net>
 * @since     2016-7-15
 * @version   1.0
 */
class SyntaxAnalyzer
{

    /**
     * ReflectionClass 对象
     * @var object
     */
    protected $reflector;

    /**
     * 源代码
     * @var string
     */
    protected $sourceCode;

    /**
     * 文件名
     * @var string
     */
    protected $filename;

    /**
     * tokens
     * @var array
     */
    protected $tokenizer;

    public function init($classname)
    {
        $this->setReflector($classname);
        $this->filename = $this->reflector->getFileName();
        $this->setSourceCode($this->filename);
        $this->setTokenizer($this->sourceCode);
        $this->check();
    }

    /**
     * 校验代码标准
     * 
     * 
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public function check()
    {
        $segments = explode('\\', $this->reflector->name);
        if (!empty($segments[1])) {
            $listenClass = 'Swallow\Debug\Syntax\\'.$segments[1].'Verify';
            if (class_exists($listenClass)) {
                $listen = new $listenClass();
                $listen->process($this);
            }
        }
    }

    /**
     * 设置反射类对象
     * 
     * 
     * @param string $classname
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public function setReflector($classname)
    {
        $this->reflector = new \ReflectionClass($classname);
    }
    
    /**
     * 反射类对象
     * 
     * 
     * @return \ReflectionClass
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public function getReflector()
    {
        return $this->reflector;
    }

    /**
     * 设置类源码
     * 
     * 
     * @param string $filename
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public function setSourceCode($filename)
    {
        $this->sourceCode = file_get_contents($filename);
    }
    
    /**
     * 获取类源码
     * 
     * 
     * @author liaochu<liaochu@eelly.net>
     * @return string
     * @since  2016-7-18
     */
    public function getSourceCode()
    {
        return $this->sourceCode;
    }

    /**
     * token token_get_all()
     * 
     * 
     * @param string $content
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public function setTokenizer($content)
    {
        $this->tokenizer = Tokenizer::init($content);
    }
    
    /**
     * tokens
     * 
     * 
     * @return array
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public function getTokenizer()
    {
        return $this->tokenizer;
    }
}

