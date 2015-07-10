<?php

class Setting extends HModel{

    private static $_access_arr = null;

    /**
     * 获取ACCESS_TOKEN
     * @return array array('access_token'=>'','access_token_deadline'=>'')
     */
    public static function getAccessToken(){
        if(self::$_access_arr === null){
            $setting = self::model()->query(array(
                'condition' => 'type = ?',
                'param' => array('access_token')
            ));
            if($setting['data']){
                self::$_access_arr = json_decode($setting['data'],true);
            }else{
                self::$_access_arr = array(
                    'access_token' => '',
                    'access_token_deadline' => 0
                );
            }
        }
        return self::$_access_arr;
    }

    /**
     * 保存ACCESS_TOKEN
     * @param string $access_token
     * @param int $access_token_deadline
     * @return bool
     */
    public static function saveAccessToken($access_token,$access_token_deadline){
        self::$_access_arr = array(
            'access_token' => $access_token,
            'access_token_deadline' => $access_token_deadline
        );
        return self::model()->update(array(
            'data' => json_encode(self::$_access_arr)
        ),array('condition'=>'type = ?','param'=>array('access_token')));
    }

} 