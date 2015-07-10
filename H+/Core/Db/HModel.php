<?php

/**
 * 所有模型的基类
 * Class HModel
 */
class HModel {

    private static $_model = array();
    private $_table = null;
    private $_join = '';
    private $_group = '';
    private $_having = '';

    /**
     * 实例化模型
     * @return $this
     */
    public static function model(){
        $class = get_called_class();
        if(!isset(self::$_model[$class])){
            self::$_model[$class] = new $class;
        }
        return self::$_model[$class];
    }

    /**
     * 获取当前模型对应的表名
     * @param string $table
     * @return null|string
     */
    public function getTable($table = ''){
        if($this->_table === null){
            $config = H::getConfig('db');
            if($table == ''){
                $class = get_called_class();
                $class_arr = array_filter(preg_split("/(?=[A-Z])/",$class));
                $table = implode('_',$class_arr);
                $table = $config['table_prefix'].strtolower($table);
            }
            if($this->_join){
                $table .= ' as t';
            }
            $this->_table = $table;
        }
        return $this->_table;
    }

    public function join($join){
        $this->_join = ' '.$join;
        return $this;
    }

    public function group($group){
        $this->_group = ' GROUP BY '.$group;
        return $this;
    }

    public function having($having){
        $this->_having = ' HAVING '.$having;
        return $this;
    }

    /**
     * 查询单条数据
     * @param array $option
     *              array(
     *                  'condition' => 'id = ?',
     *                  'param' => array(),
     *                  'field' => '*',
     *                  'order' => 'id DESC'
     *              )
     * @return array
     */
    public function query($option = array()){
        $sql = $this->getQuerySql($option);

        $sql .= ' LIMIT 1';

        $param = isset($option['param'])?$option['param']:array();

        $query_res = HPdo::instance()->querySql($sql,$param,HPdo::TYPE_ONE);

        if($query_res == false){
            return array();
        }else{
            return $query_res;
        }
    }

    /**
     * 查询多条数据
     * @param array $option
     *              array(
     *                  'condition' => 'id = ?',
     *                  'param' => array(),
     *                  'field' => '*',
     *                  'order' => 'id DESC'
     *              )
     * @return array
     */
    public function queryAll($option = array()){
        $sql = $this->getQuerySql($option);

        $param = isset($option['param'])?$option['param']:array();

        return HPdo::instance()->querySql($sql,$param,HPdo::TYPE_MORE);
    }

    /**
     * 获取查询sql
     * @param array $option
     * @return string
     */
    private function getQuerySql($option){
        $table = $this->getTable();
        $field = isset($option['field'])?$option['field']:'*';

        $sql = 'SELECT '.$field.' FROM '.$table.$this->_join;

        if(isset($option['condition'])){
            $sql .= ' WHERE '.$option['condition'];
        }

        $sql .= $this->_group;

        if(isset($option['order'])){
            $sql .= ' ORDER BY '.$option['order'];
        }

        if(isset($option['limit'])){
            $sql .= ' LIMIT '.$option['limit'];
        }

        $sql .= $this->_having;

        return $sql;
    }

    /**
     * 统计数量
     * @param string $count_str 统计条件字符串
     * @param array $option
     * @return mixed
     */
    public function count($count_str,$option = array()){
        $option['field'] = 'count('.$count_str.') as count_num';
        $sql = $this->getQuerySql($option);

        $param = isset($option['param'])?$option['param']:array();

        $query_res = HPdo::instance()->querySql($sql,$param);

        return $query_res['count_num'];
    }

    /**
     * 插入单条数据
     * @param array $data 插入的数据数组
     *              array(
     *                  'name' => '你好',
     *                  'account' => 'test'
     *              )
     * @param string $table
     * @return mixed 失败返回false 成功返回插入的ID
     */
    public function insert($data,$table = ''){
        $table_str = $this->getTable($table);

        $field_arr = array();
        $value_arr = array();
        $param = array();
        foreach($data as $key => $value){
            $field_arr[] = $key;
            $value_arr[] = '?';
            $param[] = $value;
        }

        $sql = 'INSERT INTO '.$table_str.' ('.implode(',',$field_arr).') VALUES ('.implode(',',$value_arr).')';

        $res = HPdo::instance()->execSql($sql,$param);
        return $res['id'];
    }

    /**
     * 插入多条数据
     * @param array $data 插入的数据数组
     *              array(
     *                  array('name' => '你好','account' => 'test'),
     *                  array('name' => '你好','account' => 'test')
     *              )
     * @param string $table
     * @return mixed 失败返回false 成功返回插入的ID
     */
    public function insertAll($data_arr,$table = ''){
        $table_str = $this->getTable($table);

        $field_arr = array();
        $value_arr = array();
        $param = array();
        foreach($data_arr as $k => $data){
            $temp_arr = array();
            foreach($data as $key => $value){
                if($k == 0){
                    $field_arr[] = $key;
                }
                $temp_arr[] = '?';
                $param[] = $value;
            }
            $value_arr[] = '('.implode(',',$temp_arr).')';
        }

        $sql = 'INSERT INTO '.$table_str.' ('.implode(',',$field_arr).') VALUES '.implode(',',$value_arr);

        $res = HPdo::instance()->execSql($sql,$param);
        return $res['num'];
    }

    /**
     * 更新数据
     * @param array $data_arr 更新的数据数组
     *              array(
     *                  'name' => '你好',
     *                  'account' => 'test'
     *              )
     * @param array $option 参数
     * @return bool
     */
    public function update($data_arr,$option = array()){
        $set_arr = array();
        $param = array();
        foreach($data_arr as $key => $v){
            $set_arr[] = $key.' = ?';
            $param[] = $v;
        }

        $table = isset($option['table'])?$option['table']:'';

        $table_str = $this->getTable($table);

        $sql = 'UPDATE '.$table_str.' SET '.implode(',',$set_arr);

        if(isset($option['condition'])){
            $sql .= ' WHERE '.$option['condition'];
        }

        if(isset($option['param'])){
            $param = array_merge($param,$option['param']);
        }

        $res = HPdo::instance()->execSql($sql,$param);
        return $res['status'];
    }

    /**
     * 删除数据
     * @param array $option 参数
     * @return bool
     */
    public function delete($option = array()){
        $table = isset($option['table'])?$option['table']:'';

        $table_str = $this->getTable($table);

        $sql = 'DELETE FROM '.$table_str;

        if(isset($option['condition'])){
            $sql .= ' WHERE '.$option['condition'];
        }

        $param = isset($option['param'])?$option['param']:array();

        $res = HPdo::instance()->execSql($sql,$param);
        return $res['status'];
    }

    /**
     * 获取上次执行的 SQL 与 参数
     * @return array
     */
    public function getLastSql(){
        return HPdo::instance()->getLastSql();
    }

    /**
     * 开启事务
     * @return HTransaction
     */
    public static function beginTransaction(){
        $transaction = HTransaction::instance();
        $transaction->beginTransaction();
        return $transaction;
    }

} 