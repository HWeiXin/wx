<div id="login_box">
    <span id="sys_title"><?php echo H::app()->getConfig('app_name'); ?></span>
    <div>
        <input id="user_account" value="<?php echo isset($cookie['account'])?$cookie['account']:''; ?>" class="login_input" placeholder="账号" type="text">
    </div>
    <div>
        <input id="user_pwd" value="" class="login_input" placeholder="密码" type="password">
    </div>
    <div id="err_msg"></div>
    <a id="btn_login" href="javascript:;">登录</a>
    <div id="copyright">© 2015</div>
</div>