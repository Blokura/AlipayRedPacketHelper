<?php
require 'config.php';
header('Content-type:text/html; Charset=utf-8');
/*** 请填写以下配置信息 ***/
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "SELECT * FROM `zhandian` WHERE `sub`='".$_SERVER['HTTP_HOST']."';";//查询站点信息
$rs = $conn->query($sql);
if(mysqli_num_rows($rs) == 1){
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $siteID = $row["ID"];
  $appid = $row["appid"];
  $rsaPrivateKey = $row["rsaPrivateKey"];
  }else{
  $siteID = '1';
  $sql = "SELECT * FROM `zhandian` WHERE `ID`=1;";//查询
  $rs = $conn->query($sql);
  $appid = $row["appid"];
  $rsaPrivateKey = $row["rsaPrivateKey"];
  }
mysqli_close($conn); 
//$notifyUrl = 'http://www.xxx.com/alipay/notify.php';     //付款成功后的异步回调地址
$outTradeNo = $_GET['tradeno'];     //你自己的商品订单号，不能重复
$signType = 'RSA2';         //签名算法类型，支持RSA2和RSA，推荐使用RSA2
//定义json数据 
  $raw_success = array('code' => 1, 'msg' => '支付完成');
  $raw_fail = array('code' => 0, 'msg' => 'no pay or error');
  $res_success = json_encode($raw_success);
  $res_fail = json_encode($raw_fail);
/*** 配置结束 ***/
$aliPay = new AlipayService();
$aliPay->setAppid($appid);
//$aliPay->setNotifyUrl($notifyUrl);
$aliPay->setRsaPrivateKey($rsaPrivateKey);
//$aliPay->setTotalFee($payAmount);
$aliPay->setOutTradeNo($outTradeNo);
//$aliPay->setOrderName($orderName);
$result = $aliPay->doQuery();
$result = $result['alipay_trade_query_response'];
if($result['trade_status'] && $result['trade_status']=='TRADE_SUCCESS'){
  echo $res_success;
}else if($result['trade_status'] && $result['trade_status']=='WAIT_BUYER_PAY'){
  $conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
  $conn->set_charset($db_config["charset"]);//设置字符集
  $sql = "UPDATE `dingdan` SET `status` = 'WAIT_BUYER_PAY' WHERE `ID`=".$outTradeNo;//查询站点信息
  $rs = $conn->query($sql);
  mysqli_close($conn);
  $raw_waiting = array('code' => 2, 'msg' => 'waiting pay' ,'payer' => $result['buyer_logon_id']);
  $res_waiting = json_encode($raw_waiting);
  echo $res_waiting;
}else{
    echo $res_fail;
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
    public function doQuery()
    {
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=>$this->outTradeNo,
            //'total_amount'=>$this->totalFee, //单位 元
            //'subject'=>$this->orderName,  //订单标题
            //'timeout_express'=>'2h'       //该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m。
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.trade.query',             //接口名称
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
        return json_decode($result,true);
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