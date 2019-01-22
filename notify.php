<?php
require 'config.php';
header('Content-type:text/html; Charset=utf-8');
$zfbno= $_POST['trade_no']; //支付宝订单号
$trade = $_POST['out_trade_no'];  //商户内部订单号，就是自己发起订单的那个
$trade_status = $_POST['trade_status'];      //交易状态
$buyer_id = $_POST['buyer_id'];//支付账号唯一ID
$fund_bill_list = $_POST['fund_bill_list']; //支付方式以及金额
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "SELECT * FROM `zhandian` WHERE `sub`='".$_SERVER['HTTP_HOST']."'";//查询站点信息
$rs = $conn->query($sql);
if(mysqli_num_rows($rs) == 1){
  $row = $rs->fetch_array(MYSQLI_ASSOC);
  $alipayPublicKey = $row["alipayPublicKey"];
  }else{
  $siteID = '1';
  $sql = "SELECT * FROM `zhandian` WHERE `ID`=1;";//查询
  $rs = $conn->query($sql);
  $alipayPublicKey = $row["alipayPublicKey"];
  }
mysqli_close($conn);
$aliPay = new AlipayService($alipayPublicKey);
//验证签名
$result = $aliPay->rsaCheck($_POST,$_POST['sign_type']);
if($result===true){
////////////////////////////////////验签成功开始处理///////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "UPDATE `dingdan` SET `status`='".$trade_status."' , `trade_no`='".$zfbno."' ,`fund_bill_list`  = '".$fund_bill_list."',`buyer_id` = '".$buyer_id."'  WHERE `ID`=".$trade;//更改订单信息
$rs = $conn->query($sql);
$sql = "SELECT * FROM `dingdan` WHERE `ID`=".$trade;//查询指定订单号
$rs = $conn->query($sql);
while($row = mysqli_fetch_array($rs)){
  $zh = $row['buyer_id'];//拉出回款账号
  $ma = $row['status'];//拉出订单状态
  $hkm = $row['fankuan'];//拉出回款状态
  $jine = $row['totalFee'];//拉出订单金额
  }
mysqli_close($conn); 
  if($ma == 'TRADE_SUCCESS' and $hkm == '0' and $jine <= 99){ //如果订单状态为成功，回款状态为0，金额小于30

	$url="https://".$_SERVER['HTTP_HOST']."/alipayfankuan.php?tradeno=".$trade;      //回款接口地址
	$content= file_get_contents($url);    //发送一个get请求  
    echo 'success';
    exit();
  }else{
  echo 'success';
  exit();
  }
}else{
  echo 'fail';
  exit();
 }
class AlipayService
{
    //支付宝公钥
    protected $alipayPublicKey;
    protected $charset;

    public function __construct($alipayPublicKey)
    {
        $this->charset = 'utf8';
        $this->alipayPublicKey=$alipayPublicKey;
    }

    /**
     *  验证签名
     **/
    public function rsaCheck($params) {
        $sign = $params['sign'];
        $signType = $params['sign_type'];
        unset($params['sign_type']);
        unset($params['sign']);
        return $this->verify($this->getSignContent($params), $sign, $signType);
    }

    function verify($data, $sign, $signType = 'RSA') {
        $pubKey= $this->alipayPublicKey;
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        ($res) or die('支付宝RSA公钥错误。请检查公钥文件格式是否正确');

        //调用openssl内置方法验签，返回bool值
        if ("RSA2" == $signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        }
//        if(!$this->checkEmpty($this->alipayPublicKey)) {
//            //释放资源
//            openssl_free_key($res);
//        }
        return $result;
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
}