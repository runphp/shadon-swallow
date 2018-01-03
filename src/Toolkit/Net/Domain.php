<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Toolkit\Net;

use Swallow\Core\Conf;
/**
 * Domain 域名相关处理类
 * 
 * @author     fenghaikun<fenghaikun@eelly.net>
 * @since      2016年9月6日
 * @version    1.0
 */
class Domain
{
    /**
     * 返回商城的图片地址
     * 如果配置了多图片域名
     * static 轮询返回
     * crc32  根据检验码返回
     *
     * @param string $name
     * @return string
     * 
     */
    public static function getImageHost($name='apiUrl',$type='static')
    {
        //crc32 检验的方式，直接返回固定的拼装好的URL
        if($type=='crc32'){
            $hit = substr(crc32($name),-1) % 5;
            $imgServices = Conf::get('Swallow/inc/IMAGE_HOST_LIST');
            $imgHeard = isset($imgServices[$hit]) ? $imgServices[$hit] : 'img';
            $imgServiceArr = explode('.', IMAGE_URL);
            $imgService = $imgServiceArr[count($imgServiceArr) - 2] . '.' . $imgServiceArr[count($imgServiceArr) - 1];
            return 'https://'.$imgHeard.'.'.$imgService.'/'.$name;
        }else{
            return getImageHost($name);
        }
    }
   
}
