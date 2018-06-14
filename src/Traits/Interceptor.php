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

namespace Swallow\Traits;

/**
 * Swallow 拦截器Trait.
 *
 * @author     SpiritTeam
 *
 * @since      2015年1月13日
 *
 * @version    1.0
 */
trait Interceptor
{
    /**
     * 获取单例.
     *
     * @return self
     */
    public static function getInstance()
    {
        static $class = [];
        $called = get_called_class();
        $args = func_get_args();
        $key = $called::getStaticKey($called, $args); // md5($called . ':' . var_export($args, true));
        if (!isset($class[$key])) {
            $class[$key] = \Swallow\Aop\Interceptor::getInstance($called)->setOption($called::getAopOption())->newInstanceArgs($args);
        }

        return $class[$key];
    }

    /**
     * AOP选项.
     *
     * @return int
     */
    protected static function getAopOption()
    {
        return 0;
    }

    /**
     * 获取参数唯一值
     *
     * @param string $className
     * @param array  $args
     *
     * @return string
     */
    protected static function getStaticKey($className, array $args)
    {
        return md5($className.':'.var_export($args, true));
    }
}
