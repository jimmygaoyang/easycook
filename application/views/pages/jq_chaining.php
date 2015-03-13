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
	<p id="p1">jQuery is fun!!</p>
	<button>Click me</button>
	<script type="text/javascript">
	$(function () {
		$("button").click(function(event) {
			/* Act on the event */
			$("#p1").css('color', 'red').slideUp('slow').slideDown(2000);
		});
	});
	</script>
</body>
</html>
