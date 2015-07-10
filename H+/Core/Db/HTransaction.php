<?php

class HTransaction {

    private static $_model = null;

    /**
     * @var PDO
     */
    private $_pdo = null;

    public function __construct(){
        if($this->_pdo === null){
            $this->_pdo = HPdo::instance()->getConnect();
        }
    }

    /**
     * 实例化HPOD对象
     * @return HTransaction
     */
    public static function instance(){
        if(self::$_model === null){
            self::$_model = new HTransaction();
        }
        return self::$_model;
    }

    /**
     * 开启事务
     */
    public function beginTransaction(){
        $this->_pdo->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit(){
        return $this->_pdo->commit();
    }

    /**
     * 回滚事务
     */
    public function rollBack(){
        return $this->_pdo->rollBack();
    }

} 