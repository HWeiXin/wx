<?php

class IndexController extends Controller{

    public function beforeAction(){
        //是否已经登录
        if(HSession::isLogin()){
            $access_token_deadline = HSession::get('access_token_deadline',0);
            if($access_token_deadline > time()){
                return parent::beforeAction();
            }
        }

        //获取code
        $code = $this->getParams('code',false);
        if($code){
            $user_data = WeiXin::model()->getUserWebAccessToken($code);
            $user_data['access_token_deadline'] = $user_data['expires_in'] + time();

//            $user_info = WeiXin::model()->getSnsUserInfo($user_data['access_token'],$user_data['openid']);
//            $user_data['nickname'] = $user_info['nickname'];
//            $user_data['sex'] = $user_info['sex'];
//            $user_data['city'] = $user_info['city'];
//            $user_data['unionid'] = $user_info['unionid'];

            HSession::login($user_data);
            return parent::beforeAction();
        }

        //跳转到微信auth2验证接口
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $auth2url = WeiXin::model()->getAuth2Url($url);
        $this->redirect($auth2url);
    }

    public function actionIndex(){
        $access_token = HSession::get('access_token');
        $this->render(array(
            'access_token' => $access_token
        ));
    }

}