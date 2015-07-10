<?php

/**
 * 日志类
 * Class HLog
 */
class HLog {

    const LEVEL_DEBUG = 'debug';//调试类
    const LEVEL_ERROR = 'error';//错误类(普通错误 致命错误 异常)
    const LEVEL_SQL = 'sql';//SQL错误类

    private $_log_arr = array();

    private $_file = array(
        'error' => 'app',
        'debug' => 'debug',
        'sql' => 'sql',
    );

    private $_max_size = 5120; //单个文件大小5M

    private static $_model = null;

    public static function model(){
        if(self::$_model === null){
            self::$_model = new HLog();
        }
        return self::$_model;
    }

    /**
     * 添加一个日志到缓存变量
     * @param string $msg 日志消息
     * @param string $level 日志等级类型
     * @param string $file_name 日志文件名称
     */
    public function add($msg,$level,$file_name = '') {
        $this->_log_arr[$level][] = array(
            'msg' => $msg,
            'level' => $level,
            'time' => time(),
            'file_name' => $file_name
        );
    }

    /**
     * 获取日志缓存数据
     * @return array $content_arr
     */
    protected function getSaveContent() {
        $content_arr = array();
        foreach ($this->_log_arr as $level => $log_arr) {
            foreach($log_arr as $v){
                $log_str = "---------------------------\n";
                $log_str .= "[$level] ".date('Y-m-d H:i:s',$v['time'])."\n";
                $log_str .= $v['msg']."\n";
                $log_str .= "REQUEST_URI = ".isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:"\n";
                $log_str .= "POST_DATA: ".var_export($_POST,true)."\n";

                if($v['file_name'] == ''){
                    $v['file_name'] = isset($this->_file[$level])?$this->_file[$level]:$level;
                }

                $content_arr[$v['file_name']][] = $log_str;
            }
        }
        return $content_arr;
    }

    /**
     * 保存日志
     * @return bool
     */
    public function save(){
        $content_arr = $this->getSaveContent();
        if(empty($content_arr)){
            return false;
        }
        $suffix = '.log';

        $path = H::app()->log_path.'/'.date('Y/m/d');

        if($this->makeDir($path)){
            foreach($content_arr as $file_name => $log_arr){
                $file_arr = glob($path.'/'.$file_name.'*.log');
                $num = count($file_arr);
                if($num > 0){
                    $file_path = $file_arr[$num-1];
                    $file_size = filesize($file_path)/1024;
                    if($file_size >= $this->_max_size){
                        $num++;
                    }
                }else{
                    $num++;
                }
                $file_path = $path.'/'.$file_name.'_'.$num.$suffix;
                //写入方式打开，将文件指针指向文件末尾。如果文件不存在则尝试创建
                $handle = fopen($file_path,'a');
                if($handle){
                    foreach($log_arr as $content){
                        fwrite($handle,$content);
                    }
                    fclose($handle);
                }
            }
        }

        $this->_log_arr = array();
        return true;
    }

    /**
     * 创建目录
     * @param string $dir 目录字符串
     * @return bool
     */
    private function makeDir($dir) {
        if (!is_dir($dir)) {
            if($this->makeDir(dirname($dir))) {
                return mkdir($dir);
            }
            return false;
        }
        return true;
    }

}