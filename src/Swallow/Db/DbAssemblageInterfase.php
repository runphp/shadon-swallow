<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */

namespace Swallow\Db;

/**
 * Mysql池。
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
interface DbAssemblageInterfase
{
    /**
     * 返回读数据库连接
     *
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function selectReadConnection($intermediate, $bindParams, $bindTypes);

    /**
     * 返回写数据库连接
     *
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function selectWriteConnection($intermediate, $bindParams, $bindTypes);

    /**
     * 强制使用主库
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function setMasterForced();

    /**
     * 清除所有数据库连接集合。
     * 主要应该场景是自动任务里面的死循环队列处理，防止主从延迟的情况下
     * 一直都连着主库。
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     *
     */
    public function clearConnections();

    /**
     * 获取主库对象
     *
     * @return \Swallow\Db\Mysql
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function getMaster();

    /**
     * 获取从库对象数组
     *
     * @param bool $isGetOne 是否只取一个
     * @return mix
     *  object: \Swallow\Db\Mysql
     *  array: 每个元素都是 \Swallow\Db\Mysql
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    public function getSlave($isGetOne = false);

    /**
     * 初始化数据库连接
     *
     * @param object $config
     *
     * @author    SpiritTeam
     * @since     2015年8月12日
     * @version   1.0
     */
    protected function initConnection($config);
}