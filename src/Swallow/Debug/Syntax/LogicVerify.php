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
 * Logic层代码规范检测
 * 
 * @author    liaochu<liaochu@eelly.net>
 * @since     2016-7-15
 * @version   1.0
 */
class LogicVerify extends BaseVerify
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
        $this->detectMethod($sa->getTokenizer());
    }
    
    /**
     * 检测方法
     * 
     * 
     * @param array $tokens
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public function detectMethod($tokens)
    {
        $methods = $this->getMethods($tokens);
        $namespace = $this->getNamespace($tokens);
        
        foreach ($methods as $methodName => $methodTokens) {
            if (!$this->detectNests($methodTokens)) {
//                 throw new \Exception('嵌套层数过多, 类名：' . $this->className . ', 方法名：' . $methodName);
            }
            
            if (!$this->detectReturns($methodTokens)) {
//                 throw new \Exception('return 过多, 类名：' . $this->className . ', 方法名：' . $methodName);
            }
            
            if (!$this->detectLoop($methodTokens)) {
                throw new \Exception('不能循环操作Model、Serivce, 类名：' . $this->className . ', 方法名：' . $methodName);
            }
            
            if (!$this->detectUseModelParentMethod($methodTokens, $namespace)) {
                throw new \Exception('Logic层使用了Model层非自定义的方法, 类名：' . $this->className . ', 方法名：' . $methodName);
            }
            
        }
    }   
    
   /**
    * 检测方法return次数不大于2，判断token['type'] == T_RETURN
    * 
    * 
    * @param array $tokens
    * @return boolean
    * @author liaochu<liaochu@eelly.net>
    * @since  2016-7-18
    */
    public function detectReturns($tokens)
    {
        $times = 0;//return 次数
        foreach ($tokens as $token) {
            if ($token['type'] == 'T_RETURN') {
                $times += 1;
            }
        }
        if ($times <= 2)
            return true;
        return false;
    }
    
    /**
     * 检测方法流程控制嵌套深度是否不大于3,判断花括号
     * 
     * 
     * @param array $tokens
     * @return boolean
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public function detectNests($tokens)
    {
        $deep = 0;//当前嵌套深度
        $max = 0;//最大深度
        foreach ($tokens as $token) {
            if ($token['type'] == 'T_OPEN_CURLY_BRACKET') {                
                $deep += 1;
                $max = max($deep, $max);
            } elseif ($token['type'] == 'T_CLOSE_CURLY_BRACKET') {
                $deep -= 1;
            }
        }

        if ($max <= 4) {
            //function() {} 方法体花括号不计算层数
            return true;
        }
        return false;
    }
    

    /**
     * 检测循环内调用Model、Service层
     * 获取foreach,for,while,do-while内的代码，匹配模型层操作
     *
     *
     * @param array $tokens
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-19
     */
    public function detectLoop($tokens)
    {       
        $loopCode = [];//循环操作内的tokens数组
        $tmp = [];
        $inloop = false;//在循环操作内
        $nests = 0;//嵌套层数
        foreach ($tokens as $token) {
            if (in_array($token['type'], array('T_FOREACH', 'T_FOR', 'T_WHILE'))) {
                //进入循环
                $inloop = true;
            } elseif ($inloop && $token['type'] == 'T_OPEN_CURLY_BRACKET') {
                //{ 嵌套层数加1
                $nests += 1;
            } elseif ($inloop && $token['type'] == 'T_CLOSE_CURLY_BRACKET') {
                //} 嵌套层数减1
                $nests -= 1; 
                $tmp[] = $token;
                if (!$nests) {
                    //循环结束,重置                    
                    $loopCode[] = $tmp;
                    $inloop = false;
                    $tmp = [];                    
                }
            }
            $inloop && $tmp[] = $token;
        }

        foreach ($loopCode as $val) {
            //for|while|foreach {...}
            $code = implode('', array_column($val, 'content'));
            //是否存在获得Model、Service层的实例
        	if (strpos($code, 'Model::getInstance()') || strpos($code, 'Service::getInstance()')) {
        		return false;
        	}
        }

        return true;        
    }
    
    /**
     * 检测Logic层是否使用模型层父方法
     * 
     * 
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-19
     */
    public function detectUseModelParentMethod($tokens, $namespace)
    {
        //方法代码
        $code =  implode('', array_column($tokens, 'content'));
        //获得类名 NewsCategoryModel::getInstance()->getAllCateId()
        $funcGetClassname = function ($line) use ($namespace) {
        	if ($pos = strpos($line, '::')) {
        		$classname = substr($line, 0, $pos);
        		return isset($namespace[$classname]) ? $namespace[$classname] : $classname;
        	} else {
        		return false;
        	}
        };
        if (!preg_match_all('/[^\s]+Model::getInstance[^;]*/s', $code, $matches)) {
        	return true;
        }
        
        foreach ($matches[0] as $line) {
            if (preg_match_all('/->([^\(]+)\(/s', $line, $subMatches)) {
                //model 类名
                $classname = $funcGetClassname($line);
                //model 反射类对象
                $reflectObj = \Swallow\Core\Reflection::getClass($classname);
                //类方法名数组
                $methods = $this->getClassProtoMethods($reflectObj);
                	
                if (!in_array($subMatches[1][0], $methods)) {
                	return false;
                }
            }
             
             
        }
        
        return true;
    }
    
    /**
     * 获得类的原生方法(非继承)
     * 
     * 
     * @param \ReflectionClass $obj
     * @return array
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-19
     */
    private function getClassProtoMethods(\ReflectionClass $obj)
    {
        $rst = [];
    	foreach ($obj->getMethods() as $item) {
    		if ($item->class == $obj->name) {
    			$rst[] = $item->name;
    		}
    	}
    	return $rst;
    }
    
    /**
     * 获得命名空间数组
     * 
     * 
     * @param array $tokens
     * @return array
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-19
     */
    public function getNamespace($tokens) {
        $rst = [];
        $namespaces = [];
        $line = 0;
    	foreach ($tokens as $token) {
    		if ($token['type'] == 'T_USE') {
    			$namespaces[$token['line']] = $token['content'];
    			$line = $token['line'];
    		} elseif ($line == $token['line']) {
    		    $namespaces[$token['line']] .= $token['content'];
    		}
    	}
    	
    	$result = [];
    	foreach ($namespaces as $classname) {
    	    //[11] => use Swallow\Toolkit\Net\Curl;
    	    //去掉use
    	    $classname = preg_replace('/use\s/i', '', $classname);
    	    if ($pos = strpos($classname, ';')) {
    	        //去掉;
    	        $classname = substr($classname, 0, $pos);	
    	    }

            $fragments = explode('\\', trim($classname, '\\'));
            //[Curl] => Swallow\Toolkit\Net\Curl
            $result[array_pop($fragments)] = $classname;
    	}
    	
    	return $result;
    }
}

