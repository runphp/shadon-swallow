<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Validation;

use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Between;
use Phalcon\Validation\Validator\Numericality;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Uniqueness;
use Phalcon\Validation\Validator\Url;
use Phalcon\Validation\Validator\Regex;
use Phalcon\Validation\Validator\InclusionIn;
use Phalcon\Validation\Validator\Confirmation;

/**
 *
 * @author     SpiritTeam
 * @since      2015年8月13日
 * @version    1.0
 */
class Validation extends \Phalcon\Validation
{

    /**
     * 验证入口
     * 
     * @param array $check
     * @param array $data
     * @author 范世军<fanshijun@eelly.net>
     * @since  2015年9月10日
     */
    public function verify(array $check, array $data)
    {
        if (empty($check) || empty($data)) {
            return true;
        }
        foreach ($check as $val) {
            $name = isset($val[0]) ? $val[0] : '';
            $validator = isset($val[1]) ? strtolower($val[1]) : '';
            $message = isset($val[2]) ? $val[2] : '';
            $rule = isset($val[3]) ? $val[3] : '';
            if (empty($name) || empty($validator)) {
                throw new \ErrorException('Verification rule fill in error!');
            }
            switch ($validator) {
                case "require":
                    $objValidator = new PresenceOf(['message' => $message]);
                    break;
                case "email":
                    $objValidator = new Email(['message' => $message]);
                    break;
                case "numeric":
                    $objValidator = new Numericality(['message' => $message]);
                    break;
                case "unique":
                    $objValidator = new Uniqueness(['model' => $rule, 'message' => $message]);
                    break;
                case "url":
                    $objValidator = new Url(['message' => $message]);
                    break;
                case "regex":
                    if (empty($rule)) {
                        throw new \ErrorException('Regex rule must be set!');
                    }
                    $objValidator = new Regex(['pattern' => $rule, 'message' => $message]);
                    break;
                case "in":
                    if (empty($rule) || ! is_array($rule)) {
                        throw new \ErrorException('In rule is error!');
                    }
                    $objValidator = new InclusionIn(['domain' => $rule, 'message' => $message]);
                    break;
                case "confirmation":
                    if (empty($rule)) {
                        throw new \ErrorException('confirmation rule is error!');
                    }
                    $objValidator = new Confirmation(['with' => $rule, 'message' => $message]);
                    break;
                case "between":
                    if (empty($rule[0]) || empty($rule[1])) {
                        throw new \ErrorException('Between rule is error!');
                    }
                    $objValidator = new Between(['minimum' => intval($rule[0]), 'maximum' => intval($rule[1]), 'message' => $message]);
                    break;
                case "length":
                    if (empty($rule[0])) {
                        throw new \ErrorException('length rule is error!');
                    }
                    $ruleLength = ['min' => intval($rule[0])];
                    ! empty($rule[1]) && $ruleLength['max'] = $rule[1];
                    if (! empty($message) && is_array($message)) {
                        $ruleLength['messageMinimum'] = $message[0];
                        $ruleLength['messageMaximum'] = isset($message[1]) ? $message[1] : '';
                    } else {
                        $ruleLength['messageMinimum'] = $ruleLength['messageMaximum'] = $message;
                    }
                    $objValidator = new StringLength($ruleLength);
                    break;
            }
            $this->add($name, $objValidator);
        }
        $messages = $this->validate($data);
        if (count($messages)) {
            return $messages;
        }
        return true;
    }
}
