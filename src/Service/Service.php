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

use Swallow\Exception\LogicException;
use Swallow\Exception\StatusCode;
use Swallow\Exception\SystemException;

class Service extends \Swallow\Di\Injectable
{
    /**
     * @var bool
     */
    protected static $isNewInstance;

    /**
     * construct.
     *
     * @author chenjinggui<chenjinggui@eelly.net>
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
     * @param string $method 方法
     * @param array  $args   参数
     */
    public function __call($method, $args)
    {
        $logicName = str_replace('\\Service\\', '\\Logic\\', static::class);
        $logicName = preg_replace('/Service$/', 'Logic', $logicName);

        return $this->assemble($logicName, $method, $args);
    }

    /**
     * @return self
     *
     * @author chenjinggui<chenjinggui@eely.net>
     *
     * @since  2015年8月26日
     */
    public static function getInstance($isNewInstance = false)
    {
        $className = static::class;
        $defaultDi = \Phalcon\Di::getDefault();
        $service = (false === $isNewInstance) ? $defaultDi->getShared($className) : $defaultDi->get($className);
        self::$isNewInstance = $isNewInstance;
        if (APP_DEBUG) {
            $verify = $defaultDi->getShared('\Swallow\Debug\VerifyBack');
            $verify->callClass($className);
        }

        return $service;
    }

    /**
     * 返回处理.
     *
     * @param $className
     * @param $method
     * @param $args
     *
     * @return array
     *
     * @author 范世军<fanshijun@eelly.net>
     *
     * @since  2015年10月26日
     */
    protected function assemble($logicName, $method, $args = [])
    {
        $retval = ['status' => StatusCode::OK, 'info' => '', 'retval' => null];
        try {
            $logic = $logicName::getInstance(self::$isNewInstance);
            $return = call_user_func_array([$logic, $method], $args);
            $retval['retval'] = $return;
        } catch (LogicException $e) {
            $retval['info'] = $e->getMessage();
            $retval['status'] = $e->getCode();
            $retval['retval'] = $e->getArgs();
        } catch (\Phalcon\Mvc\Model\Exception $e) {
            $retval['info'] = '系统繁忙！';
            $retval['status'] = $e->getCode();
            $retval['retval'] = null;
        } catch (SystemException $e) {
            $retval['info'] = '程序内部错误';
            $retval['status'] = $e->getCode();
            $retval['retval'] = null;
        } catch (\ErrorException $e) {
            throw $e;
        }
        if (isset($e)) {
            $retval['throw'] = get_class($e);
        }

        return $retval;
    }
}
