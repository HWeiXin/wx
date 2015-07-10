<?php

/**
 * 基类控制器所有都必须继承于它
 * Class Controller
 */
class Controller {

    public $controller = '';//当前控制器

    public $action = '';//当前方法

    public $layout = 'default';

    //方法执行前
    public function beforeAction(){
        return true;
    }

    //方法执行后
    public function afterAction(){

    }

    public function echoOk($option = array()){
        $res_data = array('ok'=>true);
        if(isset($option['data'])){
            $res_data += $option['data'];
        }
        if(isset($option['transaction'])){
            $option['transaction']->commit();
        }
        echo json_encode($res_data);
        H::app()->end();
    }

    public function echoErr($msg,$option = array()){
        $res_data = array('ok'=>false,'msg'=>$msg);
        if(isset($option['data'])){
            $res_data += $option['data'];
        }
        if(isset($option['transaction'])){
            $option['transaction']->rollBack();
        }
        echo json_encode($res_data);
        H::app()->end();
    }

    /**
     * 渲染视图
     * @param array $data 传递的参数数组
     * @param bool $is_layout 是否使用布局文件
     * @throws HTTPException
     */
    public function render($data = array(),$is_layout = true){
        $view_tmep_name = $this->controller.'/'.$this->action.'.php';
        $view_path = H::app()->view_path.'/'.$view_tmep_name;
        //视图文件是否存在
        if(!file_exists($view_path)){
            throw new HTTPException(H::app()->getConfig('view_file_name').'/'.$view_tmep_name.' not found');
        }

        $H_VIEW_HTML = $this->getFileHtml($view_path,$data);

        if($is_layout){
            $layout_path = H::app()->view_path.'/layout/'.$this->layout.'.php';
            //布局文件是否存在
            if(!file_exists($layout_path)){
                throw new HTTPException(H::app()->getConfig('view_file_name').'/layout/'.$this->layout.'.php not found');
            }
            $H_LAYOUT_HTML = $this->getFileHtml($layout_path,array('H_VIEW_HTML' => $H_VIEW_HTML));
            echo $H_LAYOUT_HTML;
            unset($H_VIEW_HTML);
        }else{
            echo $H_VIEW_HTML;
        }
    }

    /**
     * 获取对应视图文件
     * @param string $path 视图对应的路径
     * @param array $h____data 传递的参数
     * @return string
     */
    private function getFileHtml($path,$h____data = array()){
        ob_start();
        extract($h____data);
        include($path);
        $H_FILE_HTML = ob_get_contents();
        ob_end_clean();
        return $H_FILE_HTML;
    }

    /**
     * 输出错误页面
     * @param string $msg 错误信息
     * @param string $file 文件路径
     * @param int $line 文件行数
     * @param array $data 其它数据
     */
    public static function renderErr($msg,$file,$line,$data = array()){
        include(H::app()->h_base_path.'/Core/View/error.php');
    }

    /**
     * 获取GET或POST
     * @param string $key
     * @param bool $isneed 是否必须
     * @param null $default 默认值
     * @return null|string
     * @throws HTTPException
     */
    public function getParams($key,$isneed = true,$default = null){
        $val = $default;
        if(isset($_GET[$key])){
            $val = trim($_GET[$key]);
        }elseif(isset($_POST[$key])){
            $val = trim($_POST[$key]);
        }elseif($isneed){
            throw new HTTPException('param '.$key.' is must need.');
        }
        return $val;
    }

    /**
     * 生成url
     * @param string $str 生成字符串
     *                    /test       test控制器 默认方法
     *                    /test/show  test控制器 show方法
     *                    test        当前控制器 test方法
     *                    test/show   test控制器 show方法
     * @param array $param 需要生成URL的参数数组
     * @return string
     */
    public function genurl($str,$param = array()){
        $controller = $this->controller;

        $char = substr($str,0,1);
        if($char == '/'){
            $action = substr($str,1);
        }else{
            $arr = explode('/',$str);
            if(count($arr) > 1){
                $controller = isset($arr[0])?$arr[0]:'';
                $action = isset($arr[1])?$arr[1]:'';
            }else{
                $action = isset($arr[0])?$arr[0]:'';
            }
        }

        $param_arr = array();//参数数组

        //是否为参数路由类型
        if(H::app()->getConfig('is_param_route')){
            $param_route_key = H::app()->getConfig('param_route_key');
            $param_route_separator = H::app()->getConfig('param_route_separator');
            $url = H::app()->base_url.'/'.H_APP_ENTRY_FILE;
            $param_arr[] = $param_route_key.'='.$controller.$param_route_separator.$action;
        }else{
            $url = H::app()->base_url.'/'.$controller.'/'.$action;
        }

        //处理参数
        foreach($param as $key => $val){
            $param_arr[] = $key.'='.$val;
        }
        if(!empty($param_arr)){
            $url .= '?'.implode('&',$param_arr);
        }

        return $url;
    }

}