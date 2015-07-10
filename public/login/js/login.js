$.app = {
    url: '',
    msg_e: null,
    getUrl: function(){
        if(this.url == ''){
            this.url = this.getRootPath();
        }
        return this.url;
    },
    getRootPath: function(){
        if(typeof(window.base_url) == 'undefined'){
            //获取当前网址，如： http://localhost:8083/uimcardprj/share/meun.jsp
            var curWwwPath = window.document.location.href;
            //获取主机地址之后的目录，如： uimcardprj/share/meun.jsp
            var pathName = window.document.location.pathname;
            var pos = curWwwPath.indexOf(pathName);
            //获取主机地址，如： http://localhost:8083
            var localhostPaht = curWwwPath.substring(0,pos);
            //获取带"/"的项目名，如：/uimcardprj
            var projectName = pathName.substring(0,pathName.substr(1).indexOf('/')+1);
            window.base_url = localhostPaht + projectName;
        }
        return window.base_url;
    },
    showErrMsg: function(msg,add_class_e){
        if(this.msg_e == null){
            this.msg_e = $('#err_msg');
        }
        if(add_class_e){
            add_class_e.addClass('error');
        }
        this.msg_e.html(msg);
        this.msg_e.show();
    },
    hideErrMsg: function(){
        if(this.msg_e == null){
            this.msg_e = $('#err_msg');
        }
        $('#user_account').removeClass('error');
        $('#user_pwd').removeClass('error');
        this.msg_e.html('');
        this.msg_e.hide();
    },
    login: function(post_data){
        var btn_login_e = $('#btn_login');
        var interval = null;
        $.ajax({
            url: $.app.getUrl()+'/Login/Login',
            data: this.addRequestSign(post_data),
            type: 'post',
            dataType: 'json',
            beforeSend: function(){
                btn_login_e.html('登录中');
                var text = '';
                interval = setInterval(function(){
                    if(text == '···'){
                        text = '';
                    }else{
                        text += '·';
                    }
                    btn_login_e.html('登录中'+text);
                },1000);
            },
            success: function (res_data) {
                clearInterval(interval);
                if(res_data['ok']){
                    window.location.href = res_data['url'];
                }else{
                    btn_login_e.html('登录');
                    $.app.showErrMsg(res_data['msg']);
                }
            },
            error: function (res_data) {
                clearInterval(interval);
                btn_login_e.html('登录');
                $.app.showErrMsg('未知错误请稍后尝试');
            }
        });
    },
    encodePwd: function(str){
        return md5(md5(str)+'chat.xbwq.com.cn');
    },
    addRequestSign: function(post_data){
        var signtime = parseInt((new Date().getTime())/1000);
        var request_str = signtime.toString();
        for(var key in post_data){
            request_str += key.toString()+post_data[key].toString();
        }
        post_data['sign'] = this.requestSign(request_str);
        post_data['signtime'] = signtime;
        return post_data;
    },
    requestSign: function(str){
        return md5('sign'+md5(str)+'request');
    }
}

$('.login_input').keyup(function(event){
    if(event.keyCode == 13){
        login();
    }else{
        if($(this).val() != ''){
            $.app.hideErrMsg();
        }
    }
})

$('#btn_login').click(function(){
    login();
});

function login(){
    var user_account_e = $('#user_account');
    var account = user_account_e.val();
    if(account == ''){
        $.app.showErrMsg('账号不可以为空',user_account_e);
        return false;
    }
    var user_pwd_e = $('#user_pwd');
    var pwd = user_pwd_e.val();
    if(pwd == ''){
        $.app.showErrMsg('密码不可以为空',user_pwd_e);
        return false;
    }
    $.app.login({account:account,pwd:$.app.encodePwd(pwd)});
}