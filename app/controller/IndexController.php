<?php

class IndexController extends Controller{

    //微信所有处理的入口
    public function actionIndex(){
        var_dump($_GET);
        var_dump($_POST);
//        $this->render();
    }

}