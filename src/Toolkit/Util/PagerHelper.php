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
 * 分页工具类
 *
 * @author: guoanqi
 * @time: 2014年12月24日 星期三 14时44分42秒
 * move by zhangzeqiang 2016/05/18
 */
class PagerHelper
{
    protected $limit = 10;  //默认每页显示条数
    protected $page = 1;
    protected $sort = "";
    protected $order = "DESC";
    protected $min = 0;
    protected $max = 0;
    protected $alias = "";
    public function __construct($pagerParams = array())
    {
        $this->setPagerParams($pagerParams);            
        if($this->alias && $this->sort)
        {
            $this->sort = "$this->alias.{$this->sort}";
        }
    }

    /**
     * 设置分页参数
     * @author guoanqi
     * @param [type] $pagerParams [description]
     */
    public function setPagerParams($pagerParams)
    {
        if(!empty($pagerParams))
        {
            $this->limit = !empty($pagerParams['limit']) ? $pagerParams['limit'] : $this->limit;
            $this->page = !empty($pagerParams['page']) ? $pagerParams['page'] : 1;
            $this->sort = !empty($pagerParams['sort']) ? $pagerParams['sort'] : "";
            $this->order = !empty($pagerParams['order']) ? $pagerParams['order'] :'DESC';
            $this->min = !empty($pagerParams['min']) ? $pagerParams['min'] : 0;
            $this->max = !empty($pagerParams['max']) ? $pagerParams['max'] : 0;
            $this->alias = !empty($pagerParams['alias']) ? $pagerParams['alias'] : "";
        }
        $this->validateParams();
    }

    /**
     * 验证分页参数
     * @return [type] [description]
     */
    protected function validateParams()
    {
        $validate = array(
            array(is_numeric($this->limit) && $this->limit > 0,"limit必须为正整数"),
            array(is_numeric($this->page) && $this->page > 0, "page必须为正整数"),
            array(is_string($this->sort), "sort必须为字符串"),
            array(in_array(strtoupper($this->order), array("ASC", "DESC")), "order必须为ASC或者DESC"),
            array(is_string($this->min) || is_numeric($this->min), "min必须为字符串或数字"),
            array(is_string($this->max) || is_numeric($this->max), "min必须为字符串或数字"),
            array(is_string($this->alias), "alias必须为字符串"),
        );
        foreach($validate as $k=> $v)
        {
            if(!$v[0])
            {
                throw new \Exception('分页参数错误：'.$v[1], 210009);
            }
        }
    }
    /**
     * 设置别名
     * @param [type] $alias [description]
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        // 对字段加上表别名
        if($alias && $this->sort)
        {
            $this->sort = "$alias.{$this->sort}";
        }
    }
    /**
     * 获取分页查询参数
     * @param boolean $whereArray 是否使用数组组装条件，默认否
     * @param  [type] $pagerParams [description]
     * @param  string $alias       表别名
     * @return [type]              [description]
     */
    public function getPager($whereArray = false)
    {
        $whereStr = $orderStr = '';
        // 设置起点和终点
        if($this->sort)
        {
            $min = is_string($this->min) ? "'{$this->min}'": $this->min;
            $max = is_string($this->max) ? "'{$this->max}'": $this->max;

            if($this->min && $this->max)
            {
                $whereStr = "$this->sort > $min AND $this->sort < $max";
                $whereArray && $whereStr[$this->sort] = ['between', "$min, $max"];
            }
            else if($this->min)
            {
                $whereStr = "$this->sort > $min";
                $whereArray && $whereStr[$this->sort] = ['gt', $min];
            }
            else if($this->max)
            {
                $whereStr = "$this->sort < $max";
                $whereArray && $whereStr[$this->sort] = ['lt', $max];
            }
        }

        // 如果有页码，可以用页码进行分页
        if($this->page > 0)
        {
            $limitStr = $this->limit*($this->page-1). ",". $this->limit;
        }
        else
        {
            $limitStr = $this->limit;
        }

        // 排序
        if($this->sort)
        {
            $orderStr = $this->sort. " ".$this->order;
        }

        $pager = array(
            'where' => $whereStr,
            'order' => $orderStr,
            'limit' => $limitStr
        );

        return $pager;
    }
}