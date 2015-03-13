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
	<p>Demonstrate fadeIn() with different parameters.</p>
	<button id="fadeIn">Click to fade in boxes</button><br>
	<button id="fadeOut">Click to fade Out boxes</button><br>
	<button id="toggle">Click to toggle boxes</button><br>
	<button id="fadeto">Click to  fadeTo() with different parameters</button>
	<br><br>
	<div id="div1" style="width:80px;height:80px;display:none;background-color:red;"></div><br>
	<div id="div2" style="width:80px;height:80px;display:none;background-color:green;"></div><br>
	<div id="div3" style="width:80px;height:80px;display:none;background-color:blue;"></div><br>
	<div id="div4" style="width:80px;height:80px;background-color:red;"></div><br>


	<script type="text/javascript">
		$(function(){
			$("#fadeIn").click(function(event) {
				/* Act on the event */
				$("#div1").fadeIn();
				$("#div2").fadeIn("slow");
				$("#div3").fadeIn(3000);
			});
			$("#fadeOut").click(function(event) {
				/* Act on the event */
				$("#div1").fadeOut();
				$("#div2").fadeOut("slow");
				$("#div3").fadeOut(3000);
			});
			$("#toggle").click(function(event) {
				/* Act on the event */
				$("#div1").fadeToggle();
				$("#div2").fadeToggle("slow");
				$("#div3").fadeToggle(3000);
			});
			$("#fadeto").click(function(event) {
				$("#div4").fadeTo('slow/400/fast', 0.5);
			});
		})
	</script>
</body>
</html>
