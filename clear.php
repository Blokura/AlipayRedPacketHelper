<?php
require 'config.php';
header('Content-type:text/html; Charset=utf-8');

$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "DELETE FROM `dingdan` WHERE `time`< DATE_SUB(NOW(),INTERVAL  1 DAY) and  (`status` = 'WAIT_BUYER_PAY' or `status` is NULL)";//查询站点信息
$rs = $conn->query($sql);
exit("清理数据库完毕!");
?>