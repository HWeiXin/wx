<?php

/**
 * 打印方法
 * @param mixed $var 被打印的数据
 */
function p($var) {
    echo "<pre>" . print_r($var, true) . "</pre>";
}