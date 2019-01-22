<?php
require '../config.php';
header('Content-type:text/html; Charset=utf-8');
$trade = $_POST['out_trade_no'];  //商户内部订单号，就是自己发起订单的那个
$siteID= substr($trade,0,strpos($trade,"_"));
$trade_status = $_POST['trade_status'];      //交易状态
$total = $_POST['total_amount'];
if($siteID== ''){
  exit('fuckyou');
  }
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "SELECT * FROM `config` WHERE `k`='alipayPublicKey'";//查询站点信息
$rs = $conn->query($sql);
$row = $rs->fetch_array(MYSQLI_ASSOC);
$alipayPublicKey = $row['v'];
mysqli_close($conn);
$aliPay = new AlipayService($alipayPublicKey);
//验证签名
$result = $aliPay->rsaCheck($_POST,$_POST['sign_type']);
if($result===true){
////////////////////////////////////验签成功开始处理///////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
$conn = new Mysqli($db_config["host"],$db_config["usr"],$db_config["pwd"],$db_config["name"]) or exit();//连接数据库
$conn->set_charset($db_config["charset"]);//设置字符集
$sql = "SELECT * FROM `log` WHERE `reason`='通过支付宝充值余额,订单号".$trade."'";//查询指定订单号
$rs = $conn->query($sql); 
  if(mysqli_num_rows($rs) != 0){
    mysqli_close($conn); 
    exit('success');
    }
  if($trade_status == 'TRADE_SUCCESS'){ //如果订单状态为成功，回款状态为0，金额小于30
    $sql = "SELECT * FROM `zhandian` WHERE `ID`=".$siteID;//查询站点信息
    $rs = $conn->query($sql);
    if(mysqli_num_rows($rs) == 0){
      mysqli_close($conn); 
      exit('success');
      }else{
     $row = $rs->fetch_array(MYSQLI_ASSOC);
     $balance = $row['balance'];//拉出站点余额
     $balance_after = (float)$total / 1.01 + (float)$balance;
      }
     $sql = "INSERT INTO `log` (`fzID`, `money_before`, `money_after`, `reason`) VALUES (".$siteID.",".$balance." ,".$balance_after." , '通过支付宝充值余额,订单号".$trade."')";                                                                                              echo "创建sql:".$sql."<br>";
     $rs = $conn->query($sql);
     $sql = "UPDATE `zhandian` SET `balance` = ".$balance_after." WHERE `ID` = ".$siteID;//扣钱
     $rs = $conn->query($sql);
    mysqli_close($conn); 
    exit('success');
  }else{
   exit('success');
  }
}else{
  exit('fail');
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