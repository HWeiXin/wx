<?php

//开启session
@session_start();

/**
 * Session处理类
 * Class HSession
 */
class HSession {

    private static $_pre = '_____H';//session前缀
    private static $_login_key = '____LOGIN';

    /**
     * @param array $data_arr 需要存储的数据
     */
    public static function login($data_arr) {
        self::set(self::$_login_key, true);
        foreach ($data_arr as $key => $data) {
            self::set($key, $data);
        }
    }

    /**
     * 退出
     */
    public static function loginOut() {
        self::set(self::$_login_key, null);
    }

    /**
     * 判断是否登陆
     * @return bool
     */
    public static function isLogin() {
        return self::get(self::$_login_key) === true;
    }

    /**
     * 获取值，此获取方法同CSession 的 get方法，不相同
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null) {
        $key = self::getKey(self::$_pre . $key);
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }


    /**
     * 设置值，此获取方法同CSession 的 set方法，不相同
     * @param string $key
     * @param $val
     */
    public static function set($key, $val) {
        $key = self::getKey(self::$_pre.$key);
        $_SESSION[$key] = $val;
    }

    protected static function getKey($key) {
        return md5(H::app()->base_path) . $key;
    }

}