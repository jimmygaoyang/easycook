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
	<button id="start">开始动画</button><br>
	<button id="relative">使用相对值</button><br>
	<button id="predef">使用预定义值</button><br>
	<button id="queun">使用队列值</button><br>
	<button id="ziti">字体</button><br>
	<button id="stop">停止</button>

	<p>By default, all HTML elements have a static position, and cannot be moved. To manipulate the position, remember to first set the CSS position property of the element to relative, fixed, or absolute!</p>
	<div style="background:#98bf21;height:100px;width:100px;position:absolute;">
	 字体</div>
	<script type="text/javascript">
		$("#start").click(function(event) {
			/* Act on the event */
			$("div").animate({left:'250px',
				opacity:'0.5',
				height:'150px',
				width:'150px'});
		});
		$("#relative").click(function(event) {
			/* Act on the event */
			$("div").animate({left:'250px',
				opacity:'0.5',
				height:'+=150px',
				width:'+=150px'});
		});
		$("#predef").click(function(event) {
			/* Act on the event */
			$("div").animate({left:'250px',
				opacity:'0.5',
				height:'toggle'});
		});
		$("#queun").click(function(event) {
			/* Act on the event */
		    var div=$("div");
		    div.animate({height:'300px',opacity:'0.4'},"slow");
		    div.animate({width:'300px',opacity:'0.8'},"slow");
		    div.animate({height:'100px',opacity:'0.4'},"slow");
		    div.animate({width:'100px',opacity:'0.8'},"slow");
		});
		$("#ziti").click(function(event) {
			/* Act on the event */
			  var div=$("div");
			  div.animate({left:'100px'},"slow");
			  div.animate({fontSize:'3em'},"slow");
		});
		$("#stop").click(function(event) {
			/* Act on the event */
			$("div").stop();
		});

	</script>
</body>
</html>
