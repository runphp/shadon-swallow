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

namespace Swallow\Service;

use Swallow\Traits\PublicObject;

class Logic extends \Swallow\Di\Injectable
{
    use PublicObject;

    /**
     * construct.
     *
     * @author 范世军<fanshijun@eelly.net>
     *
     * @since  2015年10月13日
     */
    final public function __construct()
    {
        if (method_exists($this, 'onConstruct')) {
            $this->onConstruct();
        }
    }

    /**
     * @param $isNewInstance
     *
     * @return static
     *
     * @author 何辉<hehui@eely.net>
     *
     * @since  2015年8月26日
     */
    public static function getInstance($isNewInstance = false)
    {
        $className = static::class; //get_called_class()
        $defaultDi = \Phalcon\Di::getDefault();
        $proxyObject = $defaultDi->getShared('Swallow\Service\LogicProxy');
        $classObj = (false === $isNewInstance) ? $defaultDi->getShared($className) : $defaultDi->get($className);
        $proxyObject = $proxyObject->setProxyObject($classObj);
        if (APP_DEBUG) {
            $verify = $defaultDi->getShared('\Swallow\Debug\VerifyBack');
            $verify->callClass($className);
        }

        return $proxyObject;
    }
}
