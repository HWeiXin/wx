<?php

class DBException extends HException{

    public function __construct($message = '',$data = array()) {
        parent::__construct($message,500,$data);
    }

}