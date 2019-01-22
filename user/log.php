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
	  <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>订单ID</th><th>交易时间</th><th>交易金额</th><th>变动前余额</th><th>变动后余额</th><th>说明</th></thead>
          <tbody>
           <?php
            //<tr><td><b>8407486</b></td><td>大姬八云紫</td><td>万道无形x1</td><td></td><td></td><td>2</td><td>2019-01-01 06:30:00</td><td><span onclick="javascript:window.open('http://t.bilibili.com/172779451920024312')" class="btn btn-warning btn-xs">已跳转过</span><br></td></tr>
            $page = $_GET['page'];
            if($page == ''){
              $page = '1';
            }
            $pagesize=20;
            $conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
            $conn->set_charset($db_config["charset"]);//设置字符集
            $sql = "SELECT count(1) FROM `log` WHERE `fzID` = ".$_SESSION['fzID'];
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
            $sql = "SELECT * FROM `log` WHERE `fzID` = ".$_SESSION['fzID']." order by ID desc limit ".(string)($page-1)*$pagesize.",".$pagesize;
            $rs = $conn->query($sql);
            while($row=mysqli_fetch_assoc($rs)){
              $tradeFee = (float)$row['money_after'] - (float)$row['money_before'];
              $tradeFee = number_format($tradeFee, 2, '.', '');
              echo "<tr><td><b>".$row['ID']."</b></td><td>".$row['time']."</td><td>".(string)$tradeFee."</td><td>".$row['money_before']."</td><td>".$row['money_after']."</td><td>".$row['reason']."</td>";
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
echo '<li><a href="log.php?page='.$first.$link.'">首页</a></li>';
echo '<li><a href="log.php?page='.$prev.$link.'">&laquo;</a></li>';
} else {
echo '<li class="disabled"><a>首页</a></li>';
echo '<li class="disabled"><a>&laquo;</a></li>';
}
for ($i=1;$i<$page;$i++)
echo '<li><a href="log.php?page='.$i.$link.'">'.$i .'</a></li>';
echo '<li class="disabled"><a>'.$page.'</a></li>';
if($pagecount>=10)$pagecount=10;
for ($i=$page+1;$i<=$pagecount;$i++)
echo '<li><a href="log.php?page='.$i.$link.'">'.$i .'</a></li>';
echo '';
if ($page<$pagecount)
{
echo '<li><a href="log.php?page='.$next.$link.'">&raquo;</a></li>';
echo '<li><a href="log.php?page='.$last.$link.'">尾页</a></li>';
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