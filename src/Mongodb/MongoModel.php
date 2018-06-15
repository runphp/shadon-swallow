<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\Mongodb;

use Swallow\Core\Conf;
use Swallow\Debug\Trace;

/**
 * 操作日志.
 *
 * @author    何砚文<heyanwen@eelly.net>
 *
 * @since     2016-6-13
 *
 * @version   1.0
 */
class MongoModel extends Mongodb
{
    /**
     * 字段空间.
     *
     * @var array
     */
    public $filedScope = [];

    /**
     * 字段类型说明.
     *
     * @var array
     */
    public $filedType = [];

    /**
     * 是否自动创建自增主键.
     *
     * @var bool
     */
    public $autoIncreMent = false;

    /**
     * 构造.
     *
     * @author  chenjinggui<chenjinggui@eelly.net>
     *
     * @since   2015年7月20日
     *
     * @version 1.0
     */
    public function __construct()
    {
        $calledClass = get_called_class();
        $calledClass = str_replace('AopProxy\\', '', $calledClass);
        $arr = explode('\\', $calledClass, 4);
        if (empty($arr) || empty($arr[1]) || $arr[1] != 'Model') {
            Trace::dump('只能在模型层调用！');
        }
        $config = Conf::get($arr[0].'/data/conn');
        $db = strtolower($arr[2]);
        $collection = $db.'_'.$this->getCollection($arr[3]);
        parent::__construct($config, $db, $collection);
    }

    public static function getInstance()
    {
        static $instance;
        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

    /**
     * 集合选择（含手动按日期分集合）.
     *
     * @param string $modelName 模型名称
     *
     * @author  chenjinggui<chenjinggui@eelly.net>
     *
     * @since   2015年7月20日
     *
     * @version 1.0
     */
    protected function getCollection($modelName)
    {
        $modelName = substr($modelName, 0, -5);

        return strtolower(preg_replace('/[A-Z]/', '_\\0', lcfirst($modelName)));
    }

    /**
     * 添加日志信息.
     *
     * @param array $data  日志信息
     * @param bool  $batch 是否批量添加
     *
     * @author  何砚文<heyanwen@eelly.net>
     *
     * @since   2016-6-13
     */
    public function addInfo(array $data, $batch = false)
    {
        // 参数校验
        if (empty($logData = $this->fileTypeSwitch($this->getLogData($data, $batch))) || !$this->checkUnique($logData)) {
            return false;
        }

        // 是否自动创建自增主键
        $this->autoIncreMent && $logData = $this->addPrimaryKey($logData, $batch);

        $result = $batch ? $this->addAll($logData) : $this->add($logData);

        return empty($result['ok']) ? false : (empty($batch) ? $this->insertId : $result['ok']);
    }

    /**
     * 日志信息校验.
     *
     * @param array $data  日志信息
     * @param bool  $batch 是否批量添加
     *
     * @return array
     *
     * @author  何砚文<heyanwen@eelly.net>
     *
     * @since   2016-6-13
     */
    private function getLogData(array $data, $batch = false)
    {
        // 参数校验
        if (empty($data) || empty($this->filedScope['base'])) {
            return [];
        }

        // 遍历校验基础字段
        $baseField = explode(',', str_replace(' ', '', $this->filedScope['base']));
        foreach ($baseField as $field) {
            if (empty($batch) && !isset($data[$field])) {
                return [];
            } elseif ($batch) {
                array_map(function ($info) use ($field) { if (!isset($info[$field])) { return []; } }, $data);
            }
        }

        return $data;
    }

    /**
     *　字段类型强制转换(保持字段类型一致).
     *
     * @param array $data 日志信息
     *
     * @return array
     *
     * @author  何砚文<heyanwen@eelly.net>
     *
     * @since   2016-6-15
     */
    private function fileTypeSwitch(array $data)
    {
        $logData = [];
        foreach ($data as $key => $value) {
            $logData[$key] = is_array($value) ? $this->fileTypeSwitch($value) : (!empty($this->filedType[$key]) ? $this->filedType[$key]($value) : $value);
        }

        return $logData;
    }

    /**
     * 校验数据唯一性.
     *
     * @param array $data
     *
     * @return bool
     *
     * @author  Heyanwen<heyanwen@eelly.net>
     *
     * @since   2016-9-18
     */
    private function checkUnique(array $data)
    {
        // 参数校验
        if (empty($this->filedScope['unique'])) {
            return true;
        }

        // 组装查询条件
        $condition = [];
        $uniqueField = explode(',', str_replace(' ', '', $this->filedScope['unique']));
        foreach ($uniqueField as $field) {
            $condition[$field] = $data[$field];
        }

        // 获取数据
        return $this->where($condition)->count() ? false : true;
    }

    /**
     * 实现Mongodb自增主键.
     *
     * @param array $data
     * @param bool  $batch
     *
     * @return array
     *
     * @author  Heyanwen<heyanwen@eelly.net>
     *
     * @since   2016-10-12
     */
    private function addPrimaryKey($data, $batch = false)
    {
        $mongoData = $batch ? $data : [$data];
        foreach ($mongoData as $key => $val) {
            $mongoData[$key] = array_merge(['rec_id' => $this->getNextId()], $val);
        }

        return $batch ? $mongoData : current($mongoData);
    }

    /**
     * 整形转换为mongoId类型.
     *
     * @param array/int $val
     *
     * @return array/int
     *
     * @author Heyanwen<heyanwen@eelly.net>
     *
     * @since 2016-9-20
     */
    public function toMongoId($val)
    {
        // 参数校验
        if (empty($val)) {
            return false;
        }
        $result = [];
        $data = is_array($val) ? $val : [$val];
        foreach ($data as $id) {
            $result[] = createObjectId($id);
        }

        return is_array($val) ? $result : current($result);
    }
}
