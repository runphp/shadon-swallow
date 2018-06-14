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

namespace Swallow\Events\Event;

use Phalcon\Events\Event;
use Swallow\Annotations\AnnotationProxy;
use Swallow\Core\Db;

/**
 * 事务监听器.
 *
 * @author hehui<hehui@eelly.net>
 */
class TransactionListener extends AbstractListener
{
    public const ANNOTATION_NAME = 'Transaction';

    public const ANNOTATION_NAME_ALIAS = 'trans';

    public function beforeMethod(Event $event, AnnotationProxy $object, array $data)
    {
        list($class, $method, $params) = $data;
        $collection = $this->getAnnotationCollection($class, $method);
        if (false === $collection) {
            return true;
        }
        if ($collection->has(self::ANNOTATION_NAME) || $collection->has(self::ANNOTATION_NAME_ALIAS)) {
            $db = Db::getInstance();
            $db->beginTransaction();
            try {
                $object->_setMethodReturnValue(call_user_func_array([$object->_getProxyObject(), $method], $params));
            } catch (\Exception $e) {
                $db->rollback();
                $db->endTransaction();
                throw $e;
            }
            $db->commit();
            $db->endTransaction();

            return false;
        }
    }
}
