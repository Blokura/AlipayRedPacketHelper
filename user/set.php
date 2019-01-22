<?php
require "../config.php";
  session_start();
  if (isset($_SESSION['fzID']) && isset($_SESSION['usr']) && isset($_SESSION['pwd']) and $_SESSION['islogin'] == 'true'){
}else{
  unset($_SESSION);
  session_destroy(); 
  exit("<script language='javascript'>window.location.href='./login.php';</script>");
}
  if(isset($_GET['set'])){
    $conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
    $conn->set_charset($db_config["charset"]);//设置字符集
    if ($_POST['pwd'] == "")
    {
    $sql = "UPDATE `zhandian` SET `api`=".$_POST['api']." , `nomoney`= ".$_POST['nomoney'].",  `fenzhan` = ".$_POST['fenzhan']." ,`fenzhan_price` = ".$_POST['fenzhan_price'].", `name`='".$_POST['name']."',`appid`='".$_POST['appid']."',`alipayPublicKey`='".$_POST['alipayPublicKey']."',`rsaPrivateKey`='".$_POST['rsaPrivateKey']."',`ordername`='".$_POST['ordername']."',`tradeFee` = ".$_POST['tradeFee']." ,`announce`='".$_POST['announce']."',`notice` ='".$_POST['notice']."',`qq`='".$_POST['qq']."',`codepay`=".$_POST['codepay'].",`codepay_id`='".$_POST['codepay_id']."',`codepay_key`='".$_POST['codepay_key']."' WHERE `usr`='".$_SESSION['usr']."' and `pwd`='".$_SESSION['pwd']."' and `ID`=".$_SESSION['fzID'];
    }else{
    $sql = "UPDATE `zhandian` SET `api`=".$_POST['api']." , `nomoney`= ".$_POST['nomoney'].",  `fenzhan` = ".$_POST['fenzhan']." ,`fenzhan_price` = ".$_POST['fenzhan_price'].", `name`='".$_POST['name']."',`appid`='".$_POST['appid']."',`alipayPublicKey`='".$_POST['alipayPublicKey']."',`rsaPrivateKey`='".$_POST['rsaPrivateKey']."',`ordername`='".$_POST['ordername']."',`tradeFee` = ".$_POST['tradeFee']." ,`announce`='".$_POST['announce']."',`notice` ='".$_POST['notice']."',`qq`='".$_POST['qq']."',`pwd`='".$_POST['pwd']."',`codepay`=".$_POST['codepay'].",`codepay_id`='".$_POST['codepay_id']."',`codepay_key`='".$_POST['codepay_key']."' WHERE `usr`='".$_SESSION['usr']."' and `pwd`='".$_SESSION['pwd']."' and `ID`=".$_SESSION['fzID'];
    }
    $rs = $conn->query($sql);
    if(mysqli_error($conn) != ''){
      mysqli_close($conn);
      exit("<script language='javascript'>alert('修改失败!".mysqli_error($conn)."');window.location.href='set.php';</script>");
    }else{
      mysqli_close($conn);
      exit("<script language='javascript'>alert('修改成功!');window.location.href='set.php';</script>");
  }
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
    $nomoney = $row["nomoney"];
    $qq = $row['qq'];
    $codepay = $row['codepay'];
    $codepay_id = $row['codepay_id'];
    $codepay_key = $row['codepay_key'];
    $fenzhan = $row['fenzhan'];
    $fenzhan_price = $row['fenzhan_price'];
    $api = $row['api'];
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
		  <li class="active">
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
<div class="panel-heading"><h3 class="panel-title">系统设置</h3></div>
<div class="panel-body">
  <form action="set.php?set" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-2 control-label">网站名称</label>
	  <div class="col-sm-10"><input type="text" name="name" value="<?php echo $name ?>" class="form-control" required="required"></div>
	</div><br>
	<div class="form-group">
	  <label class="col-sm-2 control-label">客服ＱＱ</label>
	  <div class="col-sm-10"><input type="num" name="qq" value="<?php echo $qq ?>" class="form-control" required="required" onkeyup="value=value.replace(/[^\d]/g,'') " ng-pattern="/[^a-zA-Z]/"/></div>
	</div><br>
	<div class="form-group">
	  <label class="col-sm-2 control-label">手续费(百分比)</label>
	  <div class="col-sm-10"><input type="num" name="tradeFee" value="<?php echo $tradeFee ?>" class="form-control" required="required" onkeyup="value=value.replace(/[^\d]/g,'') " ng-pattern="/[^a-zA-Z]/"/></div>
	</div><br>
    <div class="form-group">
	  <label class="col-sm-2 control-label">余额不足不可下单</label>
	  <div class="col-sm-10"><select class="form-control" name="nomoney"><option value="0" >关闭</option><option value="1" <?php if($nomoney=="1")echo "selected = \"selected\"" ?>>开启</option></select></div>
	</div>
    <div class="form-group">
	  <label class="col-sm-2 control-label">首页公告</label>
	  <div class="col-sm-10"><textarea class="form-control" name="announce" rows="6"><?php echo $announce ?></textarea></div>
	</div>
    <div class="form-group">
	  <label class="col-sm-2 control-label">首页温馨提示</label>
	  <div class="col-sm-10"><textarea class="form-control" name="notice" rows="6"><?php echo $notice ?></textarea></div>
	</div>
    <h4>系统支付设置</h4>
    <div class="form-group">
	  <label class="col-sm-2 control-label">启用码支付</label>
	  <div class="col-sm-10"><select class="form-control" id="codepay" name="codepay" onchange="checkifshow()"><option value="0" >关闭</option><option value="1" <?php if($codepay=="1")echo "selected = \"selected\"" ?>>开启</option></select></div>
	</div>
  <div id="codepay_config" style="<?php if($codepay=="0")echo "display:none" ?>">
    <div class="form-group" id="codepay_id">
	  <label class="col-sm-2 control-label">码支付ID</label>
	  <div class="col-sm-10"><input type="text" name="codepay_id" value="<?php echo $codepay_id ?>" class="form-control" ></div>
	</div><br>
    <div class="form-group" id="codepay_key">
	  <label class="col-sm-2 control-label">码支付KEY</label>
	  <div class="col-sm-10"><input type="text" name="codepay_key" value="<?php echo $codepay_key ?>" class="form-control" ></div>
	</div><br>
  </div>
  <div id="alipay_config" style="<?php if($codepay=="1")echo "display:none" ?>">
    <div class="form-group">
	  <label class="col-sm-2 control-label">付款订单标题</label>
	  <div class="col-sm-10"><input type="text" name="ordername" value="<?php echo $ordername ?>" class="form-control" ></div>
	</div><br>
    <div class="form-group">
	  <label class="col-sm-2 control-label">APPID</label>
	  <div class="col-sm-10"><input type="num" name="appid" value="<?php echo $appid ?>" class="form-control"  onkeyup="value=value.replace(/[^\d]/g,'') " ng-pattern="/[^a-zA-Z]/"/></div>
	</div><br>
    <div class="form-group">
	  <label class="col-sm-2 control-label">支付宝公钥</label>
	  <div class="col-sm-10"><textarea class="form-control" name="alipayPublicKey" rows="6"><?php echo $alipayPublicKey ?></textarea></div>
	</div>
    <div class="form-group">
	  <label class="col-sm-2 control-label">商户私钥</label>
	  <div class="col-sm-10"><textarea class="form-control" name="rsaPrivateKey" rows="6"><?php echo $rsaPrivateKey ?></textarea></div>
	</div>
  </div>
    <div class="form-group">
	  <label class="col-sm-2 control-label">启用API功能</label>
	  <div class="col-sm-10"><select class="form-control" name="api"><option value="0" >关闭</option><option value="1" <?php if($api=="1")echo "selected = \"selected\"" ?>>开启</option></select></div>
	</div>
    <h4>其他设置</h4>
     <div class="form-group">
	  <label class="col-sm-2 control-label">开启自助开通分站</label>
	  <div class="col-sm-10"><select class="form-control" id="fenzhan" name="fenzhan" onchange="checkifshowfenzhan()"><option value="0" >关闭</option><option value="1" <?php if($fenzhan=="1")echo "selected = \"selected\"" ?>>开启</option></select></div>
	</div>
    <div class="form-group" id="fenzhan_open" <?php if($fenzhan=="0")echo "style = \"display:none\"" ?>>
	  <label class="col-sm-2 control-label">分站加价</label>
	  <div class="col-sm-10"><input type="text" name="fenzhan_price" value="<?php echo $fenzhan_price ?>" class="form-control" ></div>
	</div><br>
	<h4>管理员账号设置</h4>
	<div class="form-group">
	  <label class="col-sm-2 control-label">用户名</label>
	  <div class="col-sm-10"><input type="text" name="user" value="<?php echo $_SESSION['usr']?>" class="form-control" required=""></div>
	</div><br>
	<div class="form-group">
	  <label class="col-sm-2 control-label">密码重置</label>
	  <div class="col-sm-10"><input type="text" name="pwd" value="" class="form-control" placeholder="不修改请留空"></div>
	</div><br>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"><br>
	 </div>
	</div>
  </form>
</div>
</div>
    </div>
  </div>
  <script>
    function checkifshow(){
    if(document.getElementById("codepay").value == "0"){
      document.getElementById("codepay_config").style = "display:none";
      document.getElementById("alipay_config").style = "";
    }else{
      document.getElementById("alipay_config").style = "display:none";
      document.getElementById("codepay_config").style = "";
    }
     }
    function checkifshowfenzhan(){
    if(document.getElementById("fenzhan").value == "0"){
      document.getElementById("fenzhan_open").style = "display:none";
    }else{
      document.getElementById("fenzhan_open").style = "";
    }
     }
 </script>
    </body></html>