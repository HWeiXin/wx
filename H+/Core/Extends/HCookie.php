<?php

class HCookie {

    private static $_pre = null;

    /**
     * 获取cookie前缀
     * @param string $key cookie的标示
     * @return string
     */
    private static function getPreKey($key) {
        if (self::$_pre === null) {
            self::$_pre = md5(CC::app()->basePath);
        }
        return self::$_pre .'__'. $key;
    }

    /**
     * 设置cookie
     * @param string $key cookie的标示
     * @param mixed $val
     * @param int $time 默认为一周
     */
    public static function set($key, $val, $time = null) {
        if($time === null){
            $time = time() + 604800;
        }
        setcookie(self::getPreKey($key), serialize($val), $time,CC::app()->baseUrl);
    }

    /**
     * 获取cookie值
     * @param string $key cookie的标示
     * @return mixed
     */
    public static function get($key) {
        return unserialize($_COOKIE[self::getPreKey($key)]);
    }

    /**
     * 删除cookie
     * @param string $key cookie的标示
     */
    public static function delete($key){
        self::set($key,null,time());
    }

    /**
     * 是否设置cookie
     * @param string $key cookie的标示
     * @return bool
     */
    public static function issetKey($key){
        return isset($_COOKIE[self::getPreKey($key)]);
    }

}