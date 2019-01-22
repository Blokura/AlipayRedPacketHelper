<?php
require "../config.php";
require_once './other/gt/class.geetestlib.php';
require_once './other/gt/config.php';
if(isset($_GET['logout']))
{
  session_start();
  unset($_SESSION);
  session_destroy(); 
  exit("<script language='javascript'>alert('退出登录成功!');window.location.href='./index.php';</script>");
  }
  
if(isset($_POST['usr']) && isset($_POST['pwd'])){
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
        echo '<script type="text/javascript">alert("错误:请先完成极验验证再提交!");window.location = "https://'.$_SERVER['HTTP_HOST'].'/login.php";</script>';
        exit();
    }
}else{  //服务器宕机,走failback模式
    if ($GtSdk->fail_validate($_POST['geetest_challenge'],$_POST['geetest_validate'],$_POST['geetest_seccode'])) {
    }else{
        echo '<script type="text/javascript">alert("错误:请先完成极验验证再提交!");window.location = "https://'.$_SERVER['HTTP_HOST'].'/login.php";</script>';
        exit();
    }
}
  
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "SELECT * FROM `zhandian` WHERE `usr`='".$_POST['usr']."' and `pwd`='".$_POST['pwd']."'";//查询站点信息
$rs = $conn->query($sql);
if(mysqli_num_rows($rs) == 1){
  $row = $rs->fetch_array(MYSQLI_ASSOC);
    $status = $row["status"];
    if ($status != '0'){
      exit("<script language='javascript'>alert('该站点已被冻结,请联系主站客服!');window.location.href='./login.php';</script>");
    }
  setcookie(session_name(), session_id(), time() + 30*3600, "/"); 
  $_SESSION['fzID'] = $row['ID'];
  $_SESSION['usr'] = $_POST['usr'];
  $_SESSION['pwd'] = $_POST['pwd'];
  $_SESSION['islogin'] = 'true';
  exit("<script language='javascript'>alert('登录成功!');window.location.href='./index.php';</script>");
}else{
  exit("<script language='javascript'>alert('账号或密码错误,请重试!');window.location.href='./login.php';</script>");
}
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>用户登录</title>
  <link href="//lib.baomitu.com/twitter-bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
  <script src="//lib.baomitu.com/jquery/1.12.4/jquery.min.js"></script>
  <script src="./other/gt/gt.js"></script>
  <script src="//lib.baomitu.com/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <!--[if lt IE 9]>
    <script src="//lib.baomitu.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="//lib.baomitu.com/respond.js/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body>
  <nav class="navbar navbar-fixed-top navbar-default">
    <div class="container">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
          <span class="sr-only">导航按钮</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="./">后台管理中心</a>
      </div><!-- /.navbar-header -->
      <div id="navbar" class="collapse navbar-collapse">
        <ul class="nav navbar-nav navbar-right">
          <li class="active">
            <a href="./login.php"><span class="glyphicon glyphicon-user"></span> 登陆</a>
          </li>
        </ul>
      </div><!-- /.navbar-collapse -->
    </div><!-- /.container -->
  </nav><!-- /.navbar -->
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-md-8 col-lg-6 center-block" style="float: none;">
      <div class="panel panel-primary">
        <div class="panel-heading"><h3 class="panel-title">后台用户登陆</h3></div>
        <div class="panel-body">
          <form action="./login.php" method="post" class="form-horizontal" role="form">
            <div class="input-group">
              <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
              <input type="text" name="usr" value="" class="form-control" placeholder="用户名" required="required"/>
            </div><br/>
            <div class="input-group">
              <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
              <input type="password" name="pwd" class="form-control" placeholder="密码" required="required"/>
            </div><br/>
			  
				<div id="embed-captcha" class="small"></div>
               <p id="wait" >正在加载验证码,若长时间无反应请刷新页面</p><br/>
			<br/>
			            <div class="form-group">
              <div class="col-xs-12"><input type="submit" value="登陆" class="btn btn-primary form-control"/></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
<script>
    var handlerEmbed = function (captchaObj) {
        $("#submit").click(function (e) {
            var validate = captchaObj.getValidate();
            if (!validate) {
                $("#notice")[0].style = "";
                setTimeout(function () {
                    $("#notice")[0].style = "display:none;";
                }, 2000);
                e.preventDefault();
            }
        });
        // 将验证码加到id为captcha的元素里，同时会有三个input的值：geetest_challenge, geetest_validate, geetest_seccode
        captchaObj.appendTo('#embed-captcha');
        captchaObj.onReady(function () {
            $("#wait")[0].style = "display:none;";
        });
        // 更多接口参考：http://www.geetest.com/install/sections/idx-client-sdk.html
    };
    $.ajax({
        // 获取id，challenge，success（是否启用failback）
        url: "./other/gt/StartCaptchaServlet.php?t=" + (new Date()).getTime(), // 加随机数防止缓存
        type: "get",
        dataType: "json",
        success: function (data) {
            console.log(data);
            // 使用initGeetest接口
            // 参数1：配置参数
            // 参数2：回调，回调的第一个参数验证码对象，之后可以使用它做appendTo之类的事件
            initGeetest({
                gt: data.gt,
                challenge: data.challenge,
                new_captcha: data.new_captcha,
                product: "float", // 产品形式，包括：float，embed，popup。注意只对PC版验证码有效
                https: true ,
                offline: !data.success, // 表示用户后台检测极验服务器是否宕机，一般不需要关注
                width: '100%',
                lang: 'zh-cn',
                // 更多配置参数请参见：http://www.geetest.com/install/sections/idx-client-sdk.html#config
            }, handlerEmbed
        // 省略其他方法的调用
    );
        }
    });
</script>
</body>