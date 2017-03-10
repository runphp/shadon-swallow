<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Debug\Syntax;

/**
 * 分词
 * 
 * @author    liaochu<liaochu@eelly.net>
 * @since     2016-7-18
 * @version   1.0
 */
class Tokenizer
{   
    private static $_resolveTokenCache = array();
    
    /**
     * 获得PHP tokens，token_get_all
     * 
     * 
     * @param string $content
     * @return array
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public static function init($content)
    {
        $rst = array();
        
        $tokens = @token_get_all($content);
        
        foreach ($tokens as $token) {
            //是否数组
            $isArray = isset($token[1]);            
            if ($isArray) {
                $data['type'] = token_name($token[0]);
                $data['content'] = $token[1];
                $data['line'] = $line = $token[2];
            } else {
                $simpleToken = self::resolveSimpleToken($token);
                $data = $simpleToken;
                $data['line'] = !empty($line) ? $line : 0; 
            }
            $rst[] = $data;
        }
        return $rst;
    }    
    
    /**
     * 将字符串token转换为数组
     * 
     * 
     * @param string $token
     * @return array
     * @author liaochu<liaochu@eelly.net>
     * @since  2016-7-18
     */
    public static function resolveSimpleToken($token)
    {
        $newToken = array();
        
        switch ($token) {
            case '{':
                $newToken['type'] = 'T_OPEN_CURLY_BRACKET';
                break;
            case '}':
                $newToken['type'] = 'T_CLOSE_CURLY_BRACKET';
                break;
            case '[':
                $newToken['type'] = 'T_OPEN_SQUARE_BRACKET';
                break;
            case ']':
                $newToken['type'] = 'T_CLOSE_SQUARE_BRACKET';
                break;
            case '(':
                $newToken['type'] = 'T_OPEN_PARENTHESIS';
                break;
            case ')':
                $newToken['type'] = 'T_CLOSE_PARENTHESIS';
                break;
            case ':':
                $newToken['type'] = 'T_COLON';
                break;
            case '.':
                $newToken['type'] = 'T_STRING_CONCAT';
                break;
            case '?':
                $newToken['type'] = 'T_INLINE_THEN';
                break;
            case ';':
                $newToken['type'] = 'T_SEMICOLON';
                break;
            case '=':
                $newToken['type'] = 'T_EQUAL';
                break;
            case '*':
                $newToken['type'] = 'T_MULTIPLY';
                break;
            case '/':
                $newToken['type'] = 'T_DIVIDE';
                break;
            case '+':
                $newToken['type'] = 'T_PLUS';
                break;
            case '-':
                $newToken['type'] = 'T_MINUS';
                break;
            case '%':
                $newToken['type'] = 'T_MODULUS';
                break;
            case '^':
                $newToken['type'] = 'T_BITWISE_XOR';
                break;
            case '&':
                $newToken['type'] = 'T_BITWISE_AND';
                break;
            case '|':
                $newToken['type'] = 'T_BITWISE_OR';
                break;
            case '<':
                $newToken['type'] = 'T_LESS_THAN';
                break;
            case '>':
                $newToken['type'] = 'T_GREATER_THAN';
                break;
            case '!':
                $newToken['type'] = 'T_BOOLEAN_NOT';
                break;
            case ',':
                $newToken['type'] = 'T_COMMA';
                break;
            case '@':
                $newToken['type'] = 'T_ASPERAND';
                break;
            case '$':
                $newToken['type'] = 'T_DOLLAR';
                break;
            case '`':
                $newToken['type'] = 'T_BACKTICK';
                break;
            default:
                $newToken['type'] = 'T_NONE';
                break;
        }
        
        $newToken['content'] = $token;
        
        self::$_resolveTokenCache[$token] = $newToken;
        
        return $newToken;
    }
}