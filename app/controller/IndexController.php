<?php

class IndexController extends Controller{

    public function actionIndex(){
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        $auth_url = WeiXin::model()->getAuth2Url($url);

        $this->redirect($auth_url);
    }

    public function actionTest(){
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        p($url);
        var_dump($_GET);
        var_dump($_POST);
    }

}