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
	<p id="test">This is some <b>bold</b> text in a paragraph.</p>
	<p>Name: <input type="text" id="test1" value="Mickey Mouse"></p>
	<p><a href="http://www.w3cschool.cc" id="w3s">W3Cschool.cc</a></p>
	<button id="btn1">Show Text</button><br>
	<button id="btn2">Show HTML</button><br>
	<button id="btn3">Show Value</button><br>
	<button id="btn4">Show attr</button>

	<script type="text/javascript">
	$(function(){
		$("#btn1").click(function(event) {
			/* Act on the event */
			alert("Text: " + $("#test").text());
		});
		$("#btn2").click(function(event) {
			/* Act on the event */
			alert("Html: " + $("#test").html());
		});
		$("#btn3").click(function(event) {
			/* Act on the event */
			alert("Value:"+$("#test1").val())
		});
		$("#btn4").click(function(event) {
			/* Act on the event */
			alert("attr:"+$("#w3s").attr('href'));
		});
	})
	</script>
</body>
</html>
