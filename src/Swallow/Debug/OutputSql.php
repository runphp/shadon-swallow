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
 * 输出sql
 *
 * @author     SpiritTeam
 * @since      2015年1月9日
 * @version    1.0
 */
class OutputSql extends \Swallow\Di\Injectable
{

    /**
     * 获取sql
     * 
     * @return array
     */
    public function getSqlList()
    {
        $profiles = $this->getDI()->getShared('Phalcon\Db\Profiler')->getProfiles();
        //$this->getProfiler()->getTotalElapsedSeconds();
        //$this->getProfiler()->getNumberTotalStatements();
        if (empty($profiles)) {
            return false;
        }
        $sqlArr = [];
        foreach ($profiles as $profile) {
            $sqlInfo = $this->sqlVariables($profile);
            if (empty($sqlInfo)){
                continue;
            }
            $sqlArr[] = $sqlInfo;
        }
        return $sqlArr;
    }
    
    /**
     * 获取最后sql
     *
     * @return array
     */
    public function getLastSql()
    {
        $profile = $this->getDI()->getShared('Phalcon\Db\Profiler')->getLastProfile();
        $sqlInfo = $this->sqlVariables($profile);
        return $sqlInfo;
    }
    
    /**
     * 处理sql
     * 
     * @param $profile
     * @return array
     */
    public function sqlVariables($profile)
    {
        if (empty($profile)) {
            return false;
        }
        $sql = $profile->getSQLStatement();
        if (preg_match('/ecm_table_sync/i', $sql)) {
            return false;
        }
        $sqlVariables = $profile->getsqlVariables();
        if (! empty($sqlVariables) && is_array($sqlVariables)) {
            krsort($sqlVariables);
            foreach ($sqlVariables as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $v = is_string($v) ? '"' . $v . '"' : $v;
                        $sql = str_replace(':' . $key . $k, $v, $sql);
                    }
                } else {
                    $val = is_string($val) ? '"' . $val . '"' : $val;
                    $sql = str_replace(':' . $key, $val, $sql);
                }
            }
        }
        $sqlInfo = [
            'sql' => $sql,
            'initialTime' => $profile->getInitialTime(),
            'finalTime' => $profile->getFinalTime(),
            'totalTime' => $profile->getTotalElapsedSeconds()
        ];
        return $sqlInfo;
    }
}
