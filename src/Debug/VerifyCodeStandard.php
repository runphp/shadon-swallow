<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Debug;

use Swallow\Core\Cache;
use Swallow\Core\Reflection;

/**
 * 验证代码规范
 *
 * @author     SpiritTeam
 * @since      2015年1月12日
 * @version    1.0
 */
class VerifyCodeStandard
{

    /**
     * 类名
     * @var string
     */
    private static $className;

    /**
     * 文件名
     * @var string
     */
    private static $fileName;

    /**
     * 反射对象
     * @var \ReflectionClass
     */
    private static $reflector;

    /**
     * 方法数组
     * @var array
     */
    private static $methods = array();

    /**
     * 方法限制行数
     * @var int
     */
    private static $line = 250;

    /**
     * 匹配首字母大写 正则
     * @var string
     */
    private static $capital = "/^[A-Z][A-Za-z]*$/";

    /**
     * 匹配首字母小写 正则
     * @var string
     */
    private static $lower = "/^[a-z][A-Za-z]*$/";

    /**
     * 验证类相关规范
     *
     * @param string $className 类全路径
     */
    public static function verify($className)
    {
        self::$className = $className;
        self::$reflector = Reflection::getClass(self::$className);
        self::$fileName = self::$reflector->getFileName();
        $result = self::isVerify();
        if ($result) {
            // 1、验证类命名空间规范
            self::verifyClassNamespace();
            // 2、验证类注释规范
            self::verifyClassDocComment();
            // 3、验证类命名规范
            self::verifyClassName();
            // 4、验证类属性规范
            self::verifyProperty();
            // 5、验证方法规范
            self::verifyMethod();
            // 6、验证通过则持久化 MD5(文件路径) 文件更新时间
            self::saveVerifyResult();
        }
        // 递归父类检查
        while (false != ($parent = self::$reflector->getParentClass())) {
            self::verify($parent->getName());
            self::$reflector = $parent;
        }
    }

    /**
     * 如果文件修改时间 和上次验证时间不一致 重新验证 否 则跳过验证
     */
    private static function isVerify()
    {
        if (substr(self::$className, 0, 8) == 'AopProxy') {
            return false;
        }
        static $filemtimeArr = array();
        $filemtime = filemtime(self::$fileName); // 获取文件修改时间
        $key = self::getCacheKey(self::$fileName);
        if (empty($filemtimeArr[$key])) {
            $filemtimeArr[$key] = $filemtime;
            if (self::getVerifyTime($key) != $filemtimeArr[$key]) {
                return true;
            }
        }
        return false;
    }

    /**
     * 保存验证结果
     * 持久化 MD5(文件路径) 文件更新时间
     */
    private static function saveVerifyResult()
    {
        $filemtime = filemtime(self::$fileName); // 获取文件修改时间
        $Cache = Cache::getInstance()->set(self::getCacheKey(self::$fileName), $filemtime, '_none_');
    }

    /**
     * 获取类文件验证时间
     *
     * @param string $key
     * @return mixed
     */
    private static function getVerifyTime($key)
    {
        $Cache = Cache::getInstance()->get(self::getCacheKey($key), '_none_');
        return $Cache;
    }

    /**
     * 获取缓存键值
     *
     * @param  string $key
     * @return string
     */
    private static function getCacheKey($key)
    {
        return '_vf_' . md5(self::$fileName);
    }

    /**
     * 验证类命名空间规范
     */
    private static function verifyClassNamespace()
    {
        if (self::$reflector->inNamespace()) { // 是否采用命名空间
            $spaceName = self::$reflector->getNamespaceName();
            if (! empty($spaceName)) {
                $spaceNameArr = explode("\\", $spaceName);
                foreach ($spaceNameArr as $name) {
                    if (! preg_match(self::$capital, $name)) {
                        throw new \Exception(self::$className . '：类的命名空间不符合首字母大写驼峰命名规则，请仔细阅读规则文档！');
                    }
                }
            }
        } else {
            throw new \Exception(self::$className . '：命名空间不存在，请仔细阅读规则文档！');
        }
    }

    /**
     * 验证类注释规范
     */
    private static function verifyClassDocComment()
    {
        $docComment = trim(self::$reflector->getDocComment());
        if ($docComment) {
            $msgStatus = false;
            if (substr($docComment, 0, 3) != '/**' || substr($docComment, - 2) != '*/') {
                $msgStatus = true;
            }
            $docArr = array('@author', '@since', '@version');
            $pos = 0;
            foreach ($docArr as $doc) {
                $pos = strpos($docComment, $doc, $pos);
                if (! $pos) {
                    $msgStatus = true;
                    break;
                }
            }
            if($msgStatus){throw new \Exception(self::$className . '：类的注释不符合规则，请仔细阅读规则文档！');}
        } else {
            throw new \Exception(self::$className . '：类的注释不存在，请仔细阅读规则文档！');
        }
    }

    /**
     * 验证类命名规范
     */
    private static function verifyClassName()
    {
        $name = self::$reflector->getShortName();
        if (! preg_match(self::$capital, $name)) {
            throw new \Exception(self::$className . '：类命名不符合首字母大写驼峰命名规则，请仔细阅读规则文档！');
        }
    }

    /**
     * 验证类属性规范
     */
    private static function verifyProperty()
    {
        $properties = self::$reflector->getProperties();
        foreach ($properties as $property) {
            self::verifyPropertyName($property);
            self::verifyPropertyDocComment($property);
        }
    }

    /**
     * 验证类属性值
     *
     * @param \ReflectionProperty $property
     */
    private static function verifyPropertyName(\ReflectionProperty $property)
    {
        $name = $property->getName();
        if (! preg_match(self::$lower, $name)) {
            throw new \Exception(self::$className . '：类的 ' . $name . ' 属性不符合首字母小写驼峰命名规则，请仔细阅读规则文档！');
        }
    }

    /**
     * 验证类属性注释
     *
     * @param \ReflectionProperty $property
     */
    private static function verifyPropertyDocComment(\ReflectionProperty $property)
    {
        $docComment = $property->getDocComment();
        if (substr($docComment, 0, 3) != '/**' || substr($docComment, - 2) != '*/') {
            throw new \Exception(self::$className . '：属性' . $property->getName() . ' 的注释不符合规则，请仔细阅读规则文档！');
        }

        if (! strpos($docComment, '@var')) {
            throw new \Exception(self::$className . '：属性' . $property->getName() . ' 没有定义@var');
        }
    }

    /**
     * 验证方法相关规范
     */
    private static function verifyMethod()
    {
        foreach (self::$reflector->getMethods() as $method) {
            if ($method->class == self::$className) {
                $name = $method->getName();
                $reflectionMethod = Reflection::getMethod($method->class, $name);
                // 1、验证方法注释规范
                self::verifyMethodDocComment($reflectionMethod, $name);
                // 2、验证方法命名规范
                self::verifyMethodName($reflectionMethod, $name);
                // 3、验证方法修饰符 ------暂时无法判断是否写修饰符--------
                self::verifyMethodModifier($reflectionMethod, $name);
                // 4、验证方法行数大小
                self::verifyMethodLine($reflectionMethod, $name);
            }
        }
    }

    /**
     * 验证方法注释规范
     *
     * @param \ReflectionMethod $reflectionMethod
     * @param string $name
     */
    private static function verifyMethodDocComment(\ReflectionMethod $reflectionMethod, $name)
    {
        $docComment = $reflectionMethod->getDocComment();
        if ($docComment) {
            $msgStatus = false;
            if (substr($docComment, 0, 3) != '/**' || substr($docComment, - 2) != '*/') {
                if($msgStatus){
                    throw new \Exception(self::$className . '：方法 ' . $name . ' 的注释不符合规则，请仔细阅读规则文档！');
                }
            }

            self::verifyAnnotations($name, $docComment);

            // 获取方法参数
            $parameters = $reflectionMethod->getParameters();
            $pos = 0;
            if (empty($parameters)) {
                return;
            }
            foreach ($parameters as $meter) {
                $pos = strpos($docComment, '$' . $meter->name, $pos);
                if (! $pos) {
                    throw new \Exception(self::$className . '：类的 ' . $name . ' 方法的注释不符合规则，请仔细阅读规则文档！');
                    break;
                }
                // 验证方法参数规范
                if (! preg_match(self::$lower, $meter->name)) {
                    throw new \Exception(self::$className . '：类的 ' . $name . ' 方法的 $' . $meter->name . ' 参数不符合首字母小写驼峰命名规则，请仔细阅读规则文档！');
                    break;
                }
            }
        } else {
            throw new \Exception(self::$className . '：方法 ' . $name . ' 的注释不存在，请仔细阅读规则文档！');
        }
    }

    /**
     * 验证注解使用规则
     *
     * @param string $name
     * @param string $docComment
     */
    private static function verifyAnnotations($name, $docComment)
    {
        $className = explode('\\', self::$className);
        if (strpos($docComment, '* @catch') && $className[1] != 'Service') {
            throw new \Exception(self::$className . '：方法 ' . $name . ' 的@catch注解使用不正确，请仔细阅读规则文档！');
        }
        if (strpos($docComment, '* @comment') && $className[1] != 'Service') {
            throw new \Exception(self::$className . '：方法 ' . $name . ' 的@comment注解使用不正确，请仔细阅读规则文档！');
        }
        if (strpos($docComment, '* @async') && $className[1] != 'Service') {
            throw new \Exception(self::$className . '：方法 ' . $name . ' 的@async注解使用不正确，请仔细阅读规则文档！');
        }
        if (strpos($docComment, '* @cache') && ! in_array($className[1], array('Logic', 'Model'))) {
            throw new \Exception(self::$className . '：方法 ' . $name . ' 的@cache注解使用不正确，请仔细阅读规则文档！');
        }
        if (strpos($docComment, '* @trans') && ! in_array($className[1], array('Logic', 'Model'))) {
            throw new \Exception(self::$className . '：方法 ' . $name . ' 的@trans注解使用不正确，请仔细阅读规则文档！');
        }
    }

    /**
     * 验证方法命名规范
     *
     * @param \ReflectionMethod $reflectionMethod
     * @param string $name
     */
    private static function verifyMethodName(\ReflectionMethod $reflectionMethod, $name)
    {
        if (! $reflectionMethod->isConstructor() && ! $reflectionMethod->isDestructor() && ! preg_match(self::$lower, $name)) {
            throw new \Exception(self::$className . '：类的 ' . $name . ' 方法不符合首字母小写驼峰命名规则，请仔细阅读规则文档！');
        }
    }

    /**
     * 验证方法修饰符   ------暂时无法判断是否写修饰符--------
     *
     * @param \ReflectionMethod $reflectionMethod
     * @param string $name
     */
    private static function verifyMethodModifier(\ReflectionMethod $reflectionMethod, $name)
    {
        /*
         * $modifiers = implode(' ', \Reflection::getModifierNames($reflectionMethod->getModifiers())); echo $modifiers.'='.$name; echo '<br>';
         */
    }

    /**
     * 验证方法行数大小
     *
     * @param ReflectionMethod $reflectionMethod
     * @param string $name
     */
    private static function verifyMethodLine(\ReflectionMethod $reflectionMethod, $name)
    {
        $endLine = $reflectionMethod->getEndLine();
        $startLine = $reflectionMethod->getStartLine();
        if ($endLine - $startLine > self::$line) {
            throw new \Exception(self::$className . '：类的 ' . $name . ' 方法不符合行数大小限制规则，请仔细阅读规则文档！');
        }
    }
}