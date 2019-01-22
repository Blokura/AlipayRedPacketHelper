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
  $sql = "SELECT * FROM `zhandian` WHERE `usr`='".$_SESSION['usr']."' and `pwd`='".$_SESSION['pwd']."'";//查询站点信息
  $rs = $conn->query($sql);
  if(mysqli_num_rows($rs) == 1){
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $_SESSION['fzID'] = $row['ID'];
  $status = $row['status'];
  if ($status != '0'){
      exit("该站点已被冻结,详情可联系主站客服!");
  }
  $balance = $row['balance'];
  $daoqi = $row['time'];
  if(strtotime($daoqi) < strtotime(date("Y-m-d H:i:s"))){
  $daoqi = $daoqi."(已过期)";
  }
  $sql = "SELECT * FROM `dingdan` WHERE `fromID` = ".$_SESSION['fzID']." and to_days(`time`) = to_days(now()) and `status` = 'TRADE_SUCCESS'";
  $rs = $conn->query($sql);
  $ordernum = mysqli_num_rows($rs);//获取订单数量
  $sql = "SELECT SUM(`totalFee`) as num FROM `dingdan` WHERE `fromID` = ".$_SESSION['fzID']." and to_days(`time`) = to_days(now()) and `status` = 'TRADE_SUCCESS'";//获取今日金额
  $rs = $conn->query($sql);
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $orderFee = $row['num'];
    if($orderFee == ''){
      $orderFee = '0';
      }
    
  //今日数据获取完毕 开始获取全部数据
    
  $sql = "SELECT * FROM `dingdan` WHERE `fromID` = ".$_SESSION['fzID']." and `status` = 'TRADE_SUCCESS'";
  $rs = $conn->query($sql);
  $allordernum = mysqli_num_rows($rs);//获取订单数量
  $sql = "SELECT SUM(`totalFee`) as num FROM `dingdan` WHERE `fromID` = ".$_SESSION['fzID']." and `status` = 'TRADE_SUCCESS'";//获取今日金额
  $rs = $conn->query($sql);
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $allorderFee = $row['num'];
  if($allorderFee == ''){
      $allorderFee = '0';
      }
  $sql = "SELECT * FROM `config` WHERE `k`='houtaiannounce'";//查询站点信息
  $rs = $conn->query($sql);
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $announce = $row['v'];//appid
    
  mysqli_close($conn);
  }else{
  unset($_SESSION);
  session_destroy(); 
  exit("<script language='javascript'>window.location.href='./login.php';</script>");
  }
?>
<html lang="zh-cn" xmlns="http://www.w3.org/1999/xhtml"><head>
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
          <li class="active">
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
  
    
    
    
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>管理后台</title>
  
  <div class="container" style="padding-top:70px;">
    <div class="col-sm-12 col-md-9 center-block"  style="float: none;">
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">系统公告</h3></div>
<div class="panel-body">
<?php echo $announce ?>
</div>
</div>
</div>
    </div>
        <div class="container" style="padding-top:0px;">
    <div class="col-sm-12 col-md-9 center-block"  style="float: none;">
      <div class="panel panel-primary">
        <div class="panel-heading text-center"><h3 class="panel-title" id="title">站点数据统计面板 分站ID:<?php echo $_SESSION['fzID'] ?></h3></div>
<table class="table table-bordered">
<tbody>
<tr height="25">
<td align="center"><font color="#808080"><b><span class="glyphicon glyphicon-tint"></span>今日订单数</b><br><?php echo $ordernum ?>条</font></td>
<td align="center"><font color="#808080"><b><i class="glyphicon glyphicon-usd"></i>账户余额</b><br><?php echo $balance ?>元</font></td>
<td align="center"><font color="#808080"><b><i class="glyphicon glyphicon-tint"></i>今日交易额</b><br><?php echo $orderFee ?>元</font></td>
</tr>
<tr height="25">
<td align="center"><font color="#808080"><b><span class="glyphicon glyphicon-tint"></span>总计订单数</b><br><?php echo $allordernum ?>条</font></td>
<td align="center"><font color="#808080"><b><i class="glyphicon glyphicon-usd"></i>到期时间</b><br><?php echo $daoqi ?></font></td>
<td align="center"><font color="#808080"><b><i class="glyphicon glyphicon-tint"></i>总计交易额</b><br><?php echo $allorderFee ?>元</font></td>
</tr>
<tr><td align="center" colspan="3">  
<a href="./set.php" class="btn btn-sm btn-default"><i class="glyphicon glyphicon-cog"></i> 系统设置</a>&nbsp;<a href="./recharge.php" class="btn btn-sm btn-default"><i class="glyphicon glyphicon-cog"></i> 余额充值</a>&nbsp;<a href="./create.php" class="btn btn-sm btn-default"><i class="glyphicon glyphicon-cog"></i> 开通分站</a>&nbsp;<a href="./list.php" class="btn btn-sm btn-default"><i class="glyphicon glyphicon-list"></i> 订单记录</a>&nbsp;<a href="./log.php" class="btn btn-sm btn-default"><i class="glyphicon glyphicon-list"></i> 余额记录</a>
</td>
<tr><td align="center" colspan="3">  
<a href="https://shimo.im/docs/Yt42CTZObRsDYhSy/" target="_blank" class="btn btn-sm btn-default"><i class="glyphicon glyphicon-cog"></i> 分站介绍</a>&nbsp;<a href="https://shimo.im/docs/IE9inrzHmAUaEbM1/p" target="_blank" class="btn btn-sm btn-default"><i class="glyphicon glyphicon-cog"></i> 支付接口配置教程</a>
</td>
</tr>
<tr height="25">
<td align="center" colspan="3"><a href="./" class="btn btn-sm btn-info"><i class="glyphicon glyphicon-home"></i>网站首页</a>&nbsp;<a href="./login.php?logout" class="btn btn-sm btn-danger"><i class="glyphicon glyphicon-log-out"></i>退出登录</a></td>
</tr>
</tbody>
</table>
</div>
    </div>
  </div>
</body></html>