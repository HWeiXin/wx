<?php

/**
 * PDO处理相关
 * Class HPdo
 */
class HPdo {

    const TYPE_ONE = 1;//查询单条
    const TYPE_MORE = 2;//查询多条

    private static $_model = null;
    private static $_db_arr = array();//实例化过的数据库对象
    private $_config = null;
    private $last_data = array(
        'sql' => '',
        'param' => array()
    );

    public function __construct(){
        if($this->_config === null){
            $this->_config = H::getConfig('db');
        }
    }

    /**
     * 实例化HPOD对象
     * @return HPdo
     */
    public static function instance(){
        if(self::$_model === null){
            self::$_model = new HPdo();
        }
        return self::$_model;
    }

    /**
     * PDO连接数据库
     * @param $dsn $dsn mysql:host=127.0.0.1;port=3306;dbname=test 数据库IP和端口(端口可以省略 如果是默认端口的话) 数据库名称
     * @param $username 用户名
     * @param $password 密码
     * @return PDO
     * @throws DBException
     */
    private function connect($dsn,$username,$password){
        if(!isset(self::$_db_arr[$dsn])){
            try{
                $options = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => true,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8',
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                );
                self::$_db_arr[$dsn] = new PDO($dsn,$username,$password,$options);
            }catch (PDOException $e){
                throw new DBException($e->getMessage());
            }
        }
        return self::$_db_arr[$dsn];
    }

    /**
     * 获取数据库连接
     * @return PDO
     */
    public function getConnect(){
        return $this->connect($this->_config['dsn'],$this->_config['username'],$this->_config['password']);
    }

    /**
     * 执行查询SQL语句
     * @param string $sql
     * @param array $params
     * @param int $type
     * @return array|mixed
     * @throws DBException
     */
    public function querySql($sql,$params = array(),$type = self::TYPE_ONE){
        $res_exec = $this->_exec($sql,$params);

        $sth = $res_exec['sth'];

        if($type == self::TYPE_ONE){
            $res = $res_exec['sth']->fetch();
        }else{
            $res = $res_exec['sth']->fetchAll();
        }

        $this->afterQuery($sth);

        return $res;
    }

    /**
     * 执行非查询SQL
     * @param string $sql
     * @param array $params
     * @return array array('id'=>'影响ID','num'=>'条数')
     */
    public function execSql($sql,$params = array()){
        $res_exec = $this->_exec($sql,$params);

        $sth = $res_exec['sth'];

        $res_data = array(
            'status' => $res_exec['status'],
            'num' => 0,
            'id' => false
        );

        if($res_exec['status']){
            $res_data['num'] = $sth->rowCount();
            $res_data['id'] = $res_exec['pdo']->lastInsertId();
        }

        $this->afterQuery($sth);

        return $res_data;
    }

    private function _exec($sql,$params){
        $this->beforeQuery($sql,$params);
        $exec_res = false;
        try{
            $sql = $this->preg_replace_sql($sql);
            $pdo = $this->getConnect();
            $sth = $pdo->prepare($sql);
            $exec_res = $sth->execute($params);
        }catch (PDOException $e){
            throw new DBException($e->getMessage(),array(
                'sql' => $sql,
                'params' => $params
            ));
        }

        $res = array(
            'pdo' => $pdo,
            'sth' => $sth,
            'status' => $exec_res
        );

        return $res;
    }

    /**
     * 执行前
     * @param string $sql
     * @param array $params
     */
    private function beforeQuery($sql,$params){
        $this->last_data = array(
            'sql' => $sql,
            'param' => $params
        );
    }

    /**
     * 执行后
     * @param string $sql
     * @param array $params
     */
    private function afterQuery($sth){
        $sth->closeCursor();//关闭连接
    }

    /**
     * 获取上次执行的 SQL 与 参数
     * @return array
     */
    public function getLastSql(){
        return $this->last_data;
    }

    /**
     * 正则替换sql语句 {{}} 的表前缀
     * @param string $sql
     * @param string $replacement
     * @return string
     */
    private function preg_replace_sql($sql,$replacement = ''){
        if($replacement == ''){
            $replacement = $this->_config['table_prefix'];
        }
        return preg_replace("/{{(.*?)}}/",$replacement."$1",$sql);
    }

} 