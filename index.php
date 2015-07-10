<?php
//项目目录
defined('H_APP_PATH') or define('H_APP_PATH',dirname(__FILE__));
//入口文件
defined('H_APP_ENTRY_FILE') or define('H_APP_ENTRY_FILE',basename(__FILE__));

//引用H+
include H_APP_PATH.'/H+/H.php';

H::app()->run();