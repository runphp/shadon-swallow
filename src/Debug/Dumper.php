<?php
/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Debug;

/**
 * Dumper.
 *
 * @author    SpiritTeam
 * @since     2015年7月27日
 * @version   1.0
 */
class Dumper
{

    /**
     * 格式化显示出变量.
     *
     * @param  mixed  $value
     * @return void
     */
    public static function dump($value)
    {
        if ('cli' === PHP_SAPI) {
            $dumper = new \Symfony\Component\VarDumper\Dumper\CliDumper();
        } else {
            $dumper = new \Symfony\Component\VarDumper\Dumper\HtmlDumper();
            $dumper->setStyles(
                [
                    'default' => 'background-color:#fff; color:#222; line-height:1.2em; font-weight:normal; font:12px Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:100000',
                    'num' => 'color:#a71d5d',
                    'const' => 'color:#795da3',
                    'str' => 'color:#df5000',
                    'cchr' => 'color:#222',
                    'note' => 'color:#a71d5d',
                    'ref' => 'color:#a0a0a0',
                    'public' => 'color:#795da3',
                    'protected' => 'color:#795da3',
                    'private' => 'color:#795da3',
                    'meta' => 'color:#b729d9',
                    'key' => 'color:#df5000',
                    'index' => 'color:#a71d5d']);
        }

        $dumper->dump((new \Symfony\Component\VarDumper\Cloner\VarCloner)->cloneVar($value));
    }
}
