<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
use Swallow\Core\Cache;

/**
 *
 * vendor/bin/phpunit --bootstrap unittest.php vendor/eelly/framework/tests/Swallow/Cache/MemcachedTest.php
 *
 */
class MemcachedTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     * @var int
     */
    const MAXPROCESS = 25;

    /**
     *
     * @var Cache
     */
    private $cache;

    /**
     *
     * @var int
     */
    private $key;

    protected function setUp()
    {
        $this->cache = Cache::getInstance();
        $this->key = 'test_test';
        dump($this->cache->get($this->key));
    }

    /**
     * @test
     */
    public function casSet()
    {
        $execute = 0;
        $index = 1000;
        $pids = [];
        for ($i = 0; $i < $index; $i ++) {
            $pids[$i] = pcntl_fork();

            if ($pids[$i] == - 1) {
                die('could not fork');
            } else if ($pids[$i]) {
                $execute ++;
                if ($execute >= self::MAXPROCESS) {
                    pcntl_wait($status);
                    $execute --;
                }
            } else {
                $this->setCacheFunction();
                exit;
            }
        }
        foreach ($pids as $i => $pid) {
            if($pid) {
                pcntl_waitpid($pid, $status);
            }
        }
    }

    public function setCacheFunction()
    {
        $value = posix_getpid();
        $ret = $this->cache->casSet($this->key, $value, 600);
        //$ret = $this->cache->set($this->key, $value);
        $this->assertTrue($ret);
    }
}