<?php

class WeixinController extends Controller{

    //微信请求接口的验证
    public function beforeAction(){
        $is_ok = WeiXin::model()->checkSignature();
        if($is_ok){
            WeiXin::model()->initXmlObj();
            return parent::beforeAction();
        }else{
            echo '';
        }
    }

    //微信所有处理的入口
    public function actionIndex(){
        $MsgType = WeiXin::model()->msg_type;
        if($MsgType == 'text'){//文本消息
            WeiXin::model()->dealtext();
        }elseif($MsgType == 'event'){//事件
            WeiXin::model()->dealevent();
        }else{
            echo '';
        }
    }

}