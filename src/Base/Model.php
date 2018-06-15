<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swallow\Base;

use Swallow\Annotations\AnnotationProxyFactory;
use Swallow\Core\Base;
use Swallow\Core\Db;
use Swallow\Core\Log;
use Swallow\Debug\Verify;

/**
 * 模块 -> 模形基类
 *        提供与数据库存的对接.
 *
 * @author     SpiritTeam
 *
 * @since      2015年1月12日
 *
 * @version    1.0
 */
abstract class Model extends Base
{
    /**
     * 表名.
     *
     * @var string
     */
    public $tableName = '';

    /**
     * 全表名.
     *
     * @var string
     */
    public $tableFullName = '';

    /**
     * 别名.
     *
     * @var string
     */
    public $alias = '';

    /**
     * 表前缀
     *
     * @var string
     */
    public $prefix = '';

    /**
     * 主键.
     *
     * @var string
     */
    public $pk = 'id';

    /**
     * 是否加 ` 到表名.
     *
     * @var bool
     */
    public $isEncodeTableName = true;

    /**
     * 字段空间.
     *
     * @var string
     */
    public $filedScope = [];

    /**
     * 上条sql记录.
     *
     * @var string
     */
    protected $sql = '';

    /**
     * 上条插入的id.
     *
     * @var int
     */
    protected $lastInsertId = 0;

    /**
     * 数据库对象
     *
     * @var Db
     */
    private $db = null;

    /**
     * 条件容器.
     *
     * @var array
     */
    private $option = [];

    /**
     * 命名空间.
     *
     * @var string
     */
    private $nameSapce = '';

    /**
     * 事务启用.
     *
     * @var int
     */
    private static $transTimes = 0;

    /**
     * 构造器.
     */
    final protected function __construct()
    {
        $className = get_class($this);
        $classNamePath = explode('\\', $className);
        $className = substr(array_pop($classNamePath), 0, -5);
        'AopProxy' == current($classNamePath) && array_shift($classNamePath);
        $this->nameSapce = implode('\\', $classNamePath);
        $this->db = Db::getInstance(current($classNamePath));
        $this->prefix = Db::$prefix;
        //分析表名
        if (empty($this->tableName)) {
            $this->tableName = strtolower(preg_replace('/[A-Z]/', '_\\0', lcfirst($className)));
        }
        //分析别名
        if (empty($this->alias)) {
            $alias = preg_replace('/[a-z]/', '', $className);
            $this->alias = strtolower($alias);
        }
        if (empty($this->tableFullName)) {
            $this->tableFullName = $this->prefix.$this->tableName;
        }
        $this->init();
    }

    /**
     * 获取单例.
     *
     * @return static
     */
    public static function getInstance()
    {
        $calledClass = static::class;
        // 校验
        Verify::callClass($calledClass);
        $calledParentClass = get_parent_class($calledClass);
        $di = \Phalcon\Di::getDefault();
        /**
         * @var \Swallow\Annotations\AnnotationProxyFactory $annotationProxyFactory
         */
        $annotationProxyFactory = $di->getShared(AnnotationProxyFactory::class);
        $args = func_get_args();

        $proxyObject = $annotationProxyFactory->createProxy($calledClass, function () use ($args, $calledClass) {
            $group = strstr($calledClass, '\\', true);
            $reflectionClass = new \ReflectionClass($calledClass);
            $instance = $reflectionClass->newInstanceWithoutConstructor();
            $constructor = $reflectionClass->getConstructor();
            if (!$constructor->isPublic()) {
                $constructor->setAccessible(true);
            }
            $constructor->invokeArgs($instance, $args);
            $instance->setModuleName($group);

            return $instance;
        }, [
            'eventType' => $calledParentClass,
        ]);

        return $proxyObject;
    }

    /**
     * 说明:设置主键.
     *
     * @param string $field 字段
     *
     * @return self
     */
    public function pk($field)
    {
        $this->pk = $field;

        return $this;
    }

    /**
     * 说明:设置字段.
     *
     * @param mixed $fields 字段
     *
     * @return self
     */
    public function field($fields)
    {
        if (true === $fields) {
            return $this->field($this->getFields(true));
        }
        $checkField = function ($field) {
            return !strpos($field, '.') && !stripos($field, ' ') && !stripos($field, ')');
        };
        if (is_array($fields)) {
            foreach ($fields as &$field) {
                $field = $checkField($field) ? "`{$field}`" : $field;
            }
            $fields = implode(',', $fields);
        } elseif (isset($this->filedScope[$fields])) {
            $fields = $this->filedScope[$fields];
        } elseif ($checkField($fields)) {
            $fields = '`'.str_replace(',', '`,`', $fields).'`';
        }
        $this->option['field'] = $fields;

        return $this;
    }

    /**
     * 说明:设置连表
     *      数组方式只能连同模块的表.
     *
     * @param string $join     连接的字串符
     * @param string $joinType 链接类型
     *
     * @return self
     */
    public function join($join, $joinType = 'left')
    {
        if (is_string($join)) {
            $this->option['join'] = $this->alias.' '.$this->tableNameFix($join);
            $this->option['join'] = preg_replace_callback('/__([A-Z_-]+)__/sU',
                function ($match) {
                    return $this->prefix.strtolower($match[1]);
                }, $this->option['join']);
        } elseif (is_array($join)) {
            $joinType = 'left' == $joinType ? ' LEFT JOIN' : ' '.strtoupper($joinType).' JOIN';
            $this->option['join'] = $this->alias;
            foreach ($join as $table => $on) {
                //.开关的联到基础服务表
                if ('.' == $table[0]) {
                    $class = substr($this->nameSapce, 0, strrpos($this->nameSapce, '\\')).'\\'.substr($table, 1).'Model';
                } else {
                    $class = $this->nameSapce.'\\'.$table.'Model';
                }
                $class = $class::getInstance();
                $joinTableName = $class->isEncodeTableName ? '`'.$class->getTableName(true).'`' : $class->getTableName(true);
                if (!empty($this->option['forceindex']) && !empty($this->option['forceindex'][$class->getTableName(true)])) {
                    $this->option['join'] .= $joinType.' '.$joinTableName.' '.$class->getAlias().' '.$this->option['forceindex'][$class->getTableName(true)].' ON '.$on;
                } else {
                    $this->option['join'] .= $joinType.' '.$joinTableName.' '.$class->getAlias().' ON '.$on;
                }
            }
        }

        return $this;
    }

    /**
     * 说明:设置强制索引
     *      联表强制索引只能放在join前调用.
     *
     * @param string $tableFullName  强制索引的表全名
     * @param string $tableIndexName 强制索引的索引名
     *
     * @return self
     */
    public function forceindex($tableFullName, $tableIndexName)
    {
        $this->option['forceindex'][$tableFullName] = 'FORCE INDEX(`'.$tableIndexName.'`)';

        return $this;
    }

    /**
     * 说明:设置限制.
     *
     * @param string $limit
     * @param int    $page
     *
     * @return self
     */
    public function limit($limit, $page = 0)
    {
        if (empty($page)) {
            $this->option['limit'] = 'LIMIT '.$limit;
        } else {
            $this->option['limit'] = 'LIMIT '.$limit * ($page - 1).','.$limit;
        }

        return $this;
    }

    /**
     * 说明:设置分组.
     *
     * @param string $group
     *
     * @return self
     */
    public function group($group)
    {
        $this->option['group'] = 'GROUP BY '.$group;

        return $this;
    }

    /**
     * 说明:设置having.
     *
     * @param string $having
     *
     * @return self
     */
    public function having($having)
    {
        $this->option['having'] = 'HAVING '.$having;

        return $this;
    }

    /**
     * 说明:设置排序.
     *
     * @param string $order
     *
     * @return self
     */
    public function order($order)
    {
        $this->option['order'] = 'ORDER BY '.$order;

        return $this;
    }

    /**
     * 说明:读取数据的主键.
     *
     * @param string $index
     *
     * @return self
     */
    public function index($index = '')
    {
        $this->option['index'] = $index;

        return $this;
    }

    /**
     * 说明:设置条件.
     *
     * @param mixed $where 条件可以为字串符可以为数组方式
     *
     * @return self
     */
    public function where($where)
    {
        if (empty($where)) {
            return $this;
        }
        if (is_array($where)) {
            $isOr = self::parseIsOr($where);
            $where = self::parseWhere($where, $isOr);
        }
        $this->option['where'] = 'WHERE '.$where;

        return $this;
    }

    /**
     * 说明:设置于表名，为空为读取表名.
     *
     * @param string $table     表名
     * @param boolen $full      带不带前缀
     * @param boolen $tableSubQ 是否子查询
     *
     * @return self
     */
    public function table($table = '', $full = false)
    {
        if ($full) {
            $this->tableName = $this->tableFullName = $this->tableNameFix($table);
        } else {
            $this->tableName = $table;
            $this->tableFullName = $this->prefix.$table;
        }

        return $this;
    }

    /**
     * 说明:设置读取或保存时要序列化的字段.
     *
     * @param string $field 须要处理序列化的字段，多个用<,>逗号分开
     *
     * @return self
     */
    public function serialize($field)
    {
        $this->option['serialize'] = $field;

        return $this;
    }

    /**
     * 说明:运行sql指令.
     *
     * @param string $sql
     *
     * @return array
     */
    public function execute($sql)
    {
        $this->sql = $this->tableNameFix($sql);
        $this->db->execute($sql);

        return $this->db->affectedRows();
    }

    /**
     * 说明:查询自定义select语句.
     *
     * @param string $sql
     *
     * @return array
     */
    public function query($sql)
    {
        $this->sql = $this->tableNameFix($sql);
        $q = $this->db->query($sql);
        if (false === $q) {
            return $q;
        }
        $q = $this->setIndex($q);
        $q = $this->unSerialize($q);
        $this->clearSet();

        return $q;
    }

    /**
     * 查询一行数据.
     *
     * @return array
     */
    public function getOne()
    {
        $sql = $this->buildQuerySql(false);
        if (!stripos($sql, 'limit')) {
            $sql = trim($sql.' LIMIT 1;');
        }
        $q = $this->db->query($sql);
        if (false === $q) {
            return $q;
        }
        $this->clearSet();

        return $q ? current($q) : $q;
    }

    /**
     * 说明:选择.
     *
     * @return array
     */
    public function select()
    {
        $sql = $this->buildQuerySql();
        $q = $this->db->query($sql);
        if (false === $q) {
            return $q;
        }
        $q = $this->setIndex($q);
        $q = $this->unSerialize($q);
        $this->clearSet();

        return $q;
    }

    /**
     * 说明:选择一条数据.
     *
     * @param string $id
     *
     * @return array
     */
    public function find($id = null)
    {
        $this->limit(1);
        if (null !== $id) {
            $this->where([$this->pk => $id]);
        }
        $sql = $this->buildQuerySql();
        $q = $this->db->query($sql);
        if (false === $q) {
            return $q;
        }
        $q = is_array($q) ? current($q) : $q;
        $q = $this->unSerialize($q);
        $this->clearSet();

        return $q;
    }

    /**
     * 说明:选择一个列值的数据.
     *
     * @param string $field
     *
     * @return mixed|string
     */
    public function getFieldOne($field)
    {
        $arr = $this->getField($field);
        if (false != $arr) {
            return current($arr);
        } else {
            return '';
        }
    }

    /**
     * 说明:选择一条数据.
     *
     * @param string $field
     *
     * @return array
     */
    public function getField($field)
    {
        empty($this->option['field']) && $this->field($field);
        $sql = $this->buildQuerySql();
        $q = $this->db->query($sql);
        if (false === $q) {
            return $q;
        }
        $q = $this->unSerialize($q);
        if (is_array($q)) {
            if (empty($this->option['index'])) {
                $q = array_column($q, $field);
            } else {
                $q = array_column($q, $field, $this->option['index']);
            }
        }
        $this->clearSet();

        return $q;
    }

    /**
     * 说明:统计总数.
     *
     * @param string $field
     *
     * @return int
     */
    public function count($field = '0')
    {
        if (!empty($this->option['order'])) {
            unset($this->option['order']);
        }
        $this->option['limit'] = 'LIMIT 1';
        $this->option['field'] = 'COUNT('.$field.') AS row';
        $sql = $this->buildQuerySql();
        $q = $this->db->query($sql);
        if (false === $q) {
            return $q;
        }
        $this->clearSet();

        return (int) ($q[0]['row']);
    }

    /**
     * 说明:建立sql语句给外部.
     *
     * @return string
     */
    public function buildSql()
    {
        $sql = $this->buildQuerySql(false);
        $this->clearSet();

        return $sql;
    }

    /**
     * 说明:删除.
     *
     * @param int    $id 删除的id号可以留空
     * @param string $pk 重定字段
     *
     * @return int 影响的行数
     */
    public function delete($id = null, $pk = null)
    {
        if (!empty($id)) {
            empty($pk) && $pk = $this->pk;
            $this->where([$pk => ['in', $id]]);
        }
        $where = $this->option['where'];
        $sql = 'DELETE FROM '.$this->tableFullName.' '.$where;
        if (!empty($this->option['limit'])) {
            $sql .= ' LIMIT '.(int) $this->option['limit'];
        }
        $sql .= ';';
        $this->sql = $sql;
        $this->db->execute($this->sql);
        $this->clearSet();

        return $this->db->affectedRows();
    }

    /**
     * 说明:添加.
     *
     * @param array $data      添加的数据
     * @param bool  $isReplace 是否采用REPLACE INTO方式
     *
     * @return int 返回id号
     */
    public function add($data, $isReplace = false)
    {
        $data = $this->doSerialize($data);
        $sql = $isReplace ? 'REPLACE INTO' : 'INSERT INTO';
        $sql .= ' '.$this->tableFullName.' (`'.implode('`,`', array_keys($data)).'`) VALUES';
        $sql .= " ('".implode("','", self::safe($data))."')";
        $this->sql = $sql;
        $r = $this->db->execute($sql);
        if (false === $r) {
            return $r;
        }
        $this->lastInsertId = $this->db->insertId();
        $this->clearSet();

        return $this->lastInsertId;
    }

    /**
     * 说明:批量添加.
     *
     * @param array $data      添加的数据
     * @param bool  $isReplace
     *
     * @return int 最后id号
     */
    public function addAll($data, $isReplace = false)
    {
        $data = $this->doSerialize($data);
        $sql = $isReplace ? 'REPLACE INTO' : 'INSERT INTO';
        $sql .= ' '.$this->tableFullName.' (`'.implode('`,`', array_keys($data[0])).'`) VALUES';
        $row = [];
        foreach ($data as $value) {
            $row[] = " ('".implode("','", self::safe($value))."')";
        }
        $sql .= implode(',', $row).';';
        $this->sql = $sql;
        $r = $this->db->execute($sql);
        if (false === $r) {
            return $r;
        }
        $this->lastInsertId = $this->db->insertId();
        $this->clearSet();

        return $this->lastInsertId;
    }

    /**
     * 说明:保存.
     *
     * @param array|string $data        保存的数据
     * @param string       $pk          主键
     * @param bool         $lowPriority
     *
     * @return int 影响的行数
     */
    public function save($data, $pk = null, $lowPriority = false)
    {
        $data = $this->doSerialize($data);
        $sql = 'UPDATE';
        $lowPriority && $sql .= ' LOW_PRIORITY';
        $sql .= ' `'.$this->tableFullName.'`';
        if (empty($this->option['where'])) {
            empty($pk) && $pk = $this->pk;
            $this->where([$pk => $data[$pk]]);
            unset($data[$pk]);
        }
        $where = $this->option['where'];
        $sql .= ' SET';
        if (is_string($data)) {
            $sql .= ' '.$data;
        } else {
            $set = [];
            foreach ($data as $key => $value) {
                if ('_string' != $key) {
                    $value = self::safe($value);
                    $set[] = " `{$key}` = '{$value}' ";
                } else {
                    if (is_array($value)) {
                        $value = implode(' ,', $value);
                    }
                    $set[] = " {$value} ";
                }
            }
            $sql .= ' '.implode(',', $set);
        }
        $sql .= ' '.$where;
        if (!empty($this->option['limit'])) {
            $sql .= ' LIMIT '.(int) $this->option['limit'];
        }
        $sql .= ';';
        $this->option = [];
        $this->sql = $sql;
        $r = $this->db->execute($sql);
        if (false === $r) {
            return $r;
        }
        $this->clearSet();

        return $this->db->affectedRows();
    }

    /**
     * 说明:读取表名.
     *
     * @param sting $name 常量名
     *
     * @return mixed
     */
    public function getConst($name)
    {
        $key = 'static::'.$name;

        return defined($key) ? constant($key) : null;
    }

    /**
     * 说明:读取表名.
     *
     * @param boolen $isFull 带不带前缀
     *
     * @return string
     */
    public function getTableName($isFull = false)
    {
        if ($isFull) {
            return $this->tableFullName;
        } else {
            return $this->tableName;
        }
    }

    /**
     * 说明:读取表名.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * 说明:获取sql.
     *
     * @return array
     */
    public function getLastSql()
    {
        return $this->sql;
    }

    /**
     * 说明:读取最后一个插入的id号.
     *
     * @return array
     */
    public function getLastInsertId()
    {
        return $this->lastInsertId;
    }

    /**
     * 说明:读取所有字段名.
     *
     * @param bool $onlyFielsName 只返回字段名列表
     *
     * @return array
     */
    public function getFields($onlyFielsName = false)
    {
        static $info = null;
        if (isset($info)) {
            return $onlyFielsName ? array_keys($info) : $info;
        }
        $result = $this->db->query('SHOW COLUMNS FROM `'.$this->tableFullName.'`;');
        $info = [];
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['Field']] = [
                    'name'    => $val['Field'],
                    'type'    => $val['Type'],
                    'notnull' => (bool) ('' === $val['Null']),  // not null is empty, null is yes
                    'default' => $val['Default'],
                    'primary' => ('pri' == strtolower($val['Key'])),
                    'autoinc' => ('auto_increment' == strtolower($val['Extra'])), ];
            }
        }

        return $onlyFielsName ? array_keys($info) : $info;
    }

    /**
     * 读取数据库错误.
     */
    public function getError()
    {
        return $this->db->getError();
    }

    /**
     * 说明:创建数据.
     *
     * @param string $field 过滤只使用指定的字段
     * @param array  $post  原数据,不填用post
     *
     * @return array
     */
    public function create($field, $post)
    {
        $post = !empty($post) ? $post : $_POST;
        $field = explode(',', $field);
        $odata = [];
        foreach ($field as $value) {
            if (isset($post[$value])) {
                $odata[$value] = $post[$value];
            }
        }

        return $odata;
    }

    /**
     * 启动事务
     *
     * @return bool
     */
    public function startTrans()
    {
        $r = false;
        if (0 == self::$transTimes) {
            $r = $this->db->beginTransaction();
        }
        $r && self::$transTimes++;

        return $r;
    }

    /**
     * 用于非自动提交状态下面的查询提交.
     *
     * @param bool $startStatus
     *
     * @return bool
     */
    public function commit()
    {
        if (self::$transTimes > 0) {
            $this->db->commit();
            self::$transTimes = 0;
            $this->db->endTransaction();
        }

        return true;
    }

    /**
     * 事务回滚.
     *
     * @param bool $startStatus
     *
     * @return bool
     */
    public function rollback()
    {
        if (self::$transTimes > 0) {
            $this->db->rollback();
            self::$transTimes = 0;
            $this->db->endTransaction();
        }

        return true;
    }

    /**
     * 安全过滤.
     *
     * @param array|string $var
     *
     * @return mixed
     */
    public static function safe($var)
    {
        if (is_array($var)) {
            return array_map('self::safe', $var);
        } elseif (is_string($var)) {
            return addslashes($var);
        } else {
            return $var;
        }
    }

    /**
     * 初始化.
     */
    protected function init(): void
    {
    }

    /**
     * 设定index.
     *
     * @param array $lists
     *
     * @return array
     */
    private function setIndex(array $lists)
    {
        if (empty($this->option['index'])) {
            return $lists;
        }
        $retval = [];
        // $lists 出现内存不足记录当时执行的sql语句，无问题后请删除
        if (2000 < strlen($this->sql)) {
            $sql = sprintf('%s ... (%d chars) ... %s', substr($this->sql, 0, 1000), strlen($this->sql) - 2000, substr($this->sql, -1000, 1000));
        } else {
            $sql = $this->sql;
        }
        Log::pushCustomFatalInfo(['lastSql' => $sql]);
        $k = $this->option['index'];
        foreach ($lists as $value) {
            $retval[$value[$k]] = $value;
        }

        return $retval;
    }

    /**
     * 说明:内部建立sql语句.
     *
     * @param bool $end 是不加上;号
     *
     * @return $this
     */
    private function buildQuerySql($end = true)
    {
        $sql = 'SELECT';
        $sql .= empty($this->option['field']) ? ' *' : ' '.$this->option['field'];
        $sql .= ' FROM `'.$this->tableFullName.'`';
        !empty($this->option['forceindex']) && !empty($this->option['forceindex'][$this->tableFullName]) && $sql .= ' '.$this->option['forceindex'][$this->tableFullName];
        !empty($this->option['join']) && $sql .= ' '.$this->option['join'];
        !empty($this->option['where']) && $sql .= ' '.$this->option['where'];
        !empty($this->option['group']) && $sql .= ' '.$this->option['group'];
        !empty($this->option['having']) && $sql .= ' '.$this->option['having'];
        !empty($this->option['order']) && $sql .= ' '.$this->option['order'];
        !empty($this->option['limit']) && $sql .= ' '.$this->option['limit'];
        $end && $sql .= ';';
        $this->sql = $sql;

        return $sql;
    }

    /**
     * 解析是否是or拼接.
     *
     * @param array $where
     *
     * @return array
     */
    private static function parseIsOr(array &$where)
    {
        $isOr = false;
        if (isset($where['_logic'])) {
            $isOr = 'OR' == strtoupper($where['_logic']);
            unset($where['_logic']);
        }

        return $isOr;
    }

    /**
     * 解析where.
     *
     * @param array $wheres
     * @param bool  $isOr
     */
    private static function parseWhere(array $wheres, $isOr = false)
    {
        $sqlWhere = [];
        $comparison = [
            'eq'      => '=',
            'neq'     => '<>',
            'gt'      => '>',
            'egt'     => '>=',
            'lt'      => '<',
            'elt'     => '<=',
            'notlike' => 'NOT LIKE',
            'like'    => 'LIKE',
            'in'      => 'IN',
            'notin'   => 'NOT IN',
            'between' => 'BETWEEN', ];
        $autoEq = function ($where) {
            return is_array($where) ? $where : ['eq', $where];
        };
        $autoField = function ($field) {
            return false === strpos($field, '.') ? "`{$field}`" : $field;
        };
        foreach ($wheres as $field => &$where) {
            if ('_complex' == $field) {
                $or = self::parseIsOr($where);
                $where = '('.self::parseWhere($where, $or).')';
                continue;
            } elseif ('_' == substr($field, 0, 1)) {
                continue;
            }
            $where = $autoEq($where);
            if (!empty($where['_multi'])) {
                unset($where['_multi']);
                $isAnd = 0 < strpos($field, '&');
                $fields = explode($isAnd ? '&' : '|', $field);
                $tmp = [];
                foreach ($fields as $k => $f) {
                    $tmp[$f] = $where[$k];
                }
                $where = '( '.self::parseWhere($tmp, !$isAnd).' )';
            } else {
                $join = ' AND ';
                if (!is_array($where[0])) {
                    $where = [$where];
                } elseif (is_string($end = end($where)) && in_array($end = strtolower($end), ['or', 'and'])) {
                    $join = ' '.strtoupper(array_pop($where)).' ';
                }
                $field = $autoField($field);
                foreach ($where as &$value) {
                    $value = $autoEq($value);
                    $val = strtolower($value[0]);
                    switch ($val) {
                        case 'eq':
                        case 'neq':
                        case 'gt':
                        case 'egt':
                        case 'lt':
                        case 'elt':
                        case 'not like':
                        case 'like':
                            $q = $comparison[$value[0]];
                            $var = self::safe($value[1]);
                            $var = "'$var'";
                            $var = "{$q} {$var}";
                            $value = "{$field} {$var}";
                            break;
                        case 'in':
                        case 'notin':
                            $q = $comparison[$value[0]];
                            $var = $value[1];
                            if (!is_array($var)) {
                                $var = explode(',', (string)$var);
                            }
                            $var = implode("','", self::safe($var));
                            $var = "{$q} ('{$var}')";
                            $value = $field.' '.$var;
                            break;
                        case 'between':
                            $q = $comparison[$value[0]];
                            $var = $value[1];
                            if (!is_array($var)) {
                                $var = explode(',', $var);
                            }
                            $var = array_map('intval', $var);
                            $var = "{$q} '{$var[0]}' AND '{$var[1]}'";
                            $value = $field.' '.$var;
                            break;
                        case 'exp':
                            $value = $field.'='.$value[1];
                            break;
                        default:
                            $value = $value[1];
                            break;
                    }
                }
                $count = count($where);
                $where = implode($join, $where);
                $count > 1 && $where = "({$where})";
            }
        }

        return implode($isOr ? ' OR ' : ' AND ', $wheres);
    }

    /**
     * 运行序列化.
     *
     * @param array $q
     *
     * @return string
     */
    private function doSerialize($q)
    {
        if (is_array($q) && !empty($this->option['serialize'])) {
            $serialize = explode(',', $this->option['serialize']);
            // 是否为多维
            if (is_array($q[0])) {
                foreach ($q as &$value) {
                    foreach ($serialize as $ns) {
                        if (!empty($value[$ns])) {
                            $value[$ns] = serialize($value[$ns]);
                        }
                    }
                }
            } else {
                foreach ($serialize as $ns) {
                    if (!empty($q[$ns])) {
                        $q[$ns] = serialize($q[$ns]);
                    }
                }
            }

            return $q;
        } else {
            return $q;
        }
    }

    /**
     * 清空设置.
     */
    private function clearSet(): void
    {
        $this->option = [];
    }

    /**
     * 解序列列化.
     *
     * @param array $q
     *
     * @return array
     */
    private function unSerialize($q)
    {
        if (is_array($q) && !empty($this->option['serialize'])) {
            $serialize = explode(',', $this->option['serialize']);
            // 是否为多维
            if (is_array($q[0])) {
                foreach ($q as &$value) {
                    foreach ($serialize as $ns) {
                        if (!empty($value[$ns])) {
                            $value[$ns] = unserialize($value[$ns]);
                        }
                    }
                }
            } else {
                foreach ($serialize as $ns) {
                    if (!empty($q[$ns])) {
                        $q[$ns] = unserialize($q[$ns]);
                    }
                }
            }

            return $q;
        } else {
            return $q;
        }
    }

    /**
     * 修正表名.
     *
     * @param string $table
     *
     * @return string
     */
    private function tableNameFix($table)
    {
        strpos($table, '__PREFIX__') && $table = str_replace('__PREFIX__', $this->prefix, $table);
        strpos($table, '__TABLE__') && $sql = str_replace('__TABLE__', $this->tableFullName, $table);

        return $table;
    }
}
