<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Service;

use Swallow\Traits\PublicObject;

class Logic extends \Swallow\Di\Injectable
{

    use PublicObject;
    
    /**
     * construct
     * 
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年10月13日
     */
    public final function __construct()
    {
        if (method_exists($this, "onConstruct")) {
            $this->onConstruct();
        }
    }

    /**
     * @return self
     * 
     * @param $isNewInstance
     * @author 何辉<hehui@eely.net>
     * @since  2015年8月26日
     */
    public static function getInstance($isNewInstance = false)
    {
        $className = static::class; //get_called_class()
        $defaultDi = \Phalcon\Di::getDefault();
        $proxyObject = $defaultDi->getShared('Swallow\Service\LogicProxy');
        $classObj = ($isNewInstance === false) ? $defaultDi->getShared($className) : $defaultDi->get($className);
        $proxyObject = $proxyObject->setProxyObject($classObj);
        if (APP_DEBUG) {
            $verify = $defaultDi->getShared('\Swallow\Debug\VerifyBack');
            $verify->callClass($className);            
            //代码规范检测
//             $syntaxAnalyzer = $defaultDi->getShared('\Swallow\Debug\Syntax\SyntaxAnalyzer');
//             $syntaxAnalyzer->init($className);
        }
        return $proxyObject;
    }
}
