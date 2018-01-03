<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2016 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */
if (! function_exists('formatterGoodsImgSize')) {

    /**
     * 格式化商品图片尺寸大小
     *
     * @param type $imgUrl
     * @param type $size
     * @author :allenbin
     * @author hehui<hehui@eelly.net>
     */
    function formatterGoodsImgSize(&$imgUrl, $size)
    {
        $tempUrl = $imgUrl;

        if (! empty($imgUrl)) {
            if (! strpos($imgUrl, 'small_')) {
                $urlArr = explode('/', $imgUrl);
                $urlSizeVal = ! empty($size) ? 'small_' . $size . "_" . array_pop($urlArr) : 'small_' . array_pop($urlArr);
                $tempUrl = implode("/", $urlArr) . '/' . $urlSizeVal;
            } else {
                $tempUrl = str_replace('small_', 'small_' . $size . '_', $imgUrl);
            }
        }
        $imgUrl = $tempUrl;
        return $tempUrl;
    }
}

if (! function_exists('formatterGoodsImgArrSize')) {

    /**
     * 格式化商品图片尺寸大小
     *
     * @param array $imgArr
     *            包含图片的数组
     * @param int $size
     *            尺寸（50、118、148、220、235）
     * @param string $field
     *            图片字段名
     * @author hehui<hehui@eelly.net>
     */
    function formatterGoodsImgArrSize(&$imgArr, $size, $field)
    {
        if (! is_array($imgArr)) {
            return;
        }
        foreach ($imgArr as &$item) {
            formatterGoodsImgSize($item[$field], $size);
        }
    }
}

if (! function_exists('mineTypeExtension')) {

    /**
     * 通过mine type 获取文件后缀
     *
     *
     * @param String $mineType
     * @author hehui<hehui@eelly.net>
     * @since 2016年10月5日
     */
    function mineTypeExtension($mineType)
    {
        $types = [
            'application/andrew-inset' => 'ez',
            'application/atom+xml' => 'atom',
            'application/json' => 'json',
            'application/mac-binhex40' => 'hqx',
            'application/mac-compactpro' => 'cpt',
            'application/mathml+xml' => 'mathml',
            'application/msword' => 'doc',
            'application/octet-stream' => 'so',
            'application/oda' => 'oda',
            'application/ogg' => 'ogg',
            'application/pdf' => 'pdf',
            'application/postscript' => 'ps',
            'application/rdf+xml' => 'rdf',
            'application/rss+xml' => 'rss',
            'application/smil' => 'smil',
            'application/srgs' => 'gram',
            'application/srgs+xml' => 'grxml',
            'application/vnd.mif' => 'mif',
            'application/vnd.mozilla.xul+xml' => 'xul',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.rn-realmedia' => 'rm',
            'application/vnd.wap.wbxml' => 'wbxml',
            'application/vnd.wap.wmlc' => 'wmlc',
            'application/vnd.wap.wmlscriptc' => 'wmlsc',
            'application/voicexml+xml' => 'vxml',
            'application/x-bcpio' => 'bcpio',
            'application/x-cdlink' => 'vcd',
            'application/x-chess-pgn' => 'pgn',
            'application/x-cpio' => 'cpio',
            'application/x-csh' => 'csh',
            'application/x-director' => 'dxr',
            'application/x-dvi' => 'dvi',
            'application/x-futuresplash' => 'spl',
            'application/x-gtar' => 'gtar',
            'application/x-hdf' => 'hdf',
            'application/x-javascript' => 'js',
            'application/x-koan' => 'skt',
            'application/x-latex' => 'latex',
            'application/x-netcdf' => 'nc',
            'application/x-sh' => 'sh',
            'application/x-shar' => 'shar',
            'application/x-shockwave-flash' => 'swf',
            'application/x-stuffit' => 'sit',
            'application/x-sv4cpio' => 'sv4cpio',
            'application/x-sv4crc' => 'sv4crc',
            'application/x-tar' => 'tar',
            'application/x-tcl' => 'tcl',
            'application/x-tex' => 'tex',
            'application/x-texinfo' => 'texinfo',
            'application/x-troff' => 'tr',
            'application/x-troff-man' => 'man',
            'application/x-troff-me' => 'me',
            'application/x-troff-ms' => 'ms',
            'application/x-ustar' => 'ustar',
            'application/x-wais-source' => 'src',
            'application/xhtml+xml' => 'xhtml',
            'application/xml' => 'xsl',
            'application/xml-dtd' => 'dtd',
            'application/xslt+xml' => 'xslt',
            'application/zip' => 'zip',
            'audio/basic' => 'snd',
            'audio/midi' => 'midi',
            'audio/mpeg' => 'mpga',
            'audio/x-aiff' => 'aiff',
            'audio/x-mpegurl' => 'm3u',
            'audio/x-pn-realaudio' => 'ram',
            'audio/x-wav' => 'wav',
            'chemical/x-pdb' => 'pdb',
            'chemical/x-xyz' => 'xyz',
            'image/bmp' => 'bmp',
            'image/cgm' => 'cgm',
            'image/gif' => 'gif',
            'image/ief' => 'ief',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/svg+xml' => 'svgz',
            'image/tiff' => 'tiff',
            'image/vnd.djvu' => 'djvu',
            'image/vnd.wap.wbmp' => 'wbmp',
            'image/x-cmu-raster' => 'ras',
            'image/x-icon' => 'ico',
            'image/x-portable-anymap' => 'pnm',
            'image/x-portable-bitmap' => 'pbm',
            'image/x-portable-graymap' => 'pgm',
            'image/x-portable-pixmap' => 'ppm',
            'image/x-rgb' => 'rgb',
            'image/x-xbitmap' => 'xbm',
            'image/x-xpixmap' => 'xpm',
            'image/x-xwindowdump' => 'xwd',
            'model/iges' => 'igs',
            'model/mesh' => 'silo',
            'model/vrml' => 'wrl',
            'text/calendar' => 'ifb',
            'text/css' => 'css',
            'text/csv' => 'csv',
            'text/html' => 'html',
            'text/plain' => 'txt',
            'text/richtext' => 'rtx',
            'text/rtf' => 'rtf',
            'text/sgml' => 'sgml',
            'text/tab-separated-values' => 'tsv',
            'text/vnd.wap.wml' => 'wml',
            'text/vnd.wap.wmlscript' => 'wmls',
            'text/x-setext' => 'etx',
            'video/mp4' => 'mp4',
            'video/mpeg' => 'mpg',
            'video/quicktime' => 'qt',
            'video/vnd.mpegurl' => 'mxu',
            'video/x-msvideo' => 'avi',
            'video/x-sgi-movie' => 'movie',
            'x-conference/x-cooltalk' => 'ice'
        ];
        if (isset($types[$mineType])) {
            return $types[$mineType];
        } else {
            return '';
        }
    }
}