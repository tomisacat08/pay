<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:18:"./api/pay/api.html";i:1599828190;}*/ ?>

<!DOCTYPE html>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

	<meta http-equiv="Content-Language" content="zh-cn">
	<title>支付文档</title>
	<link href="/api/markdown/css/md.css" rel="stylesheet" media="screen">
</head>
<body>
<p id='md' style="display:none">
	<?php echo $md_txt; ?>
</p>
<p id="html_word">

</p>
</body>
</html>
<script type="text/javascript" src="/api/markdown/js/Parser.js"></script>
<script type="text/javascript" src="/payapi/js/jquery-2.1.3.min.js"></script>
<script type="text/javascript">
	var parser = new HyperDown,
	html = parser.makeHtml($('#md').text());
	$('#html_word').html(html);
</script>
