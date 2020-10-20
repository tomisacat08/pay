<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:30:"payapi/app_download/index.html";i:1581552384;}*/ ?>
<!DOCTYPE html>
<html>
	<head>
		<title>下载APP</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta content="telephone=no,email=no" name="format-detection">
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0" />
		<meta content="yes" name="apple-mobile-web-app-capable">
		<meta content="yes" name="apple-touch-fullscreen">
    	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
		<meta http-equiv="cache-control" content="no-siteapp">
		<meta name="robots" content="INDEX,FOLLOW">

		<!-- h5适配 -->
		<script src="../js/public/flexible.js" type="text/javascript" charset="utf-8"></script>
		<!-- JQuery及其插件 -->
		<script src="../js/jquery-2.1.3.min.js" type="text/javascript" charset="utf-8"></script>
		<!-- 消除移动设备点击延时的插件 -->
		<script src="../js/fastclick.js" type="text/javascript"></script>
		<!-- 计算中部内容器高度 -->
		<script src="../js/public/resizeContent.js" type="text/javascript" charset="utf-8"></script>
		<!-- 公共样式 -->
		<link rel="stylesheet" type="text/css" href="../css/base.css"/>
		<link rel="stylesheet" type="text/css" href="../css/login.css"/>
	</head>
	<body class="load-bg">
		<div class="gzhCode">
			<button class="anzhuo xiazaiBtn"><img src="../img/Android.png"></img><span>Android 下载</span></button>
		</div>
		<!-- <button class="ios xiazaiBtn" onclick="javascript:window.location.href='https://fir.im/eosnodes'"><img src="../img/iphone.png"></img><span>iPhone 下载</span></button> -->

		<div class="toLiulanqi">
			<p>1.点击右上角</p>
			<div>
				<span>2.点击</span>
				<i>
					<img src="../img/liulanqi.png"/>
					<em>在浏览器中打开</em>
				</i>
				<span>在浏览器中打开进行下载</span>
			</div>
			<img  class="positionImg" src="../img/toLiulanqi.png"/>
		</div>
	</body>
	<script type="text/javascript">
		$('.anzhuo').click(function () {

			var ua = window.navigator.userAgent.toLowerCase();
			if(ua.match(/MicroMessenger/i) == 'micromessenger'){ //判断是否是微信内置浏览器
				$('.toLiulanqi').css('display','block');

			}else {
				window.location.href='<?php echo $app_update_url; ?>';
			}
			$('.toLiulanqi').click(function() {
				$('.toLiulanqi').css('display','none');
			});
		});
	</script>
</html>
