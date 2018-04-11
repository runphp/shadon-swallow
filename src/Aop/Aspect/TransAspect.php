<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Aop\Aspect;

use Swallow\Aop\Joinpoint;
use Swallow\Exception\LogicException;
use Swallow\Exception\DbException;
use Swallow\Exception\TransException;
use Swallow\Core\Db;

/**
 * 拦截器 数据库事务
 * 
 * @author     SpiritTeam
 * @since      2015年3月6日
 * @version    1.0
 */
class TransAspect
{

    /**
     * 标签名
     * @var string
     */
    const TAG = 'trans';

    /**
     * 接入类型 
     * @var string
     */
    const TYPE = 'around';

    /**
     * 处理事务
     * 
     * @param  Joinpoint $joinpoit
     */
    public static function run(Joinpoint $joinpoit)
    {
        $db = Db::getInstance();
        $db->beginTransaction();
        try {
            $joinpoit->process();
            $retval = $joinpoit->getReturnValue();
        } catch (TransException $e) {
            $db->rollback();
            $db->endTransaction();
            throw new LogicException($e->getMessage(), $e->getCode(), $e->getArgs(), $e);
        } catch (LogicException $e) {
            $db->rollback();
            $db->endTransaction();
            throw $e;
        } catch (DbException $e) {
            $db->rollback();
            $db->endTransaction();
            throw $e;
        }
        $db->commit();
        $db->endTransaction();
        $joinpoit->setReturnValue($retval);
    }
}
