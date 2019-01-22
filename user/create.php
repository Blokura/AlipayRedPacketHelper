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
   $fenzhanprice = $row['v'];//remark
  if(isset($_GET['create'])){
    $sql = "SELECT * FROM `zhandian` WHERE `usr`='".$_POST['usr']."'";
    $rs = $conn->query($sql);
    if(mysqli_num_rows($rs) != 0){
     exit("<script language='javascript'>alert('该用户名已存在!');window.location.href='create.php';</script>");
    }
    $sql = "SELECT * FROM `zhandian` WHERE `sub`='".$_POST['sub']."'";
    $rs = $conn->query($sql);
    if(mysqli_num_rows($rs) != 0){
     exit("<script language='javascript'>alert('该二级域名已存在!');window.location.href='create.php';</script>");
    }
    $sql = "SELECT * FROM `zhandian` WHERE `usr`='".$_SESSION['usr']."' and `pwd`='".$_SESSION['pwd']."' and `ID`=".$_SESSION['fzID'];//查询站点信息
    $rs = $conn->query($sql);
    if(mysqli_num_rows($rs) == 1){
      $row = $rs->fetch_array(MYSQLI_ASSOC);
      $balance = $row['balance'];
    }
    if((float)$balance<(float)$fenzhanprice){
      mysqli_close($conn);
      exit("<script language='javascript'>alert('余额不足!');window.location.href='create.php';</script>");
    }
    //站点钱够开始扣钱
    $sql = "UPDATE `zhandian` SET `balance` = `balance` - ".$fenzhanprice." WHERE `ID` = ".$_SESSION['fzID'];//扣钱
    $rs = $conn->query($sql);
    $sql = "SELECT * FROM `zhandian` WHERE `ID`=".$_SESSION['fzID'];//查询站点信息
    $rs = $conn->query($sql);
    $balance_after = $row['balance'];
    if($balance_after <0){
    //没钱打尼玛呢
     $sql = "UPDATE `zhandian` SET `balance` = ".$balance." WHERE `ID` = ".$_SESSION['fzID'];//加回去
     $rs = $conn->query($sql);
     mysqli_close($conn);
     exit("<script language='javascript'>alert('余额不足!');window.location.href='create.php';</script>");
    }else{
     $sql = "INSERT INTO `log` (`fzID`, `money_before`, `money_after`, `reason`) VALUES (".$_SESSION['fzID'].",".$balance." ,".(string)((float)$balance - $fenzhanprice)." , '创建分站,账号:".$_POST["name"].",QQ:".$_POST["qq"]."')"; 
     $rs = $conn->query($sql);
     $sql = "INSERT INTO `zhandian`(`name`, `usr`, `pwd`,`sub`,`time`,`qq`) VALUES ('".$_POST["name"]."','".$_POST["usr"]."','".$_POST["pwd"]."','".$_POST["sub"].".moebili.com',DATE_ADD(NOW(),INTERVAL 1 MONTH),'".$_POST["qq"]."')";
     $rs = $conn->query($sql);
      mysqli_close($conn);
      exit("<script language='javascript'>alert('开通成功!');window.location.href='create.php';</script>");
   }
      }else{
     mysqli_close($conn);
  }
  $conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
  $conn->set_charset($db_config["charset"]);//设置字符集
  $sql = "SELECT * FROM `zhandian` WHERE `usr`='".$_SESSION['usr']."' and `pwd`='".$_SESSION['pwd']."' and `ID`=".$_SESSION['fzID'];//查询站点信息
  $rs = $conn->query($sql);
  if(mysqli_num_rows($rs) == 1){
    $row = $rs->fetch_array(MYSQLI_ASSOC);
    $name = $row['name'];//站点名称
    $appid = $row['appid'];
    $alipayPublicKey = $row['alipayPublicKey'];
    $rsaPrivateKey = $row['rsaPrivateKey'];
    $ordername = $row['ordername'];
    $tradeFee = $row['tradeFee'];
    $announce = $row['announce'];
    $notice = $row['notice'];
    $qq = $row['qq'];
    $codepay = $row['codepay'];
     if($codepay = "1"){
      $alipay_style = "display:none";
    }else{
      $codepay_style = "display:none";
    }
    $codepay_id = $row['codepay_id'];
    $codepay_key = $row['codepay_key'];
  mysqli_close($conn);
  }else{
  unset($_SESSION);
  session_destroy(); 
  exit("<script language='javascript'>window.location.href='set.php';</script>");
  }
?>
<!DOCTYPE html>
<html lang="zh-cn"><head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>红包助手后台管理中心</title>
  <link href="//lib.baomitu.com/twitter-bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
  <script src="//lib.baomitu.com/jquery/1.12.4/jquery.min.js"></script>
  <script src="//lib.baomitu.com/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
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
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">创建新分站</h3></div>
<div class="panel-body">
  <form action="./create.php?create" method="post" class="form-horizontal" role="form"><input type="hidden" name="do" value="submit">
	<div class="form-group">
	  <label class="col-sm-2 control-label">网站名称</label>
	  <div class="col-sm-10"><input type="text" name="name"  class="form-control"  required="required"></div>
	</div><br>
	<div class="form-group">
	  <label class="col-sm-2 control-label">用户名</label>
	  <div class="col-sm-10"><input type="text" name="usr"  class="form-control"  required="required"></div>
	</div><br>
	<div class="form-group">
	  <label class="col-sm-2 control-label">密码</label>
	  <div class="col-sm-10"><input type="text" name="pwd"  class="form-control" required="required"></div>
	</div><br>
     <div class="form-group">
	  <label class="col-sm-2 control-label">二级域名</label>
	  <div class="col-sm-10"><input type="text" name="sub" placeholder="填二级域名,如xxx.moebili.com填xxx" class="form-control" required="required"></div>
	</div><br>
    <div class="form-group">
	  <label class="col-sm-2 control-label">客服QQ</label>
	  <div class="col-sm-10"><input type="num" name="qq"  class="form-control" required="required" onkeyup="value=value.replace(/[^\d]/g,'') " ng-pattern="/[^a-zA-Z]/"/></div>
	</div>
    <div class="form-group">
	  <label class="col-sm-2 control-label">开通月数</label>
	  <div class="col-sm-10"><input type="num" name="month"  class="form-control" readonly="readonly" value="1"></div>
	</div>
    <div class="form-group">
	  <label class="col-sm-2 control-label">开通价格</label>
	  <div class="col-sm-10"><input type="num" name="month"  class="form-control" readonly="readonly" value="<?php echo $fenzhanprice ?>"></div>
	</div><br>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="确定开通" class="btn btn-primary form-control"><br>
	 </div>
	</div>
  </form>
</div>
</div>
    </div>
  </div>
    </body></html>