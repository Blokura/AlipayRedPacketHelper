<?php
require "../config.php";
require_once '../other/gt/class.geetestlib.php';
require_once '../other/gt/config.php';
if ($HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"])
{
    $ip = $HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"];
}
elseif ($HTTP_SERVER_VARS["HTTP_CLIENT_IP"])
{
    $ip = $HTTP_SERVER_VARS["HTTP_CLIENT_IP"];
}
elseif ($HTTP_SERVER_VARS["REMOTE_ADDR"])
{
    $ip = $HTTP_SERVER_VARS["REMOTE_ADDR"];
}
elseif (getenv("HTTP_X_FORWARDED_FOR"))
{
    $ip = getenv("HTTP_X_FORWARDED_FOR");
}
elseif (getenv("HTTP_CLIENT_IP"))
{
    $ip = getenv("HTTP_CLIENT_IP");
}
elseif (getenv("REMOTE_ADDR"))
{
    $ip = getenv("REMOTE_ADDR");
}
else
{
    $ip = "127.0.0.1";
}

function isMobile(){  
    $useragent=isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';  
    $useragent_commentsblock=preg_match('|\(.*?\)|',$useragent,$matches)>0?$matches[0]:'';        
    function CheckSubstrs($substrs,$text){  
        foreach($substrs as $substr)  
            if(false!==strpos($text,$substr)){  
                return true;  
            }  
            return false;  
    }
    $mobile_os_list=array('Google Wireless Transcoder','Windows CE','WindowsCE','Symbian','Android','armv6l','armv5','Mobile','CentOS','mowser','AvantGo','Opera Mobi','J2ME/MIDP','Smartphone','Go.Web','Palm','iPAQ');
    $mobile_token_list=array('Profile/MIDP','Configuration/CLDC-','160×160','176×220','240×240','240×320','320×240','UP.Browser','UP.Link','SymbianOS','PalmOS','PocketPC','SonyEricsson','Nokia','BlackBerry','Vodafone','BenQ','Novarra-Vision','Iris','NetFront','HTC_','Xda_','SAMSUNG-SGH','Wapaka','DoCoMo','iPhone','iPod');  

    $found_mobile=CheckSubstrs($mobile_os_list,$useragent_commentsblock) ||  
              CheckSubstrs($mobile_token_list,$useragent);  

    if ($found_mobile){  
        return true;  
    }else{  
        return false;  
    }  
}

if(isMobile()){
$client_type = 'h5';
}else{
$client_type = 'web';
}

session_start();
$GtSdk = new GeetestLib(CAPTCHA_ID, PRIVATE_KEY);
$data = array(
        "user_id" => $_SESSION['user_id'], # 网站用户id
        "client_type" => $client_type, #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
        "ip_address" => $ip # 请在此处传输用户请求验证时所携带的IP
    );
if ($_SESSION['gtserver'] == 1) {   //服务器正常
    $result = $GtSdk->success_validate($_POST['geetest_challenge'], $_POST['geetest_validate'], $_POST['geetest_seccode'], $data);
    if ($result) {
    } else{
        echo '<script type="text/javascript">alert("错误:请先完成极验验证再提交!");window.location = "https://'.$_SERVER['HTTP_HOST'].'";</script>';
        exit();
    }
}else{  //服务器宕机,走failback模式
    if ($GtSdk->fail_validate($_POST['geetest_challenge'],$_POST['geetest_validate'],$_POST['geetest_seccode'])) {
    }else{
        echo '<script type="text/javascript">alert("错误:请先完成极验验证再提交!");window.location = "https://'.$_SERVER['HTTP_HOST'].'";</script>';
        exit();
    }
}
if((float)$_POST['total'] > 99 or (float)$_POST['total'] <= 0)
{
  echo '<script type="text/javascript">alert("错误:付款金额不可大于99且不可小于0!");window.location = "https://'.$_SERVER['HTTP_HOST'].'";</script>';
  exit();
}
$account = $_POST['account'];
$fkaccount = $_POST['fkaccount'];
if($account == "" or $fkaccount == ""){
  exit("缺少参数!");
}
error_reporting(E_ALL & ~E_NOTICE); //过滤脚本提醒
date_default_timezone_set('PRC'); //时区设置 解决某些机器报错
/*** 请填写以下配置信息 ***/
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "SELECT * FROM `zhandian` WHERE `sub`='".$_SERVER['HTTP_HOST']."' and `codepay` = '1'";//查询站点信息
$rs = $conn->query($sql);
if(mysqli_num_rows($rs) == 1){
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $siteID = $row["ID"];
  $qq = $row["qq"];
  $balance = $row["balance"];
  $codepay_key = $row["codepay_key"];
  $codepay_id = $row["codepay_id"];
  $nomoney = $row['nomoney'];
  if (empty($row["codepay_key"]) || (int)$row["codepay_id"] <= 1) exit('站点未设置码支付ID密钥');
  $outTradeNo = date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 10, 13), 1))), 0, 4);     //你自己的商品订单号，不能重复
  $sj = date('Y-m-d H:i:s');
  $codepay_config['host'] = 'https://'. $_SERVER['HTTP_HOST'];
  $codepay_config['path'] = $codepay_config['host'] . dirname($_SERVER['REQUEST_URI']); //API安装路径 最终为http://域名/codepay
  $codepay_path = $codepay_config['path']; //资源存放路径
  $willfk = (float)$_POST['total'] * (100-(float)$tradeFee)*0.01;
  $willfk = round($willfk,2);
  if((float)$balance < (float)$_POST['total'] * (100-(float)$tradeFee)*0.01     ){
    if($nomoney == '1'){
    echo '<script type="text/javascript">alert("很抱歉,站点自动返款余额不足,请联系客服进行补款操作或降低金额再试!");window.location = "https://'.$_SERVER['HTTP_HOST'].'";</script>';
    exit();
      }else{
      echo '<script type="text/javascript">alert("订单金额过大，系统不可自动返款，付款后请联系客服手动返款，谢谢!");</script>';
      }
    }
  $parameter = array(
    "id" => (int)$row["codepay_id"],//平台ID号
    "type" => "1",//支付方式
    "price" => (float)$_POST['total'],//原价
    "pay_id" => $outTradeNo, //可以是用户ID,站内商户订单号,用户名
    "param" => $outTradeNo,//自定义参数
    "act" => 0,//是否开启认证版的免挂机功能
    "outTime" => (int)"300",//二维码超时设置
    "page" => 4,//付款页面展示方式
    "return_url" => "https://".$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']). '/return.php',//付款后附带加密参数跳转到该页面
    "notify_url" =>"https://".$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']). '/notify.php',//付款后通知该页面处理业务
    "style" => 0,//付款页面风格
    "pay_type" => 1,//支付宝使用官方接口
    "chart" => trim(strtolower('utf-8'))//字符编码方式
    //其他业务参数根据在线开发文档，添加参数.文档地址:https://codepay.fateqq.com/apiword/
    //如"参数名"=>"参数值"
);
  }else{
  echo "未找到站点信息,请刷新后再试!";
}
ksort($parameter); //重新排序$data数组
reset($parameter); //内部指针指向数组中的第一个元素
$sign = '';
$urls = '';
foreach ($parameter AS $key => $val) {
    if ($val == '') continue;
    if ($key != 'sign') {
        if ($sign != '') {
            $sign .= "&";
            $urls .= "&";
        }
        $sign .= "$key=$val"; //拼接为url参数形式
        $urls .= "$key=" . urlencode($val); //拼接为url参数形式
    }
}
$key = md5($sign . $codepay_key);//密码追加进入开始MD5签名
$query = $urls . '&sign=' . $key; //创建订单所需的参数
$url = "http://api2.fateqq.com:52888/creat_order/?{$query}"; //支付页面
switch ($type) {
    case 1:
        $typeName = '支付宝';
        break;
    case 2:
        $typeName = 'QQ';
        break;
    default:
        $typeName = '微信';
}
$user_data = array("return_url" => "https://".$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']). '/return.php',
    "type" => 1, "outTime" => 300, "codePay_id" => $codepay_id);


$user_data["qrcode_url"] = "";


$user_data["logShowTime"] = 1;


//ob_clean(); //清空之前的输出
@header('Content-type: text/html; charset=utf-8');


/**
 * 获得支付接口返回的JSON 开发收银台
 */
if (function_exists('file_get_contents')) {
    $codepay_json = file_get_contents($url);
} else {
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $codepay_json = curl_exec($ch);
    curl_close($ch);
}
$json = json_decode($codepay_json,true);
if ($json["status"] == "0"){
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);
$sql = "INSERT INTO `dingdan` (`ID`, `qq`, `status`, `fankuan`, `totalFee`, `fkFee`, `fromID`,`buyer_id`) VALUES (".$outTradeNo.", '".$account."', 'WAIT_BUYER_PAY', 0,'".$_POST['total']."'  , 0, '".$siteID."','".$fkaccount."' )";
$rs = $conn->query($sql);
$sql = "SELECT * FROM `dingdan` WHERE `ID` = ".$outTradeNo;
$rs = $conn->query($sql);
if(mysqli_num_rows($rs) != 1){
  echo '<script type="text/javascript">alert("写入数据库失败!");window.location = "https://'.$_SERVER['HTTP_HOST'].'";</script>';   
  exit();
}
}
?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Content-Language" content="zh-cn">
    <meta name="apple-mobile-web-app-capable" content="no"/>
    <meta name="apple-touch-fullscreen" content="yes"/>
    <meta name="format-detection" content="telephone=no,email=no"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="white">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>支付宝扫码支付 - 码支付</title>
    <link href="<?php echo $codepay_path; ?>/css/wechat_pay.css" rel="stylesheet" media="screen">
<script>
var _hmt = _hmt || [];
(function() {
  var hm = document.createElement("script");
  hm.src = "https://hm.baidu.com/hm.js?b4dff7ed916cdf2bd674dead7fbc84e7";
  var s = document.getElementsByTagName("script")[0]; 
  s.parentNode.insertBefore(hm, s);
})();
</script>

</head>

<body>
<div class="body">
    <h1 class="mod-title">
        <span class="ico_log ico-1"></span>
    </h1>

    <div class="mod-ct">
        <div class="order">
        </div>
        <div class="amount" id="money">￥<?php echo $price ?></div>
        <div class="qrcode-img-wrapper" data-role="qrPayImgWrapper">
            <div data-role="qrPayImg" class="qrcode-img-area">
                <div class="ui-loading qrcode-loading" data-role="qrPayImgLoading" style="display: none;">加载中</div>
                <div style="position: relative;display: inline-block;">
                    <img id='show_qrcode' alt="加载中..." src="" width="210" height="210" style="display: block;">
                    <img onclick="$('#use').hide()" id="use"
                         src="<?php echo $codepay_path; ?>/img/use_1.png"
                         style="position: absolute;top: 50%;left: 50%;width:32px;height:32px;margin-left: -21px;margin-top: -21px">
                </div>
            </div>


        </div>
        <div class="time-item" id="msg">
            <h1>二维码过期时间</h1>
            <strong id="hour_show">0时</strong>
            <strong id="minute_show">0分</strong>
            <strong id="second_show">0秒</strong>
        </div>

        <div class="tip">
            <div class="ico-scan"></div>
            <div class="tip-text">
                <p>请使用支付宝扫一扫</p>
                <p>扫描二维码完成支付</p>
            </div>
        </div>

        <div class="detail" id="orderDetail">
            <dl class="detail-ct" id="desc" style="display: none;">

                <dt>状态</dt>
                <dd id="createTime">订单创建</dd>

            </dl>
            <a href="javascript:void(0)" class="arrow"><i class="ico-arrow"></i></a>
        </div>

        <div class="tip-text">
        </div>


    </div>
    <div class="foot">
        <div class="inner">
            <p>手机用户可保存上方二维码到手机中</p>
            <p>在支付宝扫一扫中选择“相册”即可</p>
        </div>
    </div>

</div>


<!--注意下面加载顺序 顺序错乱会影响业务-->
<script src="<?php echo $codepay_path; ?>/js/jquery-1.10.2.min.js"></script>
<!--[if lt IE 8]>
<script src="<?php echo $codepay_path;?>/js/json3.min.js"></script><![endif]-->
<script>
    var user_data =<?php echo json_encode($user_data);?>
</script>
<script src="<?php echo $codepay_path; ?>/js/notify.js"></script>
<script src="<?php echo $codepay_path; ?>/js/codepay_util.js"></script>
<script>callback(<?php echo $codepay_json;?>)</script>
<script>
    setTimeout(function () {
        $('#use').hide()
    }, user_data.logShowTime || 1000)
</script>
</body>
</html>