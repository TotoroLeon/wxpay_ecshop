ecshop的微信支付插件
包含：

	JSAPI（微信支付）
	NATIVE（扫码支付）
安装：
	覆盖到网站根目录。 注意备份原来的网站

配置：
	后台开启微信支付功能，填入相应的参数 appi ， mchid , key, appsecret,支付通知url，支付成功跳转url

说明：
	需要在公众号后台添加支付人员测试白名单。
	满足以上两个条件才可以发起支付，支付成功后wxpay\demo\notify_url.php 会接受到通知，并改变订单状态。。

	目前支持的是自动获取openid ，每次请求（自行优化mysql保存openid，节约资源）
