<?php
/**
 * Filename: wxpay.php  
 *
 *
 */
if (! defined ( 'IN_ECS' )) {
	die ( 'Hacking attempt' );
}

@$payment_lang = ROOT_PATH . 'languages/' . $GLOBALS ['_CFG'] ['lang'] . '/payment/wxpay.php';

if (file_exists ( $payment_lang )) {
	global $_LANG;
	
	include_once ($payment_lang);
}

/* 模块的基本信息 */
if (isset ( $set_modules ) && $set_modules == TRUE) {
	$i = isset ( $modules ) ? count ( $modules ) : 0;
	
	/* 代码 */
	$modules [$i] ['code'] = basename ( __FILE__, '.php' );
	
	/* 描述对应的语言项 */
	$modules [$i] ['desc'] = 'wxpay_desc';
	
	/* 是否支持货到付款 */
	$modules [$i] ['is_cod'] = '0';
	
	/* 是否支持在线支付 */
	$modules [$i] ['is_online'] = '1';
	
	/* 作者 */
	$modules [$i] ['author'] = 'CAIYA TEAM';
	
	/* 网址 */
	$modules [$i] ['website'] = 'http://wx.qq.com';
	
	/* 版本号 */
	$modules [$i] ['version'] = '0.0.1';
	
	/* 配置信息 */
	$modules [$i] ['config'] = array (
			array (
					'name' => 'wxpay_app_id',
					'type' => 'text',
					'value' => '' 
			),
			array (
					'name' => 'wxpay_app_secret',
					'type' => 'text',
					'value' => '' 
			),
			array (
					'name' => 'wxpay_mchid',
					'type' => 'text',
					'value' => '' 
			),
			array (
					'name' => 'wxpay_key',
					'type' => 'text',
					'value' => '' 
			),
			array (
					'name' => 'notifyurl',
					'type' => 'text',
					'value' => '' 
			),
			array (
					'name' => 'successurl',
					'type' => 'text',
					'value' => '' 
			) 
	);
	
	return;
}
class WxPayConf_pub {
	public $wxpay_app_id;
	public $wxpay_app_secret;
	public $wxpay_mchid;
	public $wxpay_key;
	public $notifyurl;
	public $successurl;
	public $curltimeout;
	function __construct() {
		$payment = get_payment ( 'wxpay' );
		if (isset ( $payment )) {
			$this->wxpay_app_id = $payment ['wxpay_app_id'];
			$this->wxpay_app_secret = $payment ['wxpay_app_secret'];
			$this->wxpay_mchid = $payment ['wxpay_mchid'];
			$this->wxpay_key = $payment ['wxpay_key'];
			$this->notifyurl = $payment ['notifyurl'];
			$this->successurl = $payment ['successurl'];
		}
		// var_dump($this->wxpay_app_id);
	}
	
	/*
	 * //=======【基本信息设置】===================================== //微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看 const APPID = 'wx4b56d1cfaa3a5574'; //wx4b56d1cfaa3a5574 //wx7bdd4eebef11c1a5 mp.wx //受理商ID，身份标识 const MCHID = '10018826'; //商户支付密钥Key。审核通过后，在微信发送的邮件中查看 const KEY = 'zxsaqwedfcvgthg1247875414771fads'; //JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看 const APPSECRET = 'fcc5c94e31c6cd4d588195468d27f96f'; //15e36043bab60d5645b368cc7b9299f9 //=======【JSAPI路径设置】=================================== //获取access_token过程中的跳转uri，通过跳转将code传入jsapi支付页面 //const JS_API_CALL_URL = 'http://mp.shanmao.me/xm9902/mobile/order.php?act=done'; //=======【证书路径设置】===================================== //证书路径,注意应该填写绝对路径 const SSLCERT_PATH = 'D:\wnmp\www\xm9914\wxpay/cacert/apiclient_cert.pem'; const SSLKEY_PATH = 'D:\wnmp\www\xm9914\wxpay/cacert/apiclient_key.pem'; //=======【异步通知url设置】=================================== //异步通知url，商户根据实际开发过程设定 const NOTIFY_URL = 'http://121.40.148.177/xm9914/wxpay/demo/notify_url.php'; //支付成功后跳转网址： const ZFSUCCESS_URL = 'http://121.40.148.177/xm9914/mobile/user.php?act=order_info&id='; //=======【curl超时设置】=================================== //本例程通过curl使用HTTP POST方法，此处可修改其超时时间，默认为30秒 const CURL_TIMEOUT = 30;
	 */
}

include_once (ROOT_PATH . "wxpay/WxPayPubHelper.php");

/**
 * 类
 */
class wxpay {
	public $wxpay_app_id = '';
	public $wxpay_app_secret = '';
	public $wxpay_mchid = '';
	public $wxpay_key = '';
	public $wxpay_paySignKey = '';
	public $background_notify_url = ''; // /后台支付成功通知url，需要给微信返回success
	public $pay_success_url = 'http://{$_SERVER["HTTP_HOST"]}/wxpay/payfeedback/index.php'; // /支付成功后前台展示给用户的地址
	private $_redis = null;
	function __construct() {
		$payment = get_payment ( 'wxpay' );
		if (isset ( $payment )) {
			$this->wxpay_app_id = $payment ['wxpay_app_id'];
			$this->wxpay_app_secret = $payment ['wxpay_app_secret'];
			$this->wxpay_mchid = $payment ['wxpay_mchid'];
			$this->wxpay_key = $payment ['wxpay_key'];
			$this->wxpay_paySignKey = $payment ['wxpay_paySignKey'];
			$this->background_notify_url = $payment ['notifyurl'];
			$this->pay_success_url = $payment ['successurl'];
		}
	}
	
	/**
	 * 生成支付代码
	 * 
	 * @param array $order
	 *        	订单信息
	 * @param array $payment
	 *        	支付方式信息
	 */
	function get_code($order, $payment) 
	{
		$conf = new WxPayConf_pub ();
		//微信浏览器的JSAPI支付\
		if (strpos ( $_SERVER ['HTTP_USER_AGENT'], "MicroMessenger" )) 
		{
			$tools = new JsApiPay();
			//$openid = $tools->GetOpenid();
			//@$openid = $_COOKIE ['sopenid'];
			$openid='oKIVft4Hk9gNczpAyszvsIYeGklU';//测试
			//var_dump($openId);die();
			$unifiedOrder = new UnifiedOrder_pub ();
			$returnrul = $conf->successurl . $order ["order_id"];
			$unifiedOrder->setParameter ( "openid", "$openid" ); // openid
			$unifiedOrder->setParameter ( "body", $order ['order_sn'] ); // 商品描述
			$timeStamp = time ();
			// $out_trade_no = WxPayConf_pub::APPID."$timeStamp";
			$unifiedOrder->setParameter ( "out_trade_no", $order ['order_sn'] ); // 商户订单号
			$unifiedOrder->setParameter ( "total_fee", intval ( $order ['order_amount'] * 100 ) ); // 总金额
			$unifiedOrder->setParameter ( "notify_url", $conf->notifyurl ); // 通知地址
			$unifiedOrder->setParameter ( "trade_type", "JSAPI" ); // 交易类型
			// 非必填参数，商户可根据实际情况选填
			// $unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
			// $unifiedOrder->setParameter("device_info","XXXX");//设备号
			// $unifiedOrder->setParameter("attach","XXXX");//附加数据
			// $unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
			// $unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
			// $unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
			// $unifiedOrder->setParameter("openid","XXXX");//用户标识
			// $unifiedOrder->setParameter("product_id","XXXX");//商品ID
			$prepay_id = $unifiedOrder->getPrepayId ();
			$jsApi = new JsApi_pub ();
			$jsApi->setPrepayId ( $prepay_id );
			$jsApiParameters = $jsApi->getParameters ();
			$pay_online = $jsApi->getbutton ( $jsApiParameters, $returnrul );
			return $pay_online;
		} 
		//扫码支付
		else 
		{
			$native = new NativeLink_pub();
			$native->setParameter ( "body", $order ['order_sn'] ); // 商品描述
			$timeStamp = time ();
			// $out_trade_no = WxPayConf_pub::APPID."$timeStamp";
			$native->setParameter ( "appid", $conf->wxpay_app_id ); // openid
			$native->setParameter ( "out_trade_no", $order ['order_sn'] ); // 商户订单号
			$native->setParameter ( "total_fee", intval ( $order ['order_amount'] * 100 ) ); // 总金额
			$native->setParameter ( "notify_url", $this->background_notify_url); // 通知地址
			$native->setParameter ( "trade_type", "NATIVE" ); // 交易类型
			$native->setParameter("product_id",$order['order_id']);
			$url=$native->getUrl();
			return $url;
// 			// 非必填参数，商户可根据实际情况选填
// 			// $unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
// 			// $unifiedOrder->setParameter("device_info","XXXX");//设备号
// 			// $unifiedOrder->setParameter("attach","XXXX");//附加数据
// 			 $unifiedOrder->setParameter("time_start",date ( "YmdHis" ));//交易起始时间
// 			 $unifiedOrder->setParameter("time_expire",date ( "YmdHis", time () + 600 ));//交易结束时间
// 			// $unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
// 			// $unifiedOrder->setParameter("openid","XXXX");//用户标识
// 			// $unifiedOrder->setParameter("product_id","XXXX");//商品ID
		}
	}
	
	/**
	 * 是否支持微信支付
	 * 
	 * @return bool
	 */
	public function is_show_pay($agent) {
		$ag1 = strstr ( $agent, "MicroMessenger" );
		$ag2 = explode ( "/", $ag1 );
		$ver = floatval ( $ag2 [1] );
		if ($ver < 5.0 || empty ( $aid )) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * 接受通知处理订单。
	 * 
	 * @param undefined $log_id
	 *        	20141125
	 *        	
	 */
	function respond() {
		$notify = new Notify_pub ();
		
		// 存储微信的回调
		$xml = $GLOBALS ['HTTP_RAW_POST_DATA'];
		$notify->saveData ( $xml );
		
		if ($notify->checkSign () == FALSE) {
			$notify->setReturnParameter ( "return_code", "FAIL" ); // 返回状态码
			$notify->setReturnParameter ( "return_msg", "签名失败" ); // 返回信息
		} else {
			$notify->setReturnParameter ( "return_code", "SUCCESS" ); // 设置返回码
		}
		$returnXml = $notify->returnXml ();
		echo $returnXml;
		
		$log_ = new Log_ ();
		$log_name = ROOT_PATH . "wxpay/demo/notify_url.log"; // log文件路径
		if ($notify->checkSign () == TRUE) {
			if ($notify->data ["return_code"] == "FAIL") {
				// 此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result ( $log_name, "【通信出错】:\n" . $xml . "\n" );
			} elseif ($notify->data ["result_code"] == "FAIL") {
				// 此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result ( $log_name, "【业务出错】:\n" . $xml . "\n" );
			} else {
				$order = $notify->getData ();
				$log_id = $order ["out_trade_no"];
				order_paid ( $log_id );
				// $wxpay = new wxpay();
				// $wxpay->respond($order["out_trade_no"]);
				// 此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result ( $log_name, "【支付成功】:\n" . $order ["out_trade_no"] . "\n" );
			}
		}
	}
}
class Log_
{
	// 打印log
	function  log_result($file,$word)
	{
		$fp = fopen($file,"a");
		flock($fp, LOCK_EX) ;
		fwrite($fp,"执行日期：".strftime("%Y-%m-%d-%H:%M:%S",time())."\n".$word."\n\n");
		flock($fp, LOCK_UN);
		fclose($fp);
	}
}
?>