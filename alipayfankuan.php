<?php
require 'config.php';
header('Content-type:text/html; Charset=utf-8');

//开始验证传入订单是否有效
$outTradeNo = $_GET['tradeno'];
if($outTradeNo == ""){
  echo "未传入订单号";
  exit();
}
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "SELECT * FROM `dingdan` WHERE `ID`=".$outTradeNo;//查询站点信息
$rs = $conn->query($sql);
if(mysqli_num_rows($rs) == 1){
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $zh = $row['buyer_id'];//拉出回款账号
  $ma = $row['status'];//拉出订单状态
  $hkm = $row['fankuan'];//拉出回款状态
  $jine = $row['totalFee'];//拉出订单金额
  $siteID = $row['fromID'];//拉出站点数据
  }else{
  echo "订单号不存在";
  exit();
  }
mysqli_close($conn);

if($ma != "TRADE_SUCCESS" or $hkm != "0" or $jine <= 0){
  echo "订单未支付或已处理或金额异常".$jine;
  exit();
}
                                                                                                                    echo "订单正常未返款,开始操作!<br>";
//该订单可以处理,开始读取分站数据
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "SELECT * FROM `zhandian` WHERE `ID`=".$siteID;//查询站点信息
$rs = $conn->query($sql);
if(mysqli_num_rows($rs) == 1){
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $balance = $row['balance'];//拉出站点余额
  $tradeFee = $row['tradeFee'];//拉出订单状态
  }else{
  $siteID = "1";
  $sql = "SELECT * FROM `zhandian` WHERE `ID`=".$siteID;//查询站点信息
  $rs = $conn->query($sql);
  $balance = $row['balance'];//拉出站点余额
  $tradeFee = $row['tradeFee'];//拉出订单状态
  }
mysqli_close($conn);
                                                                                                                    echo "站点余额:".$balance."<br>";
//计算订单需要支付
$willfk = (float)$jine * (100-(float)$tradeFee)*0.01;
$willfk = round($willfk,2);
                                                                                                                    echo "订单金额:".$willfk."<br>";
//判断站点余额
if($balance < $willfk){
  //不够钱了
  echo "站点余额不足";
  exit();
}

//站点钱够开始扣钱
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "UPDATE `zhandian` SET `balance` = `balance` - ".(string)$willfk." WHERE `ID` = ".$siteID;//扣钱
                                                                                                                    echo "扣钱:".$sql."<br>";
$rs = $conn->query($sql);
$sql = "SELECT * FROM `zhandian` WHERE `ID`=".$siteID;//查询站点信息
$rs = $conn->query($sql);
$row = $rs->fetch_array(MYSQLI_ASSOC);
$balance_after = $row['balance'];
                                                                                                                    echo "扣款后余额:".$balance_after."<br>";
if($balance_after <0){
//没钱打尼玛呢
  $sql = "UPDATE `zhandian` SET `balance` = `balance` + ".(string)$willfk." WHERE `ID` = ".$siteID;//加回去
  $rs = $conn->query($sql);
  mysqli_close($conn);
  exit();
}else{
  $sql = "INSERT INTO `log` (`fzID`, `money_before`, `money_after`, `reason`) VALUES (".$siteID.",".$balance." ,".$balance_after." , '给订单".$outTradeNo."回款')";
                                                                                                                    echo "创建sql:".$sql."<br>";
  $rs = $conn->query($sql);
}

//好,现在站点扣钱好了,开始进行转账!

//首先取设定好的appid和key
$sql = "SELECT * FROM `config` WHERE `k`='appid'";//查询站点信息
$rs = $conn->query($sql);
$row = $rs->fetch_array(MYSQLI_ASSOC);
$appid = $row['v'];//appid

$sql = "SELECT * FROM `config` WHERE `k`='saPrivateKey'";//查询站点信息
$rs = $conn->query($sql);
$row = $rs->fetch_array(MYSQLI_ASSOC);
$saPrivateKey = $row['v'];//rsaPrivateKey

$sql = "SELECT * FROM `config` WHERE `k`='remark'";//查询站点信息
$rs = $conn->query($sql);
$row = $rs->fetch_array(MYSQLI_ASSOC);
$remark = $row['v'];//remark
if($remark == ''){
$remark = '支付宝返款';
}
$sql = "SELECT * FROM `config` WHERE `k`='payer_show_name'";//查询站点信息
$rs = $conn->query($sql);
$row = $rs->fetch_array(MYSQLI_ASSOC);
$payer_show_name = $row['v'];//remark
if($payer_show_name == ''){
$payer_show_name = '支付宝返款';
}


$signType = 'RSA2';       //签名算法类型，支持RSA2和RSA，推荐使用RSA2
$aliPay = new AlipayService($appid,$saPrivateKey);
$result = $aliPay->doPay($willfk,$outTradeNo,$zh,$realName,$remark,$payer_show_name);
$result = $result['alipay_fund_trans_toaccount_transfer_response'];
if($result['code'] && $result['code']=='10000'){
    //成功付款
    $sql= "UPDATE `dingdan` SET `fankuan`='1',`fkFee`=".$willfk." WHERE `ID`=".$outTradeNo;
    echo $sql;
    $rs = $conn->query($sql);
    mysqli_close($conn);
    echo "success";
}else{
    //付款失败
    $sql = "SELECT * FROM `zhandian` WHERE `ID`=".$siteID;//查询站点信息
    $rs = $conn->query($sql);
    $row = $rs->fetch_array(MYSQLI_ASSOC);
    $balance = $row['balance'];
    $sql = "UPDATE `zhandian` SET `balance` = `balance` + ".(string)$willfk." WHERE `ID` = ".$siteID;//加回去
    $rs = $conn->query($sql);
    $sql = "SELECT * FROM `zhandian` WHERE `ID`=".$siteID;//查询站点信息
    $rs = $conn->query($sql);
    $row = $rs->fetch_array(MYSQLI_ASSOC);
    $balance_after = $row['balance'];
    $sql = "INSERT INTO `log` (`fzID`, `money_before`, `money_after`, `reason`) VALUES (".$siteID.",".$balance." ,".$balance_after." , '订单".$outTradeNo."回款失败:".$result['sub_msg']."')";
    $rs = $conn->query($sql);
    mysqli_close($conn);
    echo $result['msg'].' : '.$result['sub_msg'];
}
class AlipayService
{
    protected $appId;
    //私钥值
    protected $rsaPrivateKey;

    public function __construct($appid, $saPrivateKey)
    {
        $this->appId = $appid;
        $this->charset = 'utf8';
        $this->rsaPrivateKey=$saPrivateKey;
    }

    /**
     * 转帐
     * @param float $totalFee 转账金额，单位：元。
     * @param string $outTradeNo 商户转账唯一订单号
     * @param string $remark 转帐备注
     * @return array
     */
    public function doPay($totalFee, $outTradeNo, $zh,$realName,$remark='',$payer)
    {
        //请求参数
        if(is_numeric($zh) and strlen($zh) >11){
          $payee_type = 'ALIPAY_USERID';
        }else{
          $payee_type = 'ALIPAY_LOGONID';
        }
        $payee_type = 
        $requestConfigs = array(
            'out_biz_no'=>$outTradeNo,
            'payee_type'=>$payee_type,
            'payee_account'=>$zh,
            'payee_real_name'=>$realName,  //收款方真实姓名
            'amount'=>$totalFee, //转账金额，单位：元。
            'payer_show_name'=>$payer,  //转账备注（选填）
            'remark'=>$remark
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.fund.trans.toaccount.transfer',             //接口名称
            'format' => 'JSON',
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'biz_content'=>json_encode($requestConfigs),
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $result = $this->curlPost('https://openapi.alipay.com/gateway.do',$commonConfigs);
        $resultArr = json_decode($result,true);
        if(empty($resultArr)){
            $result = iconv('GBK','UTF-8//IGNORE',$result);
            return json_decode($result,true);
        }
        return $resultArr;
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
}
