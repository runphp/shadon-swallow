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
 * 图片处理扩展类
 *
 * @author    SpiritTeam
 * @since     2015年5月8日
 * @version   1.0
 */
class Image
{

    /**
     * 添加图片水印
     * 
     * @param $groundImage   背景图片，即需要加水印的图片，支持GIF,JPG,PNG格式；
     * @param $waterPos      水印位置，有10种状态，0为随机位置；
     *                1为顶端居左，2为顶端居中，3为顶端居右；
     *                4为中部居左，5为中部居中，6为中部居右；
     *                7为底端居左，8为底端居中，9为底端居右；
     * @param $waterImage    图片水印，即作为水印的图片，暂只支持GIF,JPG,PNG格式；
     * @param $waterText     文字水印，即把文字作为为水印，支持ASCII码，不支持中文；
     * @param $textFont      文字大小，值为1、2、3、4或5，默认为5；
     * @param $textColor     文字颜色，值为十六进制颜色值，默认为#FF0000(红色)；
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年5月27日
     */
    public static function imageWaterMark(
        $groundImage, 
        $waterPos = 0, 
        $waterImage = "", 
        $waterText = "", 
        $textFont = 5, 
        $textColor = "#FF0000", 
        $quality = 85)
    {
        $isWaterImage = FALSE;
        $formatMsg = "暂不支持该文件格式，请用图片处理软件将图片转换为GIF、JPG、PNG格式。";
        $path = LOG_PATH . "imagelogs/image_error_" . date('Ymd', time()) . ".txt"; //日志文件
        $text_lines = explode("\n", $waterText);
        $count_tmp = 0;
        foreach ($text_lines as $vtl) {
            ! empty($vtl) && $count_tmp ++;
        }
        $count_tmp == 1 && $waterText = implode('', $text_lines);
        //读取水印文件
        if (! empty($waterImage) && file_exists($waterImage)) {
            $isWaterImage = TRUE;
            $water_info = @getimagesize($waterImage);
            $water_w = $water_info[0]; //取得水印图片的宽
            $water_h = $water_info[1]; //取得水印图片的高
            switch ($water_info[2]) { //取得水印图片的格式
                case 1:
                    $water_im = imagecreatefromgif($waterImage);
                    break;
                case 2:
                    $water_im = imagecreatefromjpeg($waterImage);
                    break;
                case 3:
                    $water_im = imagecreatefrompng($waterImage);
                    break;
                default:
                    error_log(date('Y-m-d H:i:s') . "\t" . $waterImage . " \n", 3, $path); //添加错误日志
                    die($formatMsg);
            }
            //释放内存
            if (isset($water_info))
                unset($water_info);
        }
        //读取背景图片
        if (! empty($groundImage) && file_exists($groundImage)) {
            $ground_info = @getimagesize($groundImage);
            $ground_w = $ground_info[0]; //取得背景图片的宽
            $ground_h = $ground_info[1]; //取得背景图片的高
            switch ($ground_info[2]) { //取得背景图片的格式
                case 1:
                    $ground_im = imagecreatefromgif($groundImage);
                    break;
                case 2:
                    $ground_im = imagecreatefromjpeg($groundImage);
                    break;
                case 3:
                    $ground_im = imagecreatefrompng($groundImage);
                    break;
                default:
                    error_log(date('Y-m-d H:i:s') . "\t" . $groundImage . " \n", 3, $path); //添加错误日志
                    die($formatMsg);
            }
        } else {
            error_log(date('Y-m-d H:i:s') . "\t" . $groundImage . "|" . $waterImage . " \n", 3, $path); //添加错误日志
            die("需要加水印的图片不存在！");
        }
        //水印位置
        if ($isWaterImage) { //图片水印
            $w = $water_w;
            $h = $water_h;
        } else { //文字水印
            $temp = @imagettfbbox($textFont, 0, "./MSYH.TTF", $waterText);
            //取得使用 TrueType 字体的文本的范围
            $w = $temp[2] - $temp[6];
            $h = $temp[3] - $temp[7];
            unset($temp);
        }
        if (($ground_w < $w) || ($ground_h < $h)) {
            // echo "需要加水印的图片的长度或宽度比水印".$label."还小，无法生成水印！";
            return;
        }
        switch ($waterPos) {
            case 0: //随机
                $posX = rand(0, ($ground_w - $w));
                $posY = rand(0, ($ground_h - $h));
                break;
            case 1: //1为顶端居左
                $posX = 20;
                $posY = $textFont + 10;
                break;
            case 2: //2为顶端居中
                $posX = ($ground_w - $w) / 2;
                $posY = 10;
                break;
            case 3: //3为顶端居右
                $posX = $ground_w - $w;
                $posY = $textFont + 10;
                break;
            case 4: //4为中部居左
                $posX = 10;
                $posY = ($ground_h - $h) / 2;
                break;
            case 5: //5为中部居中
                if ($textFont > 20) {
                    $posX = ($ground_w - $w) / 2 + 10;
                    $posY = ($ground_h - $h) / 2 + 20;
                } else {
                    $posX = ($ground_w - $w) / 2;
                    $posY = ($ground_h - $h) / 2;
                }
                break;
            case 6: //6为中部居右
                $posX = $ground_w - $w;
                $posY = ($ground_h - $h) / 2;
                break;
            case 7: //7为底端居左
                $posX = 10;
                //            $posY = $ground_h - $h + 20;//- 40;
                if ($textFont > 20) {
                    $posY = $ground_h - $h + 20;
                } else {
                    $posY = $ground_h - $h + 10;
                }
                break;
            case 8: //8为底端居中
                $posX = ($ground_w - $w) / 2;
                $posY = $ground_h - $h - 10;
                break;
            case 9: //9为底端居右
                $posX = $ground_w - $w - 10;
                if ($textFont > 20) {
                    $posY = $ground_h - $h + 20;
                } else {
                    $posY = $ground_h - $h + 10;
                }
                break;
            default: //随机
                $posX = rand(0, ($ground_w - $w));
                $posY = rand(0, ($ground_h - $h));
                break;
        }
        //设定图像的混色模式
        imagealphablending($ground_im, true);
        if ($isWaterImage) { //图片水印
            imagecopy($ground_im, $water_im, $posX, $posY, 0, 0, $water_w, $water_h); //拷贝水印到目标文件
        } else { //文字水印
            if (! empty($textColor) && (strlen($textColor) == 7)) {
                $R = hexdec(substr($textColor, 1, 2));
                $G = hexdec(substr($textColor, 3, 2));
                $B = hexdec(substr($textColor, 5));
                $txtcolor = imagecolorallocate($ground_im, $R, $G, $B);
            } elseif (! empty($textColor) && (strlen($textColor) == 9)) {
                //带透明值的 @author   linzhigang   2014-5-15
                $R = hexdec(substr($textColor, 1, 2));
                $G = hexdec(substr($textColor, 3, 2));
                $B = hexdec(substr($textColor, 5, 2));
                $A = floor(hexdec(substr($textColor, 7)) / 2);
                $txtcolor = imagecolorallocatealpha($ground_im, $R, $G, $B, $A);
            } else {
                die("水印文字颜色格式不正确！");
            }
            imagettftext($ground_im, $textFont, 0, $posX, $posY, $txtcolor, "./MSYH.TTF", $waterText);
        }
        if (isset($water_im))
            imagedestroy($water_im);
            //生成水印后的图片
        @unlink($groundImage);
        switch ($ground_info[2]) { //取得背景图片的格式
            case 1:
                imagegif($ground_im, $groundImage, $quality);
                break;
            case 2:
                imagejpeg($ground_im, $groundImage, $quality);
                break;
            case 3:
                imagepng($ground_im, $groundImage, floor($quality / 10));
                break;
            default:
                die("不支持的背景图片格式！");
        }
        imagedestroy($ground_im);
        unset($ground_info);
    }
    
}