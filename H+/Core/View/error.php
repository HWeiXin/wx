<!DOCTYPE html>
<html>
<head>
    <title><?php echo H::app()->getConfig('app_name'); ?></title>
    <meta charset="utf-8">
    <style>
        body{text-align: center;position: relative;top: 100px;color: red;font-size: 24px;}
    </style>
</head>
<body>
服务器错误:<?php if(!empty($data)): ?><div style="text-align: left;margin-left: 40px;"><?php p($data); ?></div><?php endif; ?><?php echo $msg; ?>
</body>
</html>