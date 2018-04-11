<?php

namespace Swallow\Toolkit\Util;

/**
 * 树
 *
 * 0是根结点
 */
class Tree
{
    public $data = array();

    public $child = array(- 1 => array());

    public $layer = array(0 => 0);

    public $parent = array();

    public $value_field = '';

    /*********************************初始化start***************************************/
    public function __construct($value = 'root'){
        $this->setNode(0, - 1, $value);
    }
    /**
     * 设置结点
     *
     * @param mix $id
     * @param mix $parent
     * @param mix $value
     */
    public function setNode($id, $parent, $value)
    {
        $parent = $parent ? $parent : 0;
        
        $this->data[$id] = $value;
        if (! isset($this->child[$id])) {
            $this->child[$id] = array();
        }
        if (isset($this->child[$parent])) {
            $this->child[$parent][] = $id;
        } else {
            $this->child[$parent] = array($id);
        }
        
        $this->parent[$id] = $parent;
    }

    /*********************************end******************************************/
    
    /**
     * 构造树
     *
     * 添加多参数，用于返回多个数据
     * example: setTree($nodes, $id_field, $parent_field, $value_field, $value_field1  ...)
     * heui@20120815
     *
     * @param array $nodes 结点数组
     * @param string $id_field
     * @param string $parent_field
     * @param string $value_field
     */
    public function setTree($nodes, $id_field, $parent_field, $value_field)
    {
        $this->value_field = $value_field;
        //多个数据用,,,,进行链接
        $args_num = func_num_args();
        if (4 < $args_num) {
            for ($i = 4; $i < $args_num; $i ++) {
                $this->value_field .= ',,,,' . func_get_arg($i);
            }
        }
        
        foreach ($nodes as $node) {
            self::setNode($node[$id_field], $node[$parent_field], $node);
        }
        self::setLayer();
    }
    
    /**
     * 计算layer
     */
    public function setLayer($root = 0)
    {
        foreach ($this->child[$root] as $id) {
            $this->layer[$id] = $this->layer[$this->parent[$id]] + 1;
            if ($this->child[$id]) {
                self::setLayer($id);
            }
        }
    }

    public function getValue($id)
    {
        $arr_value = explode(",,,,", $this->value_field);
        $arr_num = count($arr_value);
        if (1 < $arr_num) {
            $res = array();
            $res[$arr_value[0]] = $this->data[$id][$arr_value[0]];
            for ($i = 1; $i < $arr_num; $i ++) {
                if ($this->data[$id][$arr_value[$i]]) {
                    $res[$arr_value[$i]] = $this->data[$id][$arr_value[$i]];
                }
            }
            return $res;
        } else {
            return $this->data[$id][$this->value_field];
        }
    }

    /**
     * 先根遍历，数组格式
     * array(
     *     array('id' => '', 'value' => '', children => array(
     *         array('id' => '', 'value' => '', children => array()),
     *     ))
     * )
     */
    public function getArrayList($root = 0, $layer = NULL)
    {
        $data = array();
        foreach ($this->child[$root] as $id) {
            if ($layer && $this->layer[$this->parent[$id]] > $layer - 1) {
                continue;
            }
            $data[] = array(
                'id' => $id, 
                'value' => self::getValue($id), 
                'children' => $this->child[$id] ? self::getArrayList($id, $layer) : array());
        }
        return $data;
    }

}

?>