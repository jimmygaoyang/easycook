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
	<button>发送一个 HTTP post 请求并获取返回结果</button>
	<table>
		<tbody>
			<tr>
				<td style="height: 90px; background-color: #eee; text-align: center; vertical-align: top;"><script charset="Shift_JIS" src="http://chabudai.sakura.ne.jp/blogparts/honehoneclock/honehone_clock_tr.js"></script></td>
				<td style="height: 90px; background-color: #eee; text-align: center; vertical-align: top;"><script charset="Shift_JIS" src="http://chabudai.sakura.ne.jp/blogparts/honehoneclock/honehone_clock_wh.js"></script></td>
			</tr>
			<tr>
				<td><strong>背景透明</strong><br />
				<small>推荐为白色或浅背景</small></td>
				<td><strong>背景白色</strong><br />
				<small>推荐黑色或深色背景</small></td>
			</tr>
		</tbody>
	</table>

	<script type="text/javascript">
	$(document).ready(function(){
	  $("button").click(function(){
	    $.post("/files/demo_test_post.php",
	    {
	      name:"Donald Duck",
	      city:"Duckburg"
	    },
	    function(data,status){
	      alert("Data: " + data + "\nStatus: " + status);
	    });
	  });
	});
	</script>
</body>
</html>