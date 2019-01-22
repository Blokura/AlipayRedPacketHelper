<?php
require '../config.php';
header('Content-type:text/html; Charset=utf-8');
session_start();
if (isset($_SESSION['fzID']) && isset($_SESSION['usr']) && isset($_SESSION['pwd']) and $_SESSION['islogin'] == 'true'){
}else{
  unset($_SESSION);
  session_destroy(); 
  exit("<script language='javascript'>window.location.href='../index.php';</script>");
}
$month = $_GET['month'];
if ($month == ""){
  exit("未传入续费月份");
}
//开始读取单月价格
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "SELECT * FROM `config` WHERE `k`='price'";//查询站点信息
$rs = $conn->query($sql);
$row = $rs->fetch_array(MYSQLI_ASSOC);
$price = $row['v'];
//读取完毕

$totalFee = (float)$price * (float)$month;
$totalFee = round($totalFee,2);

$sql = "SELECT * FROM `zhandian` WHERE `ID`=".$_SESSION['fzID'];//查询站点信息
$rs = $conn->query($sql);
$row = $rs->fetch_array(MYSQLI_ASSOC);
$balance = $row['balance'];//拉出站点余额
$time = $row['time'];
if($balance < $totalFee){
  //不够钱了
  mysqli_close($conn);
  exit("余额不足,续费".$month."月需要".(string)$totalFee."元,而您的余额为".$balance);
}

//站点钱够开始扣钱
$sql = "UPDATE `zhandian` SET `balance` = `balance` - ".(string)$totalFee." WHERE `ID` = ".$_SESSION['fzID'];//扣钱
$rs = $conn->query($sql);
$sql = "SELECT * FROM `zhandian` WHERE `ID`=".$_SESSION['fzID'];//查询站点信息
$rs = $conn->query($sql);
$row = $rs->fetch_array(MYSQLI_ASSOC);
$balance_after = $row['balance'];
if($balance_after <0){
//没钱打尼玛呢
  $sql = "UPDATE `zhandian` SET `balance` = `balance` + ".(string)$totalFee." WHERE `ID` = ".$_SESSION['fzID'];//加回去
  $rs = $conn->query($sql);
  mysqli_close($conn);
  exit();
}else{
  if (strtotime($time)<strtotime( date("Y-m-d H:i:s"))){
    $sql = "UPDATE `zhandian` SET `time` = DATE_ADD(NOW(),INTERVAL ".$month." MONTH) WHERE `ID` = ".$_SESSION['fzID'];
  }else{
    $sql = "UPDATE `zhandian` SET `time` = DATE_ADD(`time`,INTERVAL ".$month." MONTH) WHERE `ID` = ".$_SESSION['fzID'];
  }
  $rs = $conn->query($sql);
  $sql = "SELECT * FROM `zhandian` WHERE `ID`=".$_SESSION['fzID'];//查询站点信息
  $rs = $conn->query($sql);
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $time = $row['time'];
  $sql = "INSERT INTO `log` (`fzID`, `money_before`, `money_after`, `reason`) VALUES (".$_SESSION['fzID'].",".$balance." ,".$balance_after." , '站点续费".$month."月,有效期至".$time."')";
  $rs = $conn->query($sql);
  exit("续费成功,有效期至".$time);
}
