<?php

return array(
    'app_name' => '微信项目测试',//项目名称
    'db' => array(
        'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=zjwdb_293553',//数据库IP和端口(端口可以省略 如果是默认端口的话) 数据库名称
        'username' => 'zjwdb_293553',//用户名
        'password' => '123456zxcZXC',//密码
        'table_prefix' => 'wx_'//表前缀
    ),
    'is_param_route' => true,//是否为参数路由
    'auto_import' => array(
        'models','extends'
    ),
    'is_log' => true,//是否需要记录日志
    'weixin' => array(
        'token' => '123456789',//微信请求我们的验证token
        'app_id' => 'wx2c17b84b7d487b23',
        'app_secret' => 'baf95fcc8461d154294f9fd1e5140746'
    )
);