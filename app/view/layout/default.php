<!DOCTYPE html>
<html>
<head>
    <title><?php echo H::app()->getConfig('app_name'); ?></title>
    <meta charset="utf-8">
    <link rel="icon" href="<?php echo H::app()->public_url; ?>/images/favicon.ico" sizes="any">
    <link href="<?php echo H::app()->public_url; ?>/login/css/login.css" type="text/css" rel="stylesheet">
    <script> window.base_url = '<?php echo H::app()->base_url; ?>'; </script>
</head>
<body>
    <?php echo $H_VIEW_HTML; ?>
    <script src="<?php echo H::app()->public_url; ?>/js/jquery.min.js"></script>
    <script src="<?php echo H::app()->public_url; ?>/js/md5.min.js"></script>
    <script src="<?php echo H::app()->public_url; ?>/login/js/login.js"></script>
</body>
</html>