SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE IF NOT EXISTS `config` (
  `k` varchar(32) NOT NULL,
  `v` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `config` (`k`, `v`) VALUES
('admin_lastlogin', ''),
('admin_pwd', '123456'),
('admin_user', 'zhinai'),
('alipayPublicKey', ''),
('appid', ''),
('houtaiannounce', '<div class="alert alert-info">尊敬的站长您好,欢迎进入红包助手后台管理中心!</div>\r\n<div class="alert alert-danger"><h4>服务说明</h4><br/>每天4:30将会清理数据库，清除24小时以前的所有未支付订单，请码支付的用户务必在当日补单，过期不候！</div>'),
('payer_show_name', '萌哔哩云支付'),
('price', '2'),
('remark', '支付宝返款'),
('saPrivateKey', '');

CREATE TABLE IF NOT EXISTS `dingdan` (
  `ID` bigint(20) NOT NULL COMMENT '本地商户订单号',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '生成订单时间',
  `qq` text NOT NULL COMMENT '联系QQ',
  `status` text COMMENT '支付状态',
  `fankuan` int(1) NOT NULL DEFAULT '0' COMMENT '返款状态',
  `totalFee` decimal(10,2) NOT NULL DEFAULT '0.10' COMMENT '交易金额',
  `fkFee` decimal(10,2) NOT NULL DEFAULT '0.10' COMMENT '返款金额',
  `fromID` int(11) NOT NULL DEFAULT '1' COMMENT '站点ID',
  `trade_no` text NOT NULL COMMENT '支付宝订单号',
  `fund_bill_list` text NOT NULL COMMENT '用户付款渠道和金额',
  `buyer_id` text NOT NULL COMMENT '用户支付宝uid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `log` (
  `ID` int(11) NOT NULL,
  `fzID` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `money_before` decimal(10,2) NOT NULL,
  `money_after` decimal(10,2) NOT NULL,
  `reason` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zhandian` (
  `ID` int(11) NOT NULL COMMENT '站点ID',
  `name` text NOT NULL COMMENT '站点名称',
  `usr` text NOT NULL COMMENT '站点用户名',
  `pwd` text NOT NULL COMMENT '站点密码',
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '站点状态 0正常 1冻结',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '站点余额',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '到期时间',
  `sub` text NOT NULL COMMENT '站点绑定二级域名',
  `codepay` int(1) NOT NULL DEFAULT '0' COMMENT '是否启用码支付 1启用',
  `codepay_id` text NOT NULL COMMENT '码支付ID',
  `codepay_key` text NOT NULL COMMENT '码支付KEY',
  `appid` text NOT NULL COMMENT '商家应用appid',
  `alipayPublicKey` mediumtext NOT NULL COMMENT '支付宝公钥',
  `rsaPrivateKey` mediumtext NOT NULL COMMENT '商户私钥',
  `ordername` text NOT NULL COMMENT '生成的订单标题',
  `tradeFee` int(3) NOT NULL DEFAULT '0' COMMENT '手续费百分比',
  `announce` mediumtext NOT NULL COMMENT '首页公告代码',
  `notice` mediumtext NOT NULL COMMENT '首页温馨提示代码',
  `qq` text NOT NULL,
  `api` int(1) NOT NULL DEFAULT '0' COMMENT '是否开启API',
  `fenzhan` int(1) NOT NULL DEFAULT '0' COMMENT '是否开启自助开通分站',
  `fenzhan_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '自助开通加价',
  `nomoney` int(1) NOT NULL DEFAULT '1' COMMENT '余额不足不可下单'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

INSERT INTO `zhandian` (`ID`, `name`, `usr`, `pwd`, `status`, `balance`, `time`, `sub`, `codepay`, `codepay_id`, `codepay_key`, `appid`, `alipayPublicKey`, `rsaPrivateKey`, `ordername`, `tradeFee`, `announce`, `notice`, `qq`, `api`, `fenzhan`, `fenzhan_price`, `nomoney`) VALUES
(1, '支付宝红包助手', 'admin', 'admin', 0, '0.00', '2019-12-31 16:00:00', '', 0, '', '', '', '', '', '', 0, '<div class="col-xs-12 col-sm-10 col-md-8 center-block" style="float: none;">\r\n<div class="panel panel-primary">\r\n<div class="panel-heading"><h3 class="panel-title">系统公告</h3></div>\r\n<div class="panel-body">\r\n<div class="alert alert-info">欢迎使用萌哔哩红包助手!</div>\r\n<div class="alert alert-danger"><h4>说明</h4><br/>开通分站,自定义个人收款码,系统自动反款,开通联系QQ 12345678,详情可见<a href="https://shimo.im/docs/Yt42CTZObRsDYhSy/">点我查看</a></div>\r\n<div class="alert alert-warning"><h4>操作步骤</h4><br/>输入金额和QQ创建订单 → 支付宝扫描二维码或点击链接跳转 → 付款 → 付款金额会自动转回付款的支付宝</div>\r\n</div>\r\n</div>\r\n</div>', '    <div class="col-xs-12 col-sm-10 col-md-8 center-block" style="float: none;">\r\n<div class="panel panel-primary">\r\n<div class="panel-heading"><h3 class="panel-title">温馨提示</h3></div>\r\n<div class="panel-body">\r\n<font color="red">1.<b>付款后返款会自动返还至付款的支付宝中</b></font>，若1分钟没到可联系客服查询<br/>2.若您还未领取红包，请复制 “1071832” 后打开支付宝，搜索该数字即可<br/>\r\n  3.如果您发现返款金额与您的预计不符，请查看是否为自己操作错误，很多都是每日奖励多次做结果发现没有多返款反而扣了手续费<br/>4.客服QQ <font color="red">12345678</font>，服务时间8:00~23:00，开门见山直接说明情况并附带上商家订单号（非支付宝订单号，可在账单-详情中看到）\r\n</div>\r\n</div>\r\n</div>', '', 0, 0, '0.00', 1);


ALTER TABLE `config`
  ADD PRIMARY KEY (`k`);

ALTER TABLE `dingdan`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `log`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `zhandian`
  ADD PRIMARY KEY (`ID`);


ALTER TABLE `dingdan`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '本地商户订单号';
ALTER TABLE `log`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `zhandian`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '站点ID',AUTO_INCREMENT=2;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
