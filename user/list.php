<?php
  require "../config.php";
  session_start();
  if (isset($_SESSION['fzID']) && isset($_SESSION['usr']) && isset($_SESSION['pwd']) and $_SESSION['islogin'] == 'true'){
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
          <li class="active">
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
  <div class="container-fluid" style="padding-top:70px;">
    <div class="col-md-12 center-block" style="float: none;">
<form action="list.php" method="GET" class="form-inline">
  <div class="form-group">
    <label>搜索</label>
    <input type="text" class="form-control" name="search" placeholder="请输入关键词">
  </div>
  <button type="submit" class="btn btn-primary">搜索</button>&nbsp;
</form>
	  <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>订单ID</th><th>联系QQ</th><th>交易金额</th><th>返款金额</th><th>反款账号</th><th>创建时间</th><th>支付状态</th><th>返款状态</th><th>操作</th></tr></thead>
          <tbody>
           <?php
            //<tr><td><b>8407486</b></td><td>大姬八云紫</td><td>万道无形x1</td><td></td><td></td><td>2</td><td>2019-01-01 06:30:00</td><td><span onclick="javascript:window.open('http://t.bilibili.com/172779451920024312')" class="btn btn-warning btn-xs">已跳转过</span><br></td></tr>
            if ($_GET['search'] == ''){
             
            $page = $_GET['page'];
            if($page == ''){
              $page = '1';
            }
            $pagesize=20;
            $conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
            $conn->set_charset($db_config["charset"]);//设置字符集
            $sql = "SELECT count(1) FROM `dingdan` WHERE `fromID` = ".$_SESSION['fzID'];
            $rs = $conn->query($sql);
            $row=mysqli_fetch_row($rs);
            $recordcount=$row[0]; 
            if($recordcount==0){
              $pagecount=0;
            }else if($recordcount<$pagesize ||$recordcount==$pagesize){
              $pagecount=1;
            }else if($recordcount%$pagesize==0){
              $pagecount=$recordcount/$pagesize;
            }else{
              $pagecount=(int)($recordcount/$pagesize)+1;
            }
            $sql = "SELECT * FROM `dingdan` WHERE `fromID` = ".$_SESSION['fzID']." order by ID desc limit ".(string)($page-1)*$pagesize.",".$pagesize;
            $rs = $conn->query($sql);
            if(mysqli_num_rows($rs) != 0){
              while($row=mysqli_fetch_assoc($rs)){
                if(empty($row['status'])){
                  $status = "待扫码";
                }elseif($row['status'] == "WAIT_BUYER_PAY"){
                  $status = "待支付";
                }elseif($row['status'] == "TRADE_SUCCESS" and $row['fankuan']=="0"){
                  $status = "已支付";
                  $fk = "待返款";
                  $control = "<span id=\"span_".$row['ID']."\" onclick=\"fuckbudan('".$row['ID']."')\" class=\"btn btn-warning btn-xs\">补单</span>";
                }else{
                  $status = "已支付";
                  $fk = "已返款";
                }
              echo "<tr><td><b>".$row['ID']."</b></td><td>".$row['qq']."</td><td>".$row['totalFee']."</td><td>".$row['fkFee']."</td><td>".$row['buyer_id']."</td><td>".$row['time']."</td><td>".$status."</td><td id=\"fk_".$row['ID']."\">".$fk."</td><td>".$control."</td></tr>";
              $control = "";
              $fk = "";
              }
            }
            }else{
            $page = $_GET['page'];
            if($page == ''){
              $page = '1';
            }
            $pagesize=20;
            $conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
            $conn->set_charset($db_config["charset"]);//设置字符
            $sql = "SELECT count(1) FROM `dingdan` WHERE `fromID` = ".$_SESSION['fzID']." and (`qq` LIKE '%".$_GET["search"]."%' or `trade_no` LIKE '%".$_GET["search"]."%' or `ID`=".$_GET["search"]." or `buyer_id` LIKE '%".$_GET["search"]."%')";   
            if($type == '3') $sql = "SELECT count(1) FROM `dingdan` WHERE `fromID` = ".$_SESSION['fzID']." and `fankuan`='0'  and (`qq` LIKE '%".$_GET["search"]."%' or `trade_no` LIKE '%".$_GET["search"]."%' or `ID`=".$_GET["search"]." or `buyer_id` LIKE '%".$_GET["search"]."%')";   
            $rs = $conn->query($sql);
            $row=mysqli_fetch_row($rs);
            $recordcount=$row[0]; 
            if($recordcount==0){
              $pagecount=0;
            }else if($recordcount<$pagesize ||$recordcount==$pagesize){
              $pagecount=1;
            }else if($recordcount%$pagesize==0){
              $pagecount=$recordcount/$pagesize;
            }else{
              $pagecount=(int)($recordcount/$pagesize)+1;
            }
            $sql = "SELECT * FROM `dingdan` WHERE `fromID` = ".$_SESSION['fzID']." and (`qq`  LIKE '%".$_GET["search"]."%' or `trade_no` LIKE '%".$_GET["search"]."%' or `ID`=".$_GET["search"]." or `buyer_id` LIKE '%".$_GET["search"]."%') order by ID desc limit ".(string)($page-1)*$pagesize.",".$pagesize;       
            if($type == '3')$sql = "SELECT * FROM `dingdan` WHERE `fromID` = ".$_SESSION['fzID']." and `fankuan`='0' and  (`qq`  LIKE '%".$_GET["search"]."%' or `trade_no` LIKE '%".$_GET["search"]."%' or `ID`=".$_GET["search"]." or `buyer_id` LIKE '%".$_GET["search"]."%') order by ID desc limit ".(string)($page-1)*$pagesize.",".$pagesize;       
            $rs = $conn->query($sql);
            if(mysqli_num_rows($rs) != 0){
              while($row=mysqli_fetch_assoc($rs)){
                if(empty($row['status'])){
                  $status = "待扫码";
                }elseif($row['status'] == "WAIT_BUYER_PAY"){
                  $status = "待支付";
                }elseif($row['status'] == "TRADE_SUCCESS" and $row['fankuan']=="0"){
                  $status = "已支付";
                  $fk = "待返款";
                  $control = "<span id=\"span_".$row['ID']."\" onclick=\"fuckbudan('".$row['ID']."')\" class=\"btn btn-warning btn-xs\">补单</span>";
                }else{
                  $status = "已支付";
                  $fk = "已返款";
                }
              echo "<tr><td><b>".$row['ID']."</b></td><td>".$row['qq']."</td><td>".$row['totalFee']."</td><td>".$row['fkFee']."</td><td>".$row['buyer_id']."</td><td>".$row['time']."</td><td>".$status."</td><td id=\"fk_".$row['ID']."\">".$fk."</td><td>".$control."</td></tr>";
              $control = "";
              $fk = "";
              }
            }
              
              
              
            }
              
            ?>
          </tbody>
        </table>
        </div>
        <?php
echo'<ul class="pagination">';
$first=1;
$prev=$page-1;
$next=$page+1;
$last=$pagecount;
if ($page>1)
{
echo '<li><a href="list.php?search='.$search.'&page='.$first.$link.'">首页</a></li>';
echo '<li><a href="list.php?search='.$search.'&page='.$prev.$link.'">&laquo;</a></li>';
} else {
echo '<li class="disabled"><a>首页</a></li>';
echo '<li class="disabled"><a>&laquo;</a></li>';
}
for ($i=1;$i<$page;$i++)
echo '<li><a href="list.php?search='.$search.'&page='.$i.$link.'">'.$i .'</a></li>';
echo '<li class="disabled"><a>'.$page.'</a></li>';
if($pagecount>=10)$pagecount=10;
for ($i=$page+1;$i<=$pagecount;$i++)
echo '<li><a href="list.php?search='.$search.'&page='.$i.$link.'">'.$i .'</a></li>';
echo '';
if ($page<$pagecount)
{
echo '<li><a href="list.php?search='.$search.'&page='.$next.$link.'">&raquo;</a></li>';
echo '<li><a href="list.php?search='.$search.'&page='.$last.$link.'">尾页</a></li>';
} else {
echo '<li class="disabled"><a>&raquo;</a></li>';
echo '<li class="disabled"><a>尾页</a></li>';
}
echo'</ul>';
#分页
?>
      
  </div>
</div>
<script src="//lib.baomitu.com/layer/2.3/layer.js"></script>
<script>
function fuckbudan(id){
	$.get("budan.php?tradeno="+id, function(data) {
        if(data == "补单成功"){
          document.getElementById("span_"+id).style.display = "none";
          document.getElementById("fk_"+id).innerText = "已返款";
        }else{
		alert(data);
        }
	}, 'text');
}
</script>
  </body>