<!DOCTYPE html>
<html lang="zh">
<head>
	<!-- <meta http-equiv="refresh" content="3;url={$url}"> -->
	<title>转换程序自动运行，请勿操作~</title>
</head>
<body>
	<h1><?php echo $message; ?></h1>
	<?php if($url) { ?>
	<h1 id="wrap"></h1>
<script>
var wrap = document.getElementById('wrap');
var s = 1;
var jump = function(s) {
	if(s == 0)  {
		location.replace('<?php echo $url; ?>');
	}
	if(s < 0) {
		return false;
	}
	wrap.innerHTML = s + '秒后自动进入下一步操作,请勿操作页面~否则后果自行负责！';
}
setInterval(function() {
	s -= 1;
	jump(s);
}, 1000);
jump(s);
</script>
	<?php } ?>
</body>
</html>