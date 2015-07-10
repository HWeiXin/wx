<?php

class WeiXin {

    private $_api_url = 'https://api.weixin.qq.com/cgi-bin';

    private $_token = '';//微信请求我们的验证token
    private $_app_id = '';
    private $_app_secret = '';
    private $_access_token = null;
    private $_access_token_deadline = 0;

    private static $_model = null;

    private $_xml_obj = null;
    public $toUserName = '';//微信 请求 我们 这个是 我们的openID
    public $fromUserName = '';//这个是 请求我们 的 那个用户的openID
    public $msg_type = '';//请求的消息类型

    public static function model(){
        if(self::$_model === null){
            self::$_model = new WeiXin();
            $config_wx = H::app()->getConfig('weixin');
            self::$_model->_token = $config_wx['token'];
            self::$_model->_app_id = $config_wx['app_id'];
            self::$_model->_app_secret = $config_wx['app_secret'];
        }
        return self::$_model;
    }

    /**
     * 微信请求验证
     * @return bool
     */
    public function checkSignature() {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $tmpArr = array($this->_token,$timestamp,$nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    //微信第一次验证时候调用的方法
    public function firstTimeValid() {
        if(isset($_GET["echostr"])){
            echo $_GET["echostr"];
            H::app()->end();
        }
    }

    /**
     * 初始化XML对象
     */
    public function initXmlObj(){
        $this->firstTimeValid();
        //获取POST的XML数据
        $xml_str = $GLOBALS["HTTP_RAW_POST_DATA"];
        if(!empty($xml_str)){
            $this->_xml_obj = simplexml_load_string($xml_str);
            $this->toUserName = $this->getXmlNodeToString('ToUserName');
            $this->fromUserName = $this->getXmlNodeToString('FromUserName');
            $this->msg_type = $this->getXmlNodeToString('MsgType');
        }
    }

    /**
     * 获取XML对象的某个节点字符串
     * @param string $node_key 节点KEY
     * @return string
     */
    public function getXmlNodeToString($node_key){
        return isset($this->_xml_obj->$node_key)?(string)$this->_xml_obj->$node_key:'';
    }

    //处理文本消息
    public function dealtext(){
        $keyword = trim($this->getXmlNodeToString('Content'));
        if($keyword == 'test'){
            $this->replyMsgText('auto reply : '.date('Y-m-d H:i:s'));
        }elseif($keyword == 'token'){
            $access_token = $this->getAccessToken();
            $this->replyMsgText('access_token：'.($access_token?$access_token:'未获取到'));
        }elseif($keyword == 'info'){
            $user_info = $this->getUserInfo($this->fromUserName);
            $this->replyMsgText(json_encode($user_info));
        }elseif($keyword == 'menu'){
            $menu = array(
                'button' => array(
                    array(
                        'name' => urlencode('测试菜单'),
                        'sub_button' => array(
                            array(
                                'type' => 'view',
                                'name' => urlencode('首页'),
                                'url' => $this->getAuth2Url('http://wx.ihermit.cn?a=index_index')
                            )
                        )
                    )
                )
            );
            $res = $this->creatMenu($menu);
            $this->replyMsgText(json_encode($res));
        }elseif($keyword == 'delmenu'){
            $res = $this->delMenu();
            $this->replyMsgText(json_encode($res));
        }
        echo '';
    }

    /**
     * 获取 Auth2 Url
     * @param string $url 跳转的url
     * @return string
     */
    private function getAuth2Url($url){
        $url = urlencode($url);
        return 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->_app_id.'&redirect_uri='.$url.'&response_type=code&scope=snsapi_base&state=wx#wechat_redirect';
    }

    //处理事件
    public function dealevent(){
        $event = $this->getXmlNodeToString('Event');
        if($event == 'subscribe'){//订阅
            $user_info = $this->getUserInfo($this->fromUserName);
            $this->replyMsgText('感谢来自'.$user_info['city'].'的:'.$user_info['nickname'].' 的订阅');
        }elseif($event == 'unsubscribe'){//取消订阅

        }
        echo '';
    }

    /**
     * 回复文本消息
     * @param string $msg
     */
    public function replyMsgText($msg){
        $fromUserName = $this->_xml_obj->FromUserName;
        $toUserName = $this->_xml_obj->ToUserName;
        $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                    </xml>";
        $resultStr = sprintf($textTpl,$fromUserName,$toUserName,time(),'text',$msg);
        echo $resultStr;
        H::app()->end();
    }

    /**
     * 获取access_token
     * @return string
     */
    public function getAccessToken(){
        $now_time = time();
        if($this->_access_token === null){
            //从数据库里查询
            $access_arr = Setting::getAccessToken();
            $this->_access_token = $access_arr['access_token'];
            $this->_access_token_deadline = $access_arr['access_token_deadline'];
        }

        //是否过期
        if($now_time >= $this->_access_token_deadline){
            $url = $this->_api_url.'/token';
            $api_res_arr = Curl::instance()->get($url,array(
                'grant_type' => 'client_credential',
                'appid' => $this->_app_id,
                'secret' => $this->_app_secret
            ));
            if($api_res_arr){
                $this->_access_token = isset($api_res_arr['access_token'])?$api_res_arr['access_token']:'';
                $this->_access_token_deadline = isset($api_res_arr['expires_in'])?($now_time + $api_res_arr['expires_in']):0;//有效时间
                //保存到数据库
                Setting::saveAccessToken($this->_access_token,$this->_access_token_deadline);
            }else{
                $this->_access_token = '';
            }
        }

        return $this->_access_token;
    }

    /**
     * 获取用户信息
     * @param string $openid 微信的openID
     * @return mixed 用户信息数组 或 false
     */
    public function getUserInfo($openid){
        $url = $this->getApiUrl('/user/info');
        return Curl::instance()->get($url,array(
            'openid' => $openid,
            'lang' => 'zh_CN'
        ));
    }

    /**
     * 获取带access_token的api_url
     * @param string $str 请求的参数
     * @return string
     */
    private function getApiUrl($str){
        return $this->_api_url.$str.'?access_token='.$this->getAccessToken();
    }

    /**
     * 创建自定义菜单
     * @param array $menu 菜单数组 参考微信手册
     * //所有名称都要 urlencode
     *             $menu = array(
     *               'button' => array(
     *                   array(
     *                       'type' => 'click',
     *                       'name' => '菜单一',
     *                       'key' => 'menu-1'
     *                   ),
     *                   array(
     *                       'name' => '菜单二',
     *                       'sub_button' => array(
     *                           array(
     *                               'type' => 'view',
     *                               'name' => '百度',
     *                               'url' => 'http://www.baidu.com'
     *                           ),
     *                           array(
     *                               'type' => 'view',
     *                               'name' => '淘宝',
     *                               'url' => 'http://www.taobao.com'
     *                           ),
     *                           array(
     *                               'type' => 'view',
     *                               'name' => 'Hermit',
     *                               'url' => 'http://www.bdlong.cn'
     *                           )
     *                       )
     *                   )
     *               )
     *           );
     * @return array
     */
    public function creatMenu($menu){
        $url = $this->getApiUrl('/menu/create');
        $json_data = urldecode(json_encode($menu));
        HLog::model()->add($json_data,'debug');
        return Curl::instance()->post($url,$json_data);
    }

    /**
     * 删除自定义菜单
     * @return array
     */
    public function delMenu(){
        $url = $this->getApiUrl('/menu/delete');
        return Curl::instance()->get($url);
    }

    /**
     * 获取POST请求参数
     * @param array $param
     * @return array
     */
    private function postParam($param = array()){
        return $param;
    }

} 