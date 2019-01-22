<?php
require '../config.php';
header('Content-type:application/json; Charset=utf-8');

//开始验证传入订单是否有效
$outTradeNo = $_GET['tradeno'];
if($outTradeNo == "")exit('{"code":-1,"msg":"未传入订单号"}');
$jine = $_GET['amount'];
if($jine == "")exit('{"code":-2,"msg":"未传入金额"}');
if($jine < 0.1)exit('{"code":-21,"msg":"单笔付款金额不可小于0.1元"}');
$usr = $_GET['usr'];
if($usr == "")exit('{"code":-3,"msg":"未传入系统账号"}');
$pwd = $_GET['pwd'];
if($pwd == "")exit('{"code":-4,"msg":"未传入系统密码"}');
$zh = $_GET['account'];
if($zh == "")exit('{"code":-777,"msg":"未传入收款人账号"}');
$realName = $_GET['realname'];
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "SELECT * FROM `zhandian` WHERE `usr`='$usr' and `pwd`='$pwd'";//查询站点信息
$rs = $conn->query($sql);
if(mysqli_num_rows($rs) == 1){
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $balance = $row['balance'];
  $siteID = $row['ID'];
  $api = $row['api'];
  $sub = $row['sub'];
  }else{
  mysqli_close($conn);
  exit('{"code":-5,"msg":"账号密码错误"}');
  }
//判断站点余额
if($api != '1')exit('{"code":-666,"msg":"该账号未开启API接口功能,请在管理后台开启"}');
if($_SERVER['HTTP_HOST'] != $sub)exit('{"code":-667,"msg":"调用域名错误,请使用该账号的域名以请求API接口"}');
if($balance < $jine){
  //不够钱了
  exit('{"code":-233,"msg":"余额不足"}');
}

//站点钱够开始扣钱
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "UPDATE `zhandian` SET `balance` = `balance` - ".$jine." WHERE `ID` = ".$siteID;//扣钱
$rs = $conn->query($sql);
$sql = "SELECT * FROM `zhandian` WHERE `ID`=".$siteID;//查询站点信息
$rs = $conn->query($sql);
$row = $rs->fetch_array(MYSQLI_ASSOC);
$balance_after = $row['balance'];
if($balance_after <0){
//没钱打尼玛呢
  $sql = "UPDATE `zhandian` SET `balance` = `balance` + ".(string)$willfk." WHERE `ID` = ".$siteID;//加回去
  $rs = $conn->query($sql);
  mysqli_close($conn);
  exit('{"code":-233,"msg":"余额不足"}');
}else{
  $sql = "INSERT INTO `log` (`fzID`, `money_before`, `money_after`, `reason`) VALUES (".$siteID.",".$balance." ,".$balance_after." , '使用API接口转账,订单号:".$outTradeNo."')";
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
$payer_show_name = '萌哔哩云支付';
}
$signType = 'RSA2';       //签名算法类型，支持RSA2和RSA，推荐使用RSA2
$aliPay = new AlipayService($appid,$saPrivateKey);
$result = $aliPay->doPay($jine,$outTradeNo,$zh,$realName,$remark,$payer_show_name);
$result = $result['alipay_fund_trans_toaccount_transfer_response'];
if($result['code'] && $result['code']=='10000'){
    //成功付款
    mysqli_close($conn);
    exit('{"code":0,"msg":"success"}');
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
    $sql = "INSERT INTO `log` (`fzID`, `money_before`, `money_after`, `reason`) VALUES (".$siteID.",".$balance." ,".$balance_after." , 'API接口订单".$outTradeNo."回款失败:".$result['sub_msg']."')";
    $rs = $conn->query($sql);
    mysqli_close($conn);
    exit('{"code":-999,"msg":"'.$result['sub_msg'].'"}');
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
