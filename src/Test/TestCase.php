<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2016 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Test;


use Phalcon\Di;

/**
 * TestCase
 *
 * @property \Phalcon\Db\Adapter\Pdo\Mysql $db
 * @author heui<hehui@eelly.net>
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase implements \Phalcon\Di\InjectionAwareInterface
{

    /**
     * Dependency Injector
     *
     * @var \Phalcon\DiInterface
     */
    private $dependencyInjector;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $di = Di::getDefault();
        $this->setDI($di);
        $class = substr(get_class($this), 0, - 4);
        if (method_exists($class, 'getInstance')) {
            $di->set('testObject', function () use ($class) {
                return $class::getInstance();
            });
        }
        $di->setShared('db', function () {
            $db = new \Phalcon\Db\Adapter\Pdo\Mysql(array(
                'host' => '172.18.107.96',
                'username' => 'devmall',
                'password' => 'devmall',
                'dbname' => 'malltest'
            ));
            return $db;
        });
    }

    /**
     * Sets the dependency injector
     *
     * @param mixed $dependencyInjector
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->dependencyInjector;
    }


    /**
     * Magic method __get
     *
     * @param string $propertyName
     */
    public function __get($propertyName)
    {
        return $this->getDI()->get(lcfirst($propertyName));
    }

    /**
     * Magic method __set
     *
     * @param string $propertyName
     */
    public function __set($propertyName, $value)
    {
        return $this->$propertyName = $value;
    }

    /**
     * 返回测试对象
     *
     * @return \stdClass
     * @author hehui<hehui@eelly.net>
     * @since  2016年10月27日
     */
    public function getTestObject()
    {
        return $this->testObject;
    }

    /**
     * alias getTestObject
     *
     *
     * @return stdClass
     * @author hehui<hehui@eelly.net>
     * @since  2016年11月12日
     */
    public function gto()
    {
        return $this->getTestObject();
    }
}