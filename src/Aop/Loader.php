<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swallow\Aop;

use Swallow\Annotations\Annotation;
use Swallow\Annotations\Cache;
use Swallow\Annotations\Method;
use Swallow\Traits\Singleton;

/**
 * Aop 文件生成与加载类.
 *
 * @author     SpiritTeam
 *
 * @since      2015年1月15日
 *
 * @version    1.0
 */
class Loader
{
    use Singleton;

    /**
     * 缓存路径.
     *
     * @var string
     */
    public static $cacheDir = '';

    /**
     * 注解映射.
     *
     * @var array
     */
    public static $aspect = [];

    /**
     * 反射的类名.
     *
     * @var \ReflectionClass
     */
    private static $class = null;

    /**
     * 选项值
     *
     * @var int
     */
    private static $option = 0;

    /**
     * 类名.
     *
     * @var string
     */
    private static $className = '';

    /**
     * 代理类名.
     *
     * @var string
     */
    private static $proxyName = '';

    /**
     * 代理文件路径.
     *
     * @var string
     */
    private static $proxyFilePath = '';

    /**
     * 已加载类.
     *
     * @var array
     */
    private static $loaded = [];

    /**
     * 加载文件.
     *
     * @param \ReflectionClass $class
     * @param int              $option
     *
     * @return \ReflectionMethod
     */
    public static function load(\ReflectionClass $class, $option = 0)
    {
        self::$class = $class;
        self::$option = $option;
        self::$className = $class->name;
        self::$proxyName = Proxy::getProxyName(self::$className);
        if (isset(self::$loaded[self::$proxyName])) {
            return self::$loaded[self::$proxyName];
        }
        self::$proxyFilePath = self::$cacheDir.'/'.str_replace('\\', '/', self::$proxyName).'.php';
        if (!self::checkVaild()) {
            self::build();
        }
        include self::$proxyFilePath;
        $constructor = $class->getConstructor();
        if ($constructor && !$constructor->isPublic()) {
            $constructor->setAccessible(true);
        }
        self::$loaded[self::$proxyName] = $constructor;

        return self::$loaded[self::$proxyName];
    }

    /**
     * 检查代理文件是否正常.
     *
     * @return bool
     */
    private static function checkVaild()
    {
        $classPath = self::$class->getFileName();
        $proxyPath = self::$proxyFilePath;
        if (!file_exists($proxyPath)) {
            return false;
        }
        $classMTime = filemtime($classPath);
        $proxyMTime = filemtime($proxyPath);
        if ($classMTime > $proxyMTime) {
            return false;
        }
        $loaderMTime = filemtime(__FILE__);
        if ($loaderMTime > $proxyMTime) {
            return false;
        }
        //寻找父类
        if (!(self::$option & Option::SKIP_PARENT_METHOD)) {
            /**
             * @var \ReflectionClass $parent
             */
            $parent = self::$class;
            while (false != ($parent = $parent->getParentClass())) {
                $file = $parent->getFileName();
                $classMTime = filemtime($file);
                if ($classMTime > $proxyMTime) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 开始生成文件.
     */
    private static function build(): void
    {
        $proxyClassPos = strrpos(self::$proxyName, '\\');
        $proxyClass = substr(self::$proxyName, $proxyClassPos + 1);
        $proxyClassPath = substr(self::$proxyName, 0, $proxyClassPos);
        //删除Annotation缓存
        Cache::delete(self::$className.'_Annotation');
        $annotations = Annotation::getInstance(self::$className);
        $content = '<?php'.PHP_EOL.'namespace '.$proxyClassPath.';';
        $content .= PHP_EOL.'use Swallow\Aop\Proxy;';
        $content .= PHP_EOL.'use '.self::$className.' as Traget;';
        $content .= PHP_EOL.'class '.$proxyClass;
        $content .= ' extends Traget';
        $content .= PHP_EOL.'{';
        $content .= PHP_EOL.'    public $__JoinPoints=null;';
        $classed = 'Traget::';
        $isSkipProtectedMethod = (bool) (self::$option & Option::SKIP_PROTECTED_METHOD);
        $isSkipPrivateMethod = (bool) (self::$option & Option::SKIP_PRIVATE_METHOD);
        $isSkipExtendMethod = (bool) (self::$option & Option::SKIP_PARENT_METHOD);
        /**
         * @var $method \ReflectionMethod
         */
        foreach (self::$class->getMethods() as $method) {
            //过滤最终方法和静态方法
            if ($method->isFinal() || $method->isStatic()) {
                continue;
            }
            //过滤最终方法和静态方法
            if ($isSkipProtectedMethod && $method->isProtected()) {
                continue;
            }
            //过滤最终方法和静态方法
            if ($isSkipPrivateMethod && $method->isPrivate()) {
                continue;
            }
            //按须不处理继承
            if ($isSkipExtendMethod && $method->class != self::$className) {
                continue;
            }
            $methodAnno = $annotations->getMethod($method->name);
            $aops = [];
            $aops['before'] = $methodAnno->getAttrs('before');
            $aops['around'] = $methodAnno->getAttrs('around');
            $aops['after'] = $methodAnno->getAttrs('after');
            self::parseAspect($aops, $methodAnno);
            if ($aops['before'] || $aops['around'] || $aops['after']) {
                $aops['before'] = self::getAnnoString($aops['before']);
                $aops['around'] = self::getAnnoString($aops['around']);
                $aops['after'] = self::getAnnoString($aops['after']);
                $aops = "'before'=>{$aops['before']},'around'=>{$aops['around']},'after'=>{$aops['after']}";
            } else {
                $aops = '';
            }
            $paramsArgs = '';
            $paramsCall = '';
            $paramsCount = 0;
            $callbackArgs = '';
            /**
             * @var $param \ReflectionParameter
             */
            foreach ($method->getParameters() as $param) {
                $tmp = $param->isPassedByReference() ? '&$' : '$';
                $class = $param->getClass();

                if ($class) {
                    $class = '\\'.$class->name;
                } elseif ($param->isArray()) {
                    $class = 'array ';
                } elseif ($param->isCallable()) {
                    $class = 'callable ';
                } else {
                    $class = (string) $param->getType().' ';
                }
                $paramsArgs .= ','.$class.$tmp.$param->name.
                     ($param->isDefaultValueAvailable() ? ('='.var_export($param->getDefaultValue(), true)) : '');
                $paramsCall .= ',\''.$param->name.'\'=>'.$tmp.$param->name;
                $callbackArgs .= ',$args[\''.$param->name.'\']';
                $paramsCount++;
            }
            if ($paramsCount) {
                $paramsArgs = substr($paramsArgs, 1);
                $paramsCall = substr($paramsCall, 1);
                $callbackArgs = substr($callbackArgs, 1);
            }
            $modifiers = implode(' ', \Reflection::getModifierNames($method->getModifiers()));
            $content .= PHP_EOL.'    '.$modifiers.' function '.$method->name.'('.$paramsArgs.')';
            if ($returnType = $method->getReturnType()) {
                $content .= ': '.$returnType;
            }
            $content .= PHP_EOL.'    {       return Proxy::invoke($this,\''.$method->name.'\',function($args) {';
            $content .= PHP_EOL.'            return '.$classed.$method->name.'('.$callbackArgs.');';
            $content .= PHP_EOL.'        }, ['.$paramsCall.'],['.$aops.']);';
            $content .= PHP_EOL.'    }';
        }
        $content .= PHP_EOL.'}';
        self::putFile($content);
    }

    /**
     * 处理aop其它切入点.
     *
     * @param array  $aops
     * @param Method $anno
     *
     * @return array
     */
    private static function parseAspect(array &$aops, Method $anno)
    {
        static $tags = null;
        if (!isset($tags)) {
            foreach (self::$aspect as $value) {
                $tags[$value::TAG] = $value;
            }
        }
        $attrs = $anno->getAttrs();
        foreach ($attrs as $key => $value) {
            if (!isset($tags[$key])) {
                continue;
            }
            $aspect = $tags[$key];
            $type = $aspect::TYPE;
            !is_array($aops[$type]) && $aops[$type] = [];
            $aops[$type][] = $aspect.'::run';
        }
    }

    /**
     * 转换拦截器内容.
     *
     * @param mixed $content
     *
     * @return string
     */
    private static function getAnnoString($annotations)
    {
        if (empty($annotations)) {
            return 'array()';
        }
        $annotations = var_export($annotations, true);

        return $annotations;
    }

    /**
     * 写入文件.
     *
     * @param string $content
     *
     * @return bool
     */
    private static function putFile($content)
    {
        $paths = self::$proxyFilePath;
        if (!file_exists($paths)) {
            $start = strlen(self::$cacheDir);
            while (false !== ($search = strpos($paths, '/', $start))) {
                $path = substr($paths, 0, $search);
                if (!file_exists($path)) {
                    mkdir($path);
                }
                $start = $search + 1;
            }
        }
        file_put_contents($paths, $content);
    }
}
