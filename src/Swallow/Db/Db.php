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
 * Db接口
 *
 * @author    SpiritTeam
 * @since     2015年3月10日
 * @version   1.0
 */
interface Db
{

    /**
     * 更改行数
     * 
     * @return int
     */
    public function affectedRows();

    /**
     * 插入的id
     * 
     * @return int
     */
    public function insertId();

    /**
     * 错误字符串
     *
     * @return int
     */
    public function getError();

    /**
     * 执行查询 返回数据集
     *
     * @param  string $sql
     * @return mixed
     */
    public function query($sql);

    /**
     * 执行语句
     * @param  string $sql
     * @return int
     */
    public function execute($sql);

    /**
     * 启动事务
     *
     * @return boolean
     */
    public function beginTransaction();

    /**
     * 用于非自动提交状态下面的查询提交
     *
     * @return boolean
     */
    public function commit();

    /**
     * 事务回滚
     *
     * @return boolean
     */
    public function rollback();

    /**
     * 结速事务
     *
     * @return boolean
     */
    public function endTransaction();
}