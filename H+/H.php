<?php

class H{

    private static $_h_app = null;//APP缓存变量

    public $h_base_path = '';//框架目录

    public $base_path = '';//项目根路径
    public $app_path = '';//APP根路径
    public $base_url = '';//项目url
    public $public_url = '';//公共目录url
    public $file_url = '';//files目录url
    public $log_path = '';//日志路径
    public $view_path = '';//视图路径

    private $controller_path = '';//控制器路径

    //配置项
    private $_h_config = array(
        /*--项目相关开始--*/
        'app_name' => '',//项目名称
        /*--项目相关结束--*/

        /*--数据库相关开始--*/
        'db' => array(
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=test',//数据库IP和端口(端口可以省略 如果是默认端口的话) 数据库名称
            'username' => 'root',//用户名
            'password' => '123456',//密码
            'table_prefix' => 't_'//表前缀
        ),
        /*--数据库相关结束--*/

        /*--框架目录结构相关开始--*/
        'app_file_name' => 'app',//APP文件夹名称
        'log_file_name' => 'log',//日志文件夹名称
        'controller_file_name' => 'controller',//控制器文件夹名称
        'view_file_name' => 'view',//视图文件夹名称
        /*--框架目录结构相关结束--*/

        /*--路由相关开始--*/
        'is_param_route' => false,//是否为参数路由
        'param_route_key' => 'a',//参数路由的获取关键字
        'param_route_separator' => '_',//参数路由的分割符
        'controller' => 'index',//默认控制器
        'action' => 'index',//默认方法
        /*--路由相关结束--*/

        /*--自动加载相关开始--*/
        'auto_import' => array(
            'models'
        ),
        /*--自动加载相关结束--*/

        /*--日志相关开始--*/
        'is_log' => true,//是否需要记录日志
        /*--日志相关结束--*/
    );

    function __construct(){
        $this->initSystemSetting();
        $this->initConfigSetting();
        $this->initCore();
        $this->initCommon();
    }

    //初始化系统设置
    private function initSystemSetting(){
        //入口文件
        defined('H_APP_ENTRY_FILE') or define('H_APP_ENTRY_FILE','index.php');

        //设置时区
        date_default_timezone_set('Asia/Shanghai');
        //设置编码
        header('Content-type: text/html; charset=utf-8');

        //注册自动加载
        spl_autoload_register(array($this,'loader'));

        set_exception_handler(array($this,'handleException'));//异常回调
        set_error_handler(array($this,'handleError'),error_reporting());//错误回调
        register_shutdown_function(array($this,'handleFatalError'));//致命错误回调
    }

    //初始化路径相关
    private function initConfigSetting(){
        //框架根目录
        $this->h_base_path = dirname(__FILE__);
        //项目根路径
        $this->base_path = H_APP_PATH;
        //配置项初始化
        $user_config = include $this->base_path.'/config/conf.php';
        $this->_h_config = array_merge($this->_h_config,$user_config);
        unset($user_config);
        //其它相关路径
        $this->app_path = $this->base_path.'/'.$this->_h_config['app_file_name'];
        $this->base_url = dirname($_SERVER['SCRIPT_NAME']);
        if($this->base_url == DIRECTORY_SEPARATOR){
            $this->base_url = '';
        }
        $this->public_url = $this->base_url.'/public';
        $this->file_url = $this->base_url.'/files';
        $this->log_path = $this->app_path.'/'.$this->_h_config['log_file_name'];
        $this->controller_path = $this->app_path.'/'.$this->_h_config['controller_file_name'];
        $this->view_path = $this->app_path.'/'.$this->_h_config['view_file_name'];
    }

    //核心自动加载列表
    private $core_auto_import = array(
        'HModel' => 'Db/HModel.php',
        'HPdo' => 'Db/HPdo.php',
        'HTransaction' => 'Db/HTransaction.php',
        'DBException' => 'Exception/DBException.php',
        'HException' => 'Exception/HException.php',
        'HTTPException' => 'Exception/HTTPException.php',
        'HLog' => 'Log/HLog.php',
        'HSession' => 'Extends/HSession.php',
        'HCookie' => 'Extends/HCookie.php'
    );

    //初始化核心代码
    private function initCore(){
        //基类控制器
        include $this->h_base_path.'/Core/Controller/Controller.php';
    }

    //引用框架外常用方法
    private function initCommon(){
        include $this->h_base_path.'/Common/Function.php';
    }

    /**
     * 自动加载
     * @param string $class_name 类名
     */
    private function loader($class_name){
        if(isset($this->core_auto_import[$class_name])){//核心列表
            include $this->h_base_path.'/Core/'.$this->core_auto_import[$class_name];
        }else{//文件夹
            foreach($this->_h_config['auto_import'] as $file_name){
                $path = $this->app_path.'/'.$file_name.'/'.$class_name.'.php';
                if(file_exists($path)){
                    include $path;
                }
            }
        }
    }

    /**
     * 应用初始化
     * @return H
     */
    public static function app(){
        if(self::$_h_app === null){
            self::$_h_app = new H();
        }
        return self::$_h_app;
    }

    /**
     * 获取APP配置项
     * @param string $key 配置项的KEY
     * @return mixed
     */
    public static function getConfig($key){
        return isset(self::$_h_app->_h_config[$key])?self::$_h_app->_h_config[$key]:'';
    }

    //运行程序
    public function run(){
        $route_arr = array();
        //是否为参数路由
        if($this->_h_config['is_param_route']){
            $route_str = isset($_GET[$this->_h_config['param_route_key']])?$_GET[$this->_h_config['param_route_key']]:'';
            if($route_str != ''){
                $route_arr = explode($this->_h_config['param_route_separator'],$route_str);
            }
        }else{
            $uri = $_SERVER['REQUEST_URI'];
            $uri_end = strpos($uri,'?');
            if($uri_end !== false){
                $uri = substr($uri,0,$uri_end);
            }
            if(strpos($uri,$_SERVER['SCRIPT_NAME']) === false){
                $dir_name = $this->base_url;
            }else{
                $dir_name = $_SERVER['SCRIPT_NAME'];
            }
            $route_str = substr($uri,strlen($dir_name)+1);

            if($route_str != false){
                $route_arr = explode('/',$route_str);
            }
        }
        //控制器
        $controller = (isset($route_arr[0]) && $route_arr[0])?$route_arr[0]:$this->_h_config['controller'];
        //方法
        $action = (isset($route_arr[1]) && $route_arr[1])?$route_arr[1]:$this->_h_config['action'];

        $this->runController($controller,$action);

        $this->end();
    }

    /**
     * 运行控制器里面的对应方法
     * @param string $controller 控制器
     * @param string $action 方法
     * @throws HTTPException
     */
    public function runController($controller,$action){
        //控制器类名 首字母被转换为大写字符
        $controllerClass = ucfirst($controller).'Controller';
        $path = $this->app_path.'/'.$this->_h_config['controller_file_name'].'/'.$controllerClass.'.php';

        //文件是否存在
        if(!file_exists($path)){
            throw new HTTPException($controllerClass.'.php not found');
        }
        include $path;

        $controller_obj = new $controllerClass;
        $controller_obj->controller = lcfirst($controller);
        $controller_obj->action = lcfirst($action);

        //是否继承于核心控制器类
        if(!($controller_obj instanceof Controller)){
            throw new HTTPException($controllerClass.'php is must extends Controller');
        }

        //方法名
        $action_method = 'action'.ucfirst($action);

        //方法是否存在
        if(!method_exists($controller_obj,$action_method)){
            throw new HTTPException('method '.$action_method.' not found');
        }

        //方法必须为public
        if(!is_callable(array($controller_obj,$action_method))){
            throw new HTTPException('method '.$action_method.' is must a public method');
        }

        if($controller_obj->beforeAction()){
            $controller_obj->$action_method();
            $controller_obj->afterAction();
        }
    }

    //APP结束
    public function end(){
        if($this->_h_config['is_log']){
            HLog::model()->save();
        }
        exit;
    }

    /**
     * 异常处理
     * @param HException $exception
     */
    public function handleException($exception){
        $code = $exception->getCode();
        //服务器错误
        if($code == 500){
            Controller::renderErr($exception->getMessage(),$exception->getFile(),$exception->getLine(),$exception->data);
        }elseif($this->_h_config['is_log']){
            $log = 'Exception Code['.$code.'] Msg['.$exception->getMessage().'] '.$exception->getFile().' on line '.$exception->getLine();
            HLog::model()->add($log,HLog::LEVEL_ERROR);
            HLog::model()->save();
        }
    }

    /**
     * 错误处理
     * @param int $code 错误码
     * @param string $message 错误消息
     * @param string $file 错误文件
     * @param int $line 错误行
     */
    public function handleError($code,$message,$file,$line){
        if($this->_h_config['is_log']){
            //$trace = debug_backtrace();//需要时候再用
            $log = 'Error Code['.$code.'] Msg['.$message.'] '.$file.' on line '.$line;
            HLog::model()->add($log,HLog::LEVEL_ERROR);
            Controller::renderErr($message,$file,$line);
            H::app()->end();
        }
    }

    /**
     * 致命错误处理
     */
    public function handleFatalError(){
        if($this->_h_config['is_log']){
            $error = error_get_last();
            if($error){
                $log = 'FatalError Type['.$error['type'].'] Msg['.$error['message'].'] '.$error['file'].' on line '.$error['line'];
                HLog::model()->add($log,HLog::LEVEL_ERROR);
                HLog::model()->save();
            }
        }
    }

}