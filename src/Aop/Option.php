<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Aop;

/**
 * Aop 选项
 * 
 * @author     SpiritTeam
 * @since      2015年1月13日
 * @version    1.0
 */
interface Option
{

    /**
     * 跳过保护方法
     * @var int
     */
    const SKIP_PROTECTED_METHOD = 1;

    /**
     * 跳过私有方法
     * @var int
     */
    const SKIP_PRIVATE_METHOD = 2;

    /**
     * 跳过父类方法
     * @var int
     */
    const SKIP_PARENT_METHOD = 4;
}
