#!/usr/bin/env php
<?php

$fileHeaderComment = <<<COMMENT
This file is part of eelly package.

(c) eelly.com

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
COMMENT;

define('ROOT_PATH', __DIR__);
chdir(ROOT_PATH);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__);

$rules = [
    '@Symfony'                                   => true,
    '@Symfony:risky'                             => true,
    '@PHP71Migration'                            => true,
    '@PHP71Migration:risky'                      => true,
    'ordered_imports'                            => true,
    'array_syntax'                               => ['syntax' => 'short'],
    'header_comment'                             => ['header' => $fileHeaderComment],
    'ordered_class_elements'                     => true,
    'no_multiline_whitespace_before_semicolons'  => true,
    'binary_operator_spaces'                     => ['align_double_arrow' => true],
    'declare_equal_normalize'                    => ['space' => 'none'],
    'phpdoc_order'                               => true,
    'phpdoc_no_alias_tag'                        => [],
    'phpdoc_var_without_name'                    => false,
    'increment_style'                            => ['style' => 'post'],
];

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder($finder)
    ->setCacheFile(sys_get_temp_dir().'/'.md5(__FILE__).'.php_cs.cache');
