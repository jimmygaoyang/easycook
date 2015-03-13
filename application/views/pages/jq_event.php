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
	<p class="click">单击消失</p>
	<p class="dblclick">双击消失</p>
<!-- 	<p id="mouseenter">鼠标移入看到弹窗</p> -->
	<p id="mosedown">鼠标按下触发</p>
	<p id="moseup">鼠标松开触发</p>
	Name: <input type="text" name="fullname"><br>
	Email: <input type="text" name="email">
	<script type="text/javascript">
		$(function(){
			$("p.click").click(function(){
				$("p.click").hide();
			});
			$("p.dblclick").dblclick(function(){
				$("p.dblclick").hide();
			});
				$("#mosedown").mousedown(function(){
				alert("按下了mouse键");
			});
				$("#moseup").mouseup(function(){
				alert("松开了mouse键");
			});
				$("input").focus(function(){
				$(this).css("background-color","#cccccc");
			});
				$("input").blur(function(){
				$(this).css("background-color","#ffffff");
			});
			// $("#mouseenter").mouseenter(function(){
			// 	alert("移入了mouseenter");
			// });
			// $("#mouseenter").mouseleave(function(){
			// 	alert("移出了mouseenter");
			// });
		});
	</script>
</body>
</html>
