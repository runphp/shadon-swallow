<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2016 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
namespace Swallow\Traits;

/**
 * mong config
 *
 * >
 * 保留的key
 * _id, setting, description, created, modified
 *
 * @author    hehui<hehui@eelly.net>
 * @since     2016年10月7日
 * @version   1.0
 */
trait MongoConfig
{

    /**
     * 设置数据
     *
     *
     * @param string $key
     * @param mix $value
     * @param int $expired 过期时间戳
     * @author hehui<hehui@eelly.net>
     * @since  2016年10月7日
     */
    public static function set($key, $value, $expired = -1)
    {
        $mongoId = self::mongoId();
        $clazz= self::MONGO_COLLECTION;
        $setting = $clazz::findById($mongoId);
        if (false === $setting) {
            $setting = new $clazz();
            $setting->setId($mongoId);
            $setting->setting = __CLASS__;
            $setting->description = self::DESCRIPTION;
            $setting->created = milliseconds();
        }
        $body = ['value' => $value];
        if ($expired > time()) {
            $body['expired'] = (int)$expired;
        } elseif ($expired > 0) {
            $body['expired'] = (int)(time() + $expired);
        }
        $setting->$key = $body;
        $setting->modified = milliseconds();
        return $setting->save();
    }

    /**
     * 获取数据
     *
     *
     * @param string $key
     * @author hehui<hehui@eelly.net>
     * @since 2016年10月7日
     */
    public static function get($key)
    {
        $mongoId = self::mongoId();
        $clazz= self::MONGO_COLLECTION;
        $setting = $clazz::findById($mongoId);
        if (false === $setting || !isset($setting->$key)) {
            return null;
        }
        $body = $setting->$key;
        if (isset($body['expired']) && $body['expired'] <= time()) {
            return null;
        }
        return $body['value'];
    }

    private static function mongoId()
    {
        return substr(md5(__CLASS__), 0, 24);
    }
}
