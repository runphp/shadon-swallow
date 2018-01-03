<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Di;

/**
 * Service
 *
 * @author    SpiritTeam
 * @since     2015年3月10日
 * @version   1.0
 */
class Service implements ServiceInterface
{

    /**
     * 服务名
     * @var string
     */
    protected $name;

    /**
     * 服务命名空间
     * @var string
     */
    protected $definition;

    /**
     * 是否单例
     * @var bool
     */
    protected $shared = false;

    /**
     * 单例对象
     * @var object
     */
    protected $sharedInstance = null;

    /**
     * 构造方法
     *
     */
    public final function __construct($name, $definition, $shared = false)
    {
        $this->name = $name;
        $this->definition = $definition;
        $this->shared = $shared;
    }

    /**
     * Resolves the service
     *
     * @param array $parameters
     * @return mixed
     */
    public function resolve($parameters = null)
    {
        if ($this->shared) {
            $sharedInstance = $this->sharedInstance;
            if ($sharedInstance !== null) {
                return $sharedInstance;
            }
        }
        
        $instance = null;
        if (is_string($this->definition)) {
            if (class_exists($this->definition)) {
                $class = new \ReflectionClass($this->definition);
                if (is_array($parameters)) {
                    $instance = $class->newInstanceArgs($parameters);
                } else {
                    if ($class->getConstructor() !== null) {
                        $instance = $class->newInstance($parameters);
                    } else {
                        $instance = new $this->definition;
                    }
                }
            }
        } else {
            throw new \Exception("Service '" . $this->name . "' cannot be resolved");
        }
        
        if ($instance === null) {
            throw new \Exception("Service '" . $this->name . "' cannot be resolved");
        }
        
        if ($this->shared) {
            $this->sharedInstance = $instance;
        }
        
        return $instance;
    }
}