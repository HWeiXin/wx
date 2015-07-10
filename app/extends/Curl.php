<?php

/**
 * Curl wrapper for Yii
 * v - 1.2
 * @author hackerone
 */
class Curl {

    private $_ch;
    // config from config.php
    public $options;
    private $_cookieFile;
    // default config
    public $url;
    private $_config = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_AUTOREFERER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    );

    public function setCookieFile($filename,$dir = null)
    {
        if($dir === null){
            $dir = dirname(__FILE__).'/tmp';
        }
        $this->_cookieFile = $dir.'/'.$filename;
    }
    public function getCookieFile()
    {
        if($this->_cookieFile == null){
            $this->_cookieFile = tempnam(dirname(__FILE__).'/tmp','COOKIE_');
        }
        return $this->_cookieFile;
    }
    private function _exec($url) {
        $this->url = $url;
        $this->setOption(CURLOPT_URL, $url);
        $c = curl_exec($this->_ch);
        if (!curl_errno($this->_ch))
            return $c;
        else
            throw new Exception(curl_error($this->_ch));
    }

    public function get($url, $params = array()) {
        $this->setOption(CURLOPT_HTTPGET, true);
        $r =  $this->_exec($this->buildUrl($url, $params));
        $this->close();
        return $this->dealRequestData($r);
    }

    public function post($url, $data = null) {
        if(is_array($data)){
            $data = http_build_query($data);
        }
        $this->setOption(CURLOPT_POSTFIELDS, $data);
        $this->setOption(CURLOPT_POST, true);
        $r =  $this->_exec($url);
        return $this->dealRequestData($r);
    }

    private function dealRequestData($r){
        $res_arr = json_decode($r,true);
        if(!is_array($res_arr)){
            $res_arr = array('errcode'=>-10000,'errmsg'=>'返回的不是JSON字符串');
        }
        if(isset($api_res_arr['errcode']) && $api_res_arr['errcode'] != 0){
            HLog::model()->add('errcode：'.$res_arr['errcode'].' msg：'.$res_arr['errmsg'],'error');
        }
        return $res_arr;
    }

    private static $_curl ;
    public static function instance()
    {
        if(self::$_curl === null){
            self::$_curl = new Curl();
            self::$_curl->init();
        }
        return self::$_curl;

    }

    public function put($url, $data, $params = array()) {

        // write to memory/temp
        $f = fopen('php://temp', 'rw+');
        fwrite($f, $data);
        rewind($f);

        $this->setOption(CURLOPT_PUT, true);
        $this->setOption(CURLOPT_INFILE, $f);
        $this->setOption(CURLOPT_INFILESIZE, strlen($data));

        return $this->_exec($this->buildUrl($url, $params));
    }

    public function buildUrl($url, $data = array()) {
        $parsed = parse_url($url);
        isset($parsed['query']) ? parse_str($parsed['query'], $parsed['query']) : $parsed['query'] = array();
        $params = isset($parsed['query']) ? array_merge($parsed['query'], $data) : $data;
        $parsed['query'] = ($params) ? '?' . http_build_query($params) : '';
        if (!isset($parsed['path']))
            $parsed['path'] = '/';

        $port = '';
        if(isset($parsed['port'])){
            $port = ':' . $parsed['port'];
        }

        return $parsed['scheme'] . '://' . $parsed['host'] .$port. $parsed['path'] . $parsed['query'];
    }

    public function setOptions($options = array()) {
        curl_setopt_array($this->_ch, $options);
        return $this;
    }

    public function setOption($option, $value) {
        curl_setopt($this->_ch, $option, $value);
        return $this;
    }

    // sets header for current request
    public function setHeaders($header)
    {
        if($this->_isAssoc($header)){
            $out = array();
            foreach($header as $k => $v){
                $out[] = $k .': '.$v;
            }
            $header = $out;
        }
        $this->setOption(CURLOPT_HTTPHEADER, $header);
        return $this;
    }


    // initialize curl
    public function init() {
        try {
            $this->_ch = curl_init();
            $options = is_array($this->options) ? ($this->options + $this->_config) : $this->_config;
            $this->setOptions($options);            
             
        } catch (Exception $e) {
            throw new Exception('Curl not installed');
        }
    }

    public function close()
    {
        $ch = $this->_ch;
        curl_close($ch);

    }

}
