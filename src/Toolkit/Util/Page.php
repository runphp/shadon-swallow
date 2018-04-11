<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Toolkit\Util;

use Swallow\Paginator\Adapter\QueryBuilder;
use Swallow\Paginator\Adapter\NativeArray;

/**
 * 分页类
 *
 * @author    姚礼伟<yaoliwei@eelly.net>
 * @since     2015年9月9日
 * @version   1.0
 */
class Page
{

    /**
     * 分页显示定制
     * @var []
     */
    private static $config = array(
        'header' => '<span class="sbs-paging-txt">共<em>%TOTAL_PAGE%</em> 页</span>
                     <span class="sbs-paging-txt">到第</span>
                         <span class="sbs-paging-which">
                              <input type="text" name="CurrPage" id="CurrPage" type="text" value="%NOW_PAGE%">
                         </span>
                     <a class="sbs-paging-btn" href="javascript:void(0)"id="pageTrunTo">确定</a>', 
        'prev' => '<', 
        'next' => '>', 
        'first' => '首页', 
        'last' => '尾页', 
        'theme' => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%', 
        'showPagesNum' => 8);

    /**
     * 分页模版
     * @var $template
     */
    private $template;

    /**
     * 实现分页
     *
     * @param object $builder        分页数据
     * @param int    $limit          每页显示多少条数据
     * @param int    $currentPage    当前页数
     * @return stdclass
     * @author 姚礼伟<yaoliwei@eelly.net>
     * @since  2015年9月9日
     */
    public static function getPaginate($builder, $limit, $currentPage)
    {
        $paginator = new QueryBuilder(array("builder" => $builder, "limit" => $limit, "page" => $currentPage));
        
        $page = $paginator->getPaginate();
        $template = Page::showPage($page);
        $page->template = $template;
        return $page;
    }

    /**
     * 实现数组分页
     * 
     * @param array $data 分页的数据
     * @param int $limit  每页显示多少条数据
     * @param int $currentPage 当前页数
     * @param int $totalRows 总记录数
     * @return stdClass
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年10月14日
     */
    public static function getPaginateArray(array $data, $limit, $currentPage, $totalRows)
    {
        $paginator = new NativeArray(['data' => $data, 'limit' => $limit, 'page' => $currentPage, 'totalRows' => $totalRows]);
        
        $page = $paginator->getPaginate();
        $template = Page::showPage($page);
        $page->template = $template;
        return $page;
    }

    /**
     * 生成链接URL
     * @param  integer $page 页码
     * @return string
     */
    private static function url($pages)
    {
        $url = preg_replace('/(&|\?)page=[^&]+/', '', $_SERVER['REQUEST_URI']);
        if (substr_count($url, '&') == 0 && substr_count($url, '?') == 0) {
            $url = $url . '?' . 'page=' . $pages;
        } else {
            $url = $url . '&' . 'page=' . $pages;
        }
        return $url;
    }

    /**
     * 组装分页链接
     *
     * @param object $page 分页对象
     * @return string
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年10月16日
     */
    public static function showPage($page)
    {
        if (0 == $page->total_items)
            return '';
        
        $nowCoolPage = Page::$config['showPagesNum'] / 2;
        $nowCoolPageCeil = ceil($nowCoolPage);
        
        /* 计算分页信息 */
        if (! empty($page->total_pages) && $page->current > $page->total_pages) {
            $page->current = $page->total_pages;
        }
        
        //上一页
        $up_page = $page->before > 0 ? '<a class="sbs-paging-pre" href="' . Page::url($page->before) . '">' . Page::$config['prev'] . '</a>' : '';
        //当前页为第一页 按钮为灰色不可点击
        $up_page && $page->current == 1 && $up_page = '<a class="sbs-paging-pre disabled" >' . Page::$config['prev'] . '</a>';
        
        //下一页
        $down_page = ($page->next <=
             $page->total_pages) ? '<a class="sbs-paging-next" href="' . Page::url($page->next) . '">' . Page::$config['next'] . '</a>' : '';
        //当前页为最后一页 下一页按钮为灰色不可点击
        $down_page && $page->current == $page->last && $down_page = '<a class="sbs-paging-pre disabled" >' . Page::$config['next'] . '</a>';
        
        //第一页
        $the_first = '<a class="sbs-paging-btn" href="' . Page::url(1) . '">' . Page::$config['first'] . '</a>';
        //当前页为第一页 按钮为灰色不可点击
        $page->current == 1 && $the_first = '<a class="sbs-paging-pre disabled" >' . Page::$config['first'] . '</a>';
        
        //最后一页
        $the_end = '<a class="sbs-paging-btn" href="' . Page::url($page->total_pages) . '">' . Page::$config['last'] . '</a>';
        //当前页为最后一页 最后一页按钮为灰色不可点击
        $page->current == $page->last && $the_end = '<a class="sbs-paging-pre disabled" >' . Page::$config['last'] . '</a>';
        
        
        //数字连接
        $link_page = "";
        for ($i = 1; $i <= Page::$config['showPagesNum']; $i ++) {
            if (($page->current - $nowCoolPage) <= 0) {
                $p = $i;
            } elseif (($page->current + $nowCoolPage - 1) >= $page->total_pages) {
                $p = $page->total_pages - Page::$config['showPagesNum'] + $i;
            } else {
                $p = $page->current - $nowCoolPageCeil + $i;
            }
            
            if ($p > 0 && $p != $page->current) {
                if ($p <= $page->total_pages) {
                    $link_page .= '<a class="sbs-paging-item" href="' . Page::url($p) . '">' . $p . '</a>';
                } else {
                    break;
                }
            } else {
                if ($p > 0 && $page->total_pages != 1) {
                    $link_page .= '<a class="sbs-paging-item current" href="javascript:;">' . $p . '</a>';
                }
            }
        }
        
        //替换分页内容
        $page_str = str_replace(
            array('%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%'), 
            array(
                Page::$config['header'], 
                $page->current, 
                $up_page, 
                $down_page, 
                $the_first, 
                $link_page, 
                $the_end, 
                $page->total_items, 
                $page->total_pages), Page::$config['theme']);
        return '<div class="sbs-paging">' . $page_str . '</div>
                <script type="text/javascript">
                        $(document).ready(function(){
                            $("#pageTrunTo").bind("click",function(){
                                var pageno = $("#CurrPage").val().match("[0-9]+");
                                var totalPages = ' . $page->total_pages . ';
                                if(pageno != ""){
                                    if(pageno > totalPages){
                                        pageno = totalPages;
                                    }
                                    if(location.href.match(/\?page/) != null){
                                        var url = location.href.replace(/\?page=' . $page->current . '/i, "?page="+pageno);
                                        location.href = url;
                                    } else {
                                        var url = location.href.replace(/&page=' . $page->current . '/i, "");
                                        location.href = url + "&page="+pageno;
                                    }
                                }
                            });
                        });
                 </script>';
    }

    /**
     * 设置分页样式
     * 
     * @param array $pageStyle  设置样式 [$name => $value]
     * @author zengzhihao<zengzhihao@eelly.net>
     * @since  2015年10月10日
     */
    public static function setPageStyle(array $pageStyle)
    {
        foreach ($pageStyle as $name => $value) {
            isset(Page::$config[$name]) && Page::$config[$name] = $value;
        }
    }
}