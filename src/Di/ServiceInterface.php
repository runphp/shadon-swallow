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
 * Service接口
 *
 * @author    SpiritTeam
 * @since     2015年3月10日
 * @version   1.0
 */
interface ServiceInterface
{
    /**
     * Swallow\Di\Service
     *
     * @param string $name
     * @param mixed $definition
     * @param boolean $shared
     */
    public function __construct($name, $definition, $shared = false);
    
    /**
     * Resolves the service
     *
     * @param array $parameters
     * @return mixed
     */
    public function resolve($parameters = null);
    
    
}