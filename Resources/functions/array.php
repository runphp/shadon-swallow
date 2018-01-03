<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2016 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
if (! function_exists('array_orderby')) {

    /**
     *
     *
     * @link http://php.net/manual/zh/function.array-multisort.php#100534
     * @return mixed
     * @author hehui<hehui@eelly.net>
     * @since  2016年10月21日
     */
    function array_orderby()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }
}

if (! function_exists('array_key_exists_r')) {

    /**
     * Check array for multiple keys
     *
     *
     * @param string $keys
     * @param array $searchArr
     * @return boolean
     * @link http://php.net/manual/en/function.array-key-exists.php#77848
     * @author hehui<hehui@eelly.net>
     * @since  2016年11月23日
     */
    function array_key_exists_r($keys, $searchArr)
    {
        $keysArr = explode('|', $keys);
        foreach ($keysArr as $key) {
            if (! array_key_exists($key, $searchArr)) {
                return false;
            }
        }
        return true;
    }
}