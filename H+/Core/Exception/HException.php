<?php

class HException extends Exception{

    public $data = array();//其它数据

    public function __construct($message = '',$code = 10000,$data = array()) {
        $this->data = $data;
        parent::__construct($message,$code);
    }

}