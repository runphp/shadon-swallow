<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Annotations;

/**
 * Annotation解析
 *
 * @author     SpiritTeam
 * @since      2015年1月15日
 * @version    1.0
 */
class Parse
{

    /**
     * 解析
     * 
     * @param  \ReflectionClass $obj
     * @return array
     */
    public static function init(\ReflectionClass $obj)
    {
        $data = array();
        /**
         * @var $method \ReflectionMethod
         */
        foreach ($obj->getMethods() as $method) {
            $data[$method->name] = self::analyze($method->getDocComment());
        }
        return $data;
    }

    /**
     * 分析
     * 
     * @param  string $doc
     * @return array
     */
    private static function analyze($doc)
    {
        $doc = trim($doc, '/* ');
        $doc = preg_split('/\s+\*\s(\@\w+)/', $doc, 0, PREG_SPLIT_DELIM_CAPTURE);
        //去掉注释头
        array_shift($doc);
        if (empty($doc)) {
            return array();
        }
        $data = array();
        $keyCount = array();
        $count = count($doc);
        for ($i = 0; $i < $count; $i ++) {
            if (0 == $i % 2) {
                $key = substr($doc[$i], 1);
                $data[$key] = isset($data[$key]) ? $data[$key] : array();
            } else {
                if (! empty($doc[$i]) && '.' == $doc[$i]{0}) {
                    $spacePos = strpos($doc[$i], ' ');
                    $name = false === $spacePos ? substr($doc[$i], 1) : substr($doc[$i], 1, $spacePos);
                    $doc[$i] = false === $spacePos ? '' : trim(substr($doc[$i], $spacePos));
                } else {
                    $name = isset($keyCount[$key]) ? ++ $keyCount[$key] : ($keyCount[$key] = 0);
                }
                $data[$key][$name] = $doc[$i] = trim($doc[$i], " *\r\n");
                if (empty($doc[$i])) {
                    $data[$key][$name] = true;
                } elseif ('(' == $doc[$i]{0}) {
                    $doc[$i] = str_replace(',', '&', $doc[$i]);
                    $doc[$i] = substr($doc[$i], 1, strrpos($doc[$i], ')') - 1);
                    parse_str($doc[$i], $data[$key][$name]);
                }
            }
        }
        return $data;
    }
}
