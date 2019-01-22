<?php
require "../config.php";
  session_start();
  if (isset($_SESSION['fzID']) && isset($_SESSION['usr']) && isset($_SESSION['pwd']) and $_SESSION['islogin'] == 'true'){
  }else{
  unset($_SESSION);
  session_destroy(); 
  exit("<script language='javascript'>window.location.href='./login.php';</script>");
  }
  $conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
  $conn->set_charset($db_config["charset"]);//设置字符集
  $sql = "SELECT * FROM `config` WHERE `k`='price'";//查询站点信息
  $rs = $conn->query($sql);
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $price = $row['v'];
  $sql = "SELECT * FROM `zhandian` WHERE `usr`='".$_SESSION['usr']."' and `pwd`='".$_SESSION['pwd']."' and `ID`=".$_SESSION['fzID'];//查询站点信息
  $rs = $conn->query($sql);
  if(mysqli_num_rows($rs) == 1){
    $row = $rs->fetch_array(MYSQLI_ASSOC);
    $_SESSION['fzID'] = $row['ID'];
    $status = $row['status'];
    if ($status != '0'){
      exit("该站点已被冻结,详情可联系主站客服!");
    }
    $balance = $row['balance'];
    mysqli_close($conn);
  }else{
    unset($_SESSION);
    session_destroy(); 
    exit("<script language='javascript'>window.location.href='./login.php';</script>");
  }
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>红包助手后台管理中心</title>
  <link href="//lib.baomitu.com/twitter-bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="//lib.baomitu.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"/>
  <script src="//lib.baomitu.com/jquery/1.12.4/jquery.min.js"></script>
  <script src="//lib.baomitu.com/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script src="./other/gt/gt.js"></script>
  <!--[if lt IE 9]>
    <script src="//lib.baomitu.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="//lib.baomitu.com/respond.js/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body id="error-page">
  <nav class="navbar navbar-fixed-top navbar-default">
    <div class="container">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
          <span class="sr-only">导航按钮</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="./">红包助手后台管理中心</a>
      </div><!-- /.navbar-header -->
      <div id="navbar" class="collapse navbar-collapse">
        <ul class="nav navbar-nav navbar-right">
          <li class="">
            <a href="./"><span class="glyphicon glyphicon-user"></span> 平台首页</a>
          </li>
		  <li class="">
            <a href="./set.php"><span class="glyphicon glyphicon-cog"></span> 系统设置</a>
          </li>
          <li class="">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="glyphicon glyphicon-list"></span> 记录查询<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <li><a href="./list.php">订单列表</a></li>
			  <li><a href="./log.php">余额记录</a></li>
            </ul>
          </li>
          <li><a href="./login.php?logout"><span class="glyphicon glyphicon-log-out"></span> 退出登陆</a></li>
        </ul>
      </div><!-- /.navbar-collapse -->
    </div><!-- /.container -->
  </nav><!-- /.navbar -->
  <div class="container" style="padding-top:70px;">
<div class="col-xs-12 col-sm-10 col-md-8 col-lg-6 center-block" style="float: none;">
<div class="panel panel-info">
<div class="panel-heading" style="background: linear-gradient(to right,#14b7ff,#b221ff);"><h3 class="panel-title"><font color="#FFFFFF">
<i class=""></i>可用余额：<?php echo $balance ?>元</font></h3></div>
  <div class="panel-body">
  <form action="pay.php" method="POST" name="recharge">
       <div class="input-group">
        <span class="input-group-addon"><span class="glyphicon glyphicon-yen"></span></span>
              <input type="num" name="total" class="form-control" placeholder="需要充值的金额" required="required" onkeyup="value=value.replace(/[^\d]/g,'');clac()" ng-pattern="/[^a-zA-Z]/"/>
       </div><br/>
       <div class="input-group">
              <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
              <input type="text" name="Fee" class="form-control" placeholder="实际应付金额" required="required" readonly="readonly"/>
       </div><br/>
             <div id="embed-captcha" class="small"></div>
         <p id="wait" >正在加载验证码,若长时间无反应请刷新页面</p><br/>
        <!---</div>---><br/>
       <div class="form-group">
              <div class="col-xs-12"><input type="submit" value="前往支付" class="btn btn-danger form-control"/></div>
       </div>
    </form>
        </div>
      </div>
    </div>
  </div>
    <div class="container" style="padding-top:70px;">
<div class="col-xs-12 col-sm-10 col-md-8 col-lg-6 center-block" style="float: none;">
<div class="panel panel-info">
<div class="panel-heading" style="background: linear-gradient(to right,#14b7ff,#b221ff);"><h3 class="panel-title"><font color="#FFFFFF">
<i class=""></i>续费站点 <?php echo $price ?>元/月</font></h3></div>
  <div class="panel-body">
    <form name="xufei">
       <div class="input-group">
        <span class="input-group-addon"><span class="glyphicon glyphicon-yen"></span></span>
              <input type="num" name="month" class="form-control" placeholder="需要续费的月份" required="required" onkeyup="value=value.replace(/[^\d]/g,'');xufeixufei()" ng-pattern="/[^a-zA-Z]/"/>
       </div><br/>
       <div class="input-group">
              <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
              <input type="text" name="xufeiFee" class="form-control" placeholder="实际应付金额" required="required" readonly="readonly"/>
       </div><br/>
        <!---</div>---><br/>
       <div class="form-group">
              <div class="col-xs-12"><input value="立即续费(使用余额)" class="btn btn-danger form-control" onclick="zhandianxufei()"/></div>
       </div>
      </form>
        </div>
      </div>
    </div>
  </div>
<script src="//lib.baomitu.com/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
<script src="//lib.baomitu.com/layer/2.3/layer.js"></script>
      <script>
    var clac = function (){
	var Rem=document.recharge.total.value;
	var Fee=Rem*1.01;
	document.recharge.Fee.value="应付金额(含1%手续费):"+Fee;
}
    var xufeixufei = function (){
	var Rem1=document.xufei.month.value;
	var Fee1=Rem1*<?php echo $price ?>;
	document.xufei.xufeiFee.value="扣款金额:"+Fee1;
}
    function zhandianxufei(){
	$.get("xufei.php?month="+document.xufei.month.value, function(data) {
		alert(data);
        window.location = "./recharge.php";
	}, 'text');
}
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