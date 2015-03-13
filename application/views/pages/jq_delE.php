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
	<div id="div1" style="height:100px;width:300px;border:1px solid black;background-color:yellow;">
	This is some text in the div.
	<p>This is a paragraph in the div.</p>
	<p class="italic">This is another paragraph in the div.</p>

	</div>
	<br>
	<button id="remove">Remove div element</button>
	<button id="empty">empty div</button>
	<button id="filter">remove filter</button>
	<script type="text/javascript">
	$(function(){
		$("#remove").click(function(event) {
			/* Act on the event */
			$("#div1").remove();
		});
		$("#empty").click(function(event) {
			/* Act on the event */
			$("#div1").empty();
		});
		
		$("#filter").click(function(event) {
			/* Act on the event */
			$("p").remove(".italic");
		});
		
	})
	</script>
</body>
</html>
