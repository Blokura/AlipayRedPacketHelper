<?php
require '../config.php';
header('Content-type:text/html; Charset=utf-8');

ksort($_POST); //排序post参数
reset($_POST); //内部指针指向数组中的第一个元素

$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "SELECT * FROM `zhandian` WHERE `sub`='".$_SERVER['HTTP_HOST']."'";//查询站点信息
$rs = $conn->query($sql);
if(mysqli_num_rows($rs) == 1){
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $codepay_key = $row["codepay_key"];
  }else{
  exit("网站不存在");
  }
$sign = '';//初始化
foreach ($_POST AS $key => $val) { //遍历POST参数
    if ($val == '' || $key == 'sign') continue; //跳过这些不签名
    if ($sign) $sign .= '&'; //第一个字符串签名不加& 其他加&连接起来参数
    $sign .= "$key=$val"; //拼接为url参数形式
}
if (!$_POST['pay_no'] || md5($sign . $codepay_key) != $_POST['sign']) { //不合法的数据
    exit('fail');  //返回失败 继续补单
} else { //合法的数据
    //业务处理
    $pay_id = $_POST['pay_id']; //需要充值的ID 或订单号 或用户名
    $money = (float)$_POST['money']; //实际付款金额
    $price = (float)$_POST['price']; //订单的原价
    $param = $_POST['param']; //自定义参数
    $pay_no = $_POST['pay_no']; //流水号
    $conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
    $conn->set_charset($db_config["charset"]);//设置字符集
    $sql = "UPDATE `dingdan` SET `status`='TRADE_SUCCESS' , `trade_no`='".$pay_no."' ,`totalFee`  = '".$money."'  WHERE `ID`=".$pay_id;//更改订单信息
    $rs = $conn->query($sql);
    $sql = "SELECT * FROM `dingdan` WHERE `ID`=".$pay_id;//查询指定订单号
    $rs = $conn->query($sql);
    while($row = mysqli_fetch_array($rs)){
      $zh = $row['buyer_id'];//拉出回款账号
      $ma = $row['status'];//拉出订单状态
      $hkm = $row['fankuan'];//拉出回款状态
      $jine = $row['totalFee'];//拉出订单金额
    }
    mysqli_close($conn); 
    if($ma == 'TRADE_SUCCESS' and $hkm == '0' and $jine <= 99){ //如果订单状态为成功，回款状态为0，金额小于30
	$url="https://".$_SERVER['HTTP_HOST']."/alipayfankuan.php?type=2&tradeno=".$pay_id;      //回款接口地址
	$content= file_get_contents($url);    //发送一个get请求  
    }
    exit('success'); //返回成功 不要删除哦
}