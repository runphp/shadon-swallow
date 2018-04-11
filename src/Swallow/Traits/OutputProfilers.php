<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link http://www.eelly.com
 * @license 衣联网版权所有
 */
namespace Swallow\Traits;

/**
 * session Trait
 *
 * @author SpiritTeam
 * @since 2015年1月13日
 * @version 1.0
 *         
 */
trait OutputProfilers
{

    /**
     * 輸出分析報告
     */
    public function OutputProfilers()
    {
        $outputSql = $this->getDI()->getShared('Swallow\Debug\OutputSql');
        $sqlList = $outputSql->getSqlList();
        if (empty($sqlList)) {
            return false;
        }
        $content = "-----------------------------------------------------------------------------------------------\n";
        foreach ($sqlList as $val) {
            $content .= "SQL 语句: " . $val['sql'] . "\n";
            $content .= "开始时间: " . $val['initialTime'] . "\n";
            $content .= "结束时间: " . $val['finalTime'] . "\n";
            $content .= "执行时间: " . $val['totalTime'] . "\n";
            $content .= "-----------------------------------------------------------------------------------------------\n";
            $content .= "-----------------------------------------------------------------------------------------------\n";
        }
        $logger = $this->getDI()->getLogger();
        $logger->setReplace(true)
            ->setName('dbProfiles')
            ->log($content);
    }
}
