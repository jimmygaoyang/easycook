<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8"></meta>
	<title><?php echo $title?>ECOOK</title>
	<link rel="stylesheet" type="text/css" href="/htdocs/bootstrap/css/bootstrap.min.css">
	<script type="text/javascript" src="/htdocs/js/jquery-2.1.3.min.js"></script>
	<script src="/htdocs/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
	<p>如果你点击“隐藏” 按钮，我将会消失。</p>
	<p class="toggle">toggle实验窗</p>
	<button id="hide">隐藏</button>
	<button id="show">显示</button>
	<div>
		<button id="toggle">切换</button>
	</div>
	<script type="text/javascript">
	$(function(){
		$("#hide").click(function(){
			$("p").hide(1000);
		});
		$("#show").click(function(){
			$("p").show(2000);
		});
		$("#toggle").click(function(event) {
			$("p.toggle").toggle();
		});

	});
	</script>
</body>
</html>