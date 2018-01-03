<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Toolkit\Util;

/**
 * 文件或文件夹操作扩展类
 *
 * @author    SpiritTeam
 * @since     2015年5月8日
 * @version   1.0
 */
class File
{
    /**
     * 获取文件夹树结构
     *
     * @param string $dir 开始的文件夹路径 （绝对路径）
     * @param boolean $ergodic 是否遍历
     * @return array
     */
    public static function dirTree($dir, $ergodic = false)
    {
        $dirList = $tmp = array();
        if (is_dir($dir)) {
            $cdir = scandir($dir);
            foreach ($cdir as $value) {
                if (in_array($value, array(".", "..", ".svn"))) {
                    continue;
                }
                $dirChid = $dir . DIRECTORY_SEPARATOR . $value;
                if($ergodic){
                    if (is_dir($dirChid)) {
                        $tmp['name'] = $value;
                        $tmp['children'] = self::dirTree($dirChid);
                        $dirList[] = $tmp;
                    } else {
                        $dirList[] = $value;
                    }
                }else {
                    $dirList[] = $value;
                }
            }
        }
        return $dirList;
    }
    
    
}
