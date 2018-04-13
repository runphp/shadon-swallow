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

if (!function_exists('dd')) {
    /**
     * 格式化显示出变量并结束.
     *
     * @param  mixed
     */
    function dd($var): void
    {
        $var = func_get_args();
        array_map('dump', $var);
        die();
    }
}

if (!function_exists('url')) {
    /**
     * ecmall动态url.
     *
     * @param  mixed
     */
    function url($query = null)
    {
        if (null === $query) {
            return  '/';
        }
        if (is_string($query)) {
            return $query;
        }
        if (is_array($query)) {
            return '/index.php?'.http_build_query($query);
        }
        throw new ErrorException('arguments error');
    }
}

/*
 * 截取UTF-8编码下字符串的函数
 *
 * @param   string      $str        被截取的字符串
 * @param   int         $length     截取的长度
 * @param   bool        $append     是否附加省略号
 *
 * @return  string
 */
if (!function_exists('sub_str')) {
    function sub_str($string, $length = 0, $append = true)
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        $string = str_replace(['&amp;', '&quot;', '&lt;', '&gt;'], ['&', '"', '<', '>'], $string);
        $strcut = '';
        $n = $tn = $noc = 0;
        while ($n < strlen($string)) {
            $t = ord($string[$n]);
            if (9 == $t || 10 == $t || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n++;
                $noc++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t < 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif (252 == $t || 253 == $t) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n++;
            }
            if ($noc >= $length) {
                break;
            }
        }
        if ($noc > $length) {
            $n -= $tn;
        }
        $strcut = substr($string, 0, $n);
        $strcut = str_replace(['&', '"', '<', '>'], ['&amp;', '&quot;', '&lt;', '&gt;'], $strcut);
        if ($append && $string != $strcut) {
            $strcut .= '...';
        }

        return $strcut;
    }
}
