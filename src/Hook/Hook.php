<?php

/*
 * 
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Hook;

use Swallow\Core\Conf;

/**
 * 钩子
 * 
 * @author    zengzhihao<zengzhihao@eelly.net>
 * @since     2016年3月10日
 * @version   1.0
 */
class Hook
{

    private static $tags = [];

    /**
     * 动态添加插件到某个标签
     * @param string $tag 标签名称
     * @param mixed $name 插件名称
     * @return void
     */
    static public function add($tag, $name)
    {
        if (! isset(self::$tags[$tag])) {
            self::$tags[$tag] = [];
        }
        if (is_array($name)) {
            self::$tags[$tag] = array_merge(self::$tags[$tag], $name);
        } else {
            self::$tags[$tag][] = $name;
        }
    }

    /**
     * 批量导入
     * @param array $data 信息
     * @param boolean $recursive 是否递归合并
     * @return void
     */
    static public function import($data = [], $recursive = true)
    {
        empty($data) && $data = Conf::get('Swallow/tags');
        if (empty($data)) {
            return false;
        }
        
        if (! $recursive) { // 覆盖导入
            self::$tags = array_merge(self::$tags, $data);
        } else { // 合并导入
            foreach ($data as $tag => $val) {
                ! isset(self::$tags[$tag]) && self::$tags[$tag] = [];
                // 合并模式
                self::$tags[$tag] = array_merge(self::$tags[$tag], $val);
            }
        }
    }

    /**
     * 监听标签
     * @param string $tag 标签
     * @param mixed $params 传入参数
     * @return void
     */
    static public function listen($tag, $params = null)
    {
        if (isset(self::$tags[$tag])) {
            foreach (self::$tags[$tag] as $val) {
                self::exec($val['class'], $tag, $params, isset($val['model']) ? $val['model'] : '');
            }
            return true;
        }
        return false;
    }

    /**
     * 执行某个行为
     * @param string $name 行为名称
     * @param string $tag 方法名（标签名）     
     * @param Mixed $params 传入的参数
     * @param string $modul 模块
     * @return void
     */
    static public function exec($name, $tag, $params = null, $modul = '')
    {
        empty($modul) && $modul = 'MessageQueue';
        $classPath = ucfirst($modul) . '\\Behavior\\' . ucfirst($name) . 'Behavior';
        $tag = str_replace("\r", '', lcfirst(ucwords(str_replace('_', "\r", $tag))));
        if (class_exists($classPath)) {
            $class = $classPath::getInstance();
            if (method_exists($class, $tag)) {
                return $class->$tag($params);
            }
        }
        return false;
    }
}
