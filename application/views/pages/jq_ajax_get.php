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
	<button>发送一个 HTTP GET 请求并获取返回结果</button>
	<script type="text/javascript">
		$(document).ready(function(){
		  $("button").click(function(){
		    $.get("/files/demo_test.php",function(data,status){
		      alert("数据: " + data + "\n状态: " + status);
		    });
		  });
		});
	</script>
</body>
</html>
