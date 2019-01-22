<?php
require_once './other/gt/class.geetestlib.php';
require_once './other/gt/config.php';
require 'config.php';
header('Content-type:text/html; Charset=utf-8');
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
//自定义配置信息
//$account = '734430160@qq.com'; //回款账号，通常通过post传输
$account = $_POST['account']; //qq号
$sj = date('Y-m-d H:i:s');
/*** 请填写以下配置信息 ***/
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "SELECT * FROM `zhandian` WHERE `sub`='".$_SERVER['HTTP_HOST']."'and `codepay` = '0'";//查询站点信息
$rs = $conn->query($sql);
if(mysqli_num_rows($rs) == 1){
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $siteID = $row["ID"];
  $siteName = $row["name"];
  $qq = $row["qq"];
  $appid = $row["appid"];
  $orderName = $row["ordername"];
  $rsaPrivateKey = $row["rsaPrivateKey"];
  $balance = $row["balance"];
  $tradeFee = $row["tradeFee"];
  $nomoney = $row['nomoney'];
  }else{
  echo "未找到站点信息,请刷新后再试!";
  }
mysqli_close($conn); 
if($rsaPrivateKey == '' or $appid == ''){
  echo '<script type="text/javascript">alert("错误:站点未设置appid和私钥!");window.location = "https://'.$_SERVER['HTTP_HOST'].'";</script>';
  exit();
  }
$notifyUrl = 'https://'.$_SERVER['HTTP_HOST'].'/notify.php';     //付款成功后的异步回调地址
$outTradeNo = date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 10, 13), 1))), 0, 4);     //你自己的商品订单号，不能重复
$payAmount = $_POST['total'];          //付款金额，单位:元
$payYuan = (int)$payAmount;
if($payYuan >= 1000 or $payYuan <= 0)
{
  echo '<script type="text/javascript">alert("错误:付款金额不可大于1000且不可小于0!");window.location = "https://'.$_SERVER['HTTP_HOST'].'";</script>';
  exit();
}
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


//$payAmount = '0.01';
if($orderName == ''){
$orderName = '门店消费';    //订单标题
  }
$signType = 'RSA2';         //签名算法类型，支持RSA2和RSA，推荐使用RSA2
/*** 配置结束 ***/
$aliPay = new AlipayService();
$aliPay->setAppid($appid);
$aliPay->setNotifyUrl($notifyUrl);
$aliPay->setRsaPrivateKey($rsaPrivateKey);
$aliPay->setTotalFee($payAmount);
$aliPay->setOutTradeNo($outTradeNo);
$aliPay->setOrderName($orderName);
$weberror = $aliPay->doPay();
$result2 = json_decode($weberror,true);
$result = $result2['alipay_trade_precreate_response'];
if($result['code'] && $result['code']=='10000' and $payAmount >= 0.1){
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);
$sql = "INSERT INTO `dingdan` (`ID`, `qq`, `status`, `fankuan`, `totalFee`, `fkFee`, `fromID`) VALUES (".$outTradeNo.", '".$account."', NULL, 0,'".$payAmount."'  , 0, '".$siteID."' )";
$rs = $conn->query($sql);
$sql = "SELECT * FROM `dingdan` WHERE `ID` = ".$outTradeNo;
$rs = $conn->query($sql);
if(mysqli_num_rows($rs) != 1){
  echo '<script type="text/javascript">alert("写入数据库失败!");window.location = "https://'.$_SERVER['HTTP_HOST'].'";</script>';   
  exit();
}
    //生成二维码
    $lj = $result['qr_code'];
    if($lj == ''){
    echo '订单生成失败';
    echo '<script type="text/javascript">alert("订单生成失败:'.$weberror.'");window.location = "https://'.$_SERVER['HTTP_HOST'].'";</script>';   
    exit();
    }
    $url = 'https://www.kuaizhan.com/common/encode-png?large=true&data='.$result['qr_code'];
    //echo "<img src='{$url}' style='width:300px;'><br>";
    //echo '二维码内容：'.$result['qr_code'];
  

  
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
}else{
    //echo $result['msg'].' : '.$result['sub_msg'];
  	echo '<script type="text/javascript">alert("订单生成失败:'.$weberror.'");window.location = "https://'.$_SERVER['HTTP_HOST'].'";</script>';    
    exit();
    }
class AlipayService
{
    protected $appId;
    protected $notifyUrl;
    protected $charset;
    //私钥值
    protected $rsaPrivateKey;
    protected $totalFee;
    protected $outTradeNo;
    protected $orderName;
    public function __construct()
    {
        $this->charset = 'utf8';
    }
    public function setAppid($appid)
    {
        $this->appId = $appid;
    }
    public function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
    }
    public function setRsaPrivateKey($saPrivateKey)
    {
        $this->rsaPrivateKey = $saPrivateKey;
    }
    public function setTotalFee($payAmount)
    {
        $this->totalFee = $payAmount;
    }
    public function setOutTradeNo($outTradeNo)
    {
        $this->outTradeNo = $outTradeNo;
    }
    public function setOrderName($orderName)
    {
        $this->orderName = $orderName;
    }
    /**
     * 发起订单
     * @return array
     */
    public function doPay()
    {
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=>$this->outTradeNo,
            'total_amount'=>$this->totalFee, //单位 元
            'subject'=>$this->orderName,  //订单标题
            'timeout_express'=>'5m',      //该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m。
          	'disable_pay_channels'=>'' //credit_group,coupon禁用信用付款
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.trade.precreate',             //接口名称
            'format' => 'JSON',
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'notify_url' => $this->notifyUrl,
            'biz_content'=>json_encode($requestConfigs),

        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $result = $this->curlPost('https://openapi.alipay.com/gateway.do',$commonConfigs);
        return $result;
        
    }
    public function generateSign($params, $signType = "RSA") {
        return $this->sign($this->getSignContent($params), $signType);
    }
    protected function sign($data, $signType = "RSA") {
        $priKey=$this->rsaPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }
    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;
        return false;
    }
    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, $this->charset);
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }
    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {
        if (!empty($data)) {
            $fileType = $this->charset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
        return $data;
    }
    public function curlPost($url = '', $postData = '', $options = array())
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}?>
<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo $siteName ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0" />
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">        <style>
            .container{width: 100%; max-width: 600px;}
            .mtm{margin-top: 10px;}
            #QRcode .pay-top{padding: 15px 0px;background: #FAFAFA;border: 2px #009900 dashed;margin: 20px 0px;font-family: 微软雅黑;}
        .spinner {
  margin: 100px auto;
  width: 50px;
  height: 60px;
  text-align: center;
  font-size: 10px;
}
 
.spinner > div {
  background-color: #67CF22;
  height: 100%;
  width: 6px;
  display: inline-block;
   
  -webkit-animation: stretchdelay 1.2s infinite ease-in-out;
  animation: stretchdelay 1.2s infinite ease-in-out;
}
 
.spinner .rect2 {
  -webkit-animation-delay: -1.1s;
  animation-delay: -1.1s;
}
 
.spinner .rect3 {
  -webkit-animation-delay: -1.0s;
  animation-delay: -1.0s;
}
 
.spinner .rect4 {
  -webkit-animation-delay: -0.9s;
  animation-delay: -0.9s;
}
 
.spinner .rect5 {
  -webkit-animation-delay: -0.8s;
  animation-delay: -0.8s;
}
 
@-webkit-keyframes stretchdelay {
  0%, 40%, 100% { -webkit-transform: scaleY(0.4) } 
  20% { -webkit-transform: scaleY(1.0) }
}
 
@keyframes stretchdelay {
  0%, 40%, 100% {
    transform: scaleY(0.4);
    -webkit-transform: scaleY(0.4);
  }  20% {
    transform: scaleY(1.0);
    -webkit-transform: scaleY(1.0);
  }
}
        </style>
        <link rel="stylesheet" href="../layui/css/layui.css"><link rel="stylesheet" href="../layui/css/layui.mobile.css">
        <script type="text/javascript" src="../layui/layui.js"></script><script type="text/javascript" src="../layui/layui.all.js"></script>
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
    <div id="QRcode" class="container mtm">
        <div class="text-center pay-top"><p class="text-danger">请用手机支付宝扫码以完成支付.</p></div>
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <span class="pull-left">订单号：<?php echo $outTradeNo ?></span>
                <span class="pull-right">金额：<?php echo $payAmount ?> 元,预计返款 <?php echo $willfk ?> 元</span>
            </div>
            <div class="panel-body text-center"><img src="https://www.kuaizhan.com/common/encode-png?large=true&data=<?php echo $url ?>" style="" /></div>
            <div class="panel-footer text-success text-center">该订单有效期为5分钟，请尽快支付<br>若您为手机用户，请<a href="alipays://platformapi/startapp?saId=10000007&qrcode=<?php echo $lj ?>">点我跳转支付</a></div>
        </div>
        <!--div class="progress" style="display: none;">
            <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="20" aria-valuemin="20" aria-valuemax="100" style="width: 1%"></div>
        </div-->

      <div class="spinner" id="box2">
  <div class="rect1"></div>
  <div class="rect2"></div>
  <div class="rect3"></div>
  <div class="rect4"></div>
        <div class="rect5"></div><p>等待扫码</p>
</div>
      
        <div class="alert alert-info" role="warning" style="display:none" id="box">
          <p>支付宝账号:</p><p id="account" style="color:red">***</p><br/><p style="color:red">付款后资金会自动转至该支付宝中!</p><p>用户已扫码，请在手机上继续完成支付</p>
      </div>
        <div class="alert alert-info" role="warning">
            <p>付款后资金将会自动转至付款的支付宝内</p>
            <p>您的QQ账号是:<b><?php echo $account ?></b></p>
            <p>若QQ账号有误请您立即关闭本窗口停止支付</p>
            <b>若回款失败请在3小时内联系客服</b>
            <p>如有问题请联系 QQ：<?php echo $qq?>  ，超时不作处理</p>
        </div>

    </div>
    <script>
        $(function () {
            $(".progress").show();
            var i = 0;
            setInterval(function () {
                i++;
                $(".progress .progress-bar").attr("aria-valuenow", i * 10);
                $(".progress .progress-bar").css("width", (i * 10) + "%");
                if (i >= 10) {
                    i = 1;
                }
            }, 2000);

            setInterval(function () {
                var url = "query.php?tradeno=<?php echo $outTradeNo ?>";
                $.getJSON(url, function (result) {
                    if (result.code == 1) {
                      //alert('支付成功');
                      layer.msg('支付成功');
                      setTimeout(function () { this.location.href = "https://<?php echo $_SERVER['HTTP_HOST'] ?>/" }, 2000);
                        //window.location.href = 'success/';
                    }else if (result.code == 2){
                      var box1=document.getElementById('box');
                      var box2=document.getElementById('box2');
                      var box3=document.getElementById('account');
                      box1.style.display='';
                      box2.style.display='none';
                      box3.innerText= result.payer
                    }
                });
            }, 3000);

        });
    </script>
    </body>
    </html>
