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
	<div id="div1"><h2>使用 jQuery AJAX 修改文本内容</h2></div>
	<button id="btn1">获取外部内容</button>
	<button id="btn2">附加p1</button>
	<button id="btn3">附加函数</button>

	<script type="text/javascript">
		$(document).ready(function(){
		  $("#btn1").click(function(){
		    $("#div1").load("/files/demo_test.txt");
		  });

		  $("#btn2").click(function(event) {
		  	/* Act on the event */
		  	$("#div1").load("/files/demo_test.txt #p1");
		  });
		  $("#btn3").click(function(event) {
		  	/* Act on the event */
		  	$("#div1").load("/files/demo_test.txt",function(responseTxt,statusTxt,xhr) {
		  		/* Act on the event */
		  		if(statusTxt=="success")
			        alert("外部内容加载成功!");
			    if(statusTxt=="error")
			        alert("Error: "+xhr.status+": "+xhr.statusText);
		  	});
		  });
		});
	</script>
</body>
</html>
