<?php 
require "config.php";
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "SELECT * FROM `zhandian` WHERE `sub`='".$_SERVER['HTTP_HOST']."';";//查询站点信息
$rs = $conn->query($sql);
if(mysqli_num_rows($rs) == 1){
    $row = $rs->fetch_array(MYSQLI_ASSOC);
    $status = $row["status"];
    if ($status != '0'){
      exit("该站点已被冻结,详情可联系主站客服!");
    }
    $daoqi = $row["time"];
    $qq = $row["qq"];
    if(strtotime($daoqi) < strtotime(date("Y-m-d H:i:s"))){
      exit("该站点已于".$daoqi."过期,请联系本站客服".$qq."解决!");
    }
    $siteID = $row["ID"];
    $siteName =  $row["name"];
    $announce = $row["announce"];
    $notice = $row["notice"];
    $codepay = $row["codepay"];
    mysqli_close($conn);
    if($codepay == "1") $codepay_url = "./codepay/";
  }else{
    exit("该站点还未启用,欢迎注册!!");
  }
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?php echo $siteName ?></title>
  <link href="//lib.baomitu.com/twitter-bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
  <script src="//lib.baomitu.com/jquery/1.12.4/jquery.min.js"></script>
  <script src="//lib.baomitu.com/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script src="./other/gt/gt.js"></script>
  <script src="./layui/layui.all.js"></script>
  <script src="./layui/layui.js"></script>
  <script> 
window.onload=function isWeiXin(){
  var ua = window.navigator.userAgent.toLowerCase();
  if(ua.match(/MicroMessenger/i) == 'micromessenger'){
  window.location.href="go.html";
  }else if(ua.match(/QQ/i) == "qq"){
  layer.msg('QQ浏览器可能导致无法唤起支付宝');
  }
}
    </script>
  <!--[if lt IE 9]>
    <script src="//lib.baomitu.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="//lib.baomitu.com/respond.js/1.4.2/respond.min.js"></script>
  <![endif]-->
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
  <nav class="navbar navbar-fixed-top navbar-default">
    <div class="container">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
          <span class="sr-only">导航按钮</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href=""><?php echo $siteName ?></a>
      </div><!-- /.navbar-header -->
      <div id="navbar" class="collapse navbar-collapse">
        <ul class="nav navbar-nav navbar-right">
          <li class="active">
            <a href="./"><span class="glyphicon glyphicon-user"></span> 首页</a>
          </li>
		  <li class="">
            <a href="http://wpa.qq.com/msgrd?v=3&uin=<?php echo $qq ?>&site=qq&menu=yes"><span class="glyphicon glyphicon-list"></span> 联系客服</a>
          </li>
        </ul>
      </div><!-- /.navbar-collapse -->
    </div><!-- /.container -->
  </nav><!-- /.navbar -->
  <div class="container" style="padding-top:70px;">
    <?php echo $announce ?>
    </div>
  <div class="container" style="padding-top:0px;">
    <div class="col-xs-12 col-sm-8 col-lg-8 center-block" style="float: none;">
  <div class="panel panel-danger">
            <div class="panel-heading"><h3 class="panel-title"><?php echo $siteName ?></h3></div>
  <div class="panel-body">
  <form action="<?php echo $codepay_url ?>pay.php" method="POST">
       <div class="input-group">
        <span class="input-group-addon"><span class="glyphicon glyphicon-yen"></span></span>
              <input type="num" name="total" class="form-control" placeholder="需要生成的订单金额" required="required" onkeyup="value=value.replace(/[^\d]/g,'') " ng-pattern="/[^a-zA-Z]/"/>
       </div><br/>
       <div class="input-group">
              <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
              <input type="text" name="account" class="form-control" placeholder="你的QQ账号,方便联系" required="required" onkeyup="value=value.replace(/[^\d]/g,'') " ng-pattern="/[^a-zA-Z]/"/>
       </div><br/>
       <?php 
     if($codepay == "1"){
       echo "<div class=\"input-group\"><span class=\"input-group-addon\"><span class=\"glyphicon glyphicon-user\"></span></span><input type=\"text\" name=\"fkaccount\" class=\"form-control\" placeholder=\"你的支付宝账号,返款会打到这里\" required=\"required\"></div><br/>";
     }
    ?>
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
  
    <div class="container" style="padding-top:0px;">
      <?php echo $notice ?>
    </div>
  
  <p style="text-align:center"><span style="font-weight:bold">©2018 Powered by <a href="http://wpa.qq.com/msgrd?v=3&uin=<?php echo $qq ?>&site=qq&menu=yes"><?php echo $siteName ?></a>&nbsp;<a href="http://wpa.qq.com/msgrd?v=3&uin=<?php echo $qq ?>&site=qq&menu=yes">开通分站</a></span></p>
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
        captchaObj.appendTo('#embed-captcha');
        captchaObj.onReady(function () {
            $("#wait")[0].style = "display:none;";
        });
    };
    $.ajax({
        url: "./other/gt/StartCaptchaServlet.php?t=" + (new Date()).getTime(), // 加随机数防止缓存
        type: "get",
        dataType: "json",
        success: function (data) {
            console.log(data);
            initGeetest({
                gt: data.gt,
                challenge: data.challenge,
                new_captcha: data.new_captcha,
                product: "float", 
                https: true ,
                offline: !data.success, 
                width: '100%',
                lang: 'zh-cn',
            }, handlerEmbed
    );
        }
    });
</script>
  </body>