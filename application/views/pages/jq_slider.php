<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8"></meta>
	<title><?php echo $title?>ECOOK</title>
	<link rel="stylesheet" type="text/css" href="/htdocs/bootstrap/css/bootstrap.min.css">
	<script type="text/javascript" src="/htdocs/js/jquery-2.1.3.min.js"></script>
	<script src="/htdocs/bootstrap/js/bootstrap.min.js"></script>

	<style type="text/css"> 
	#panel,#flip,#flipUp,#toggle
	{
	padding:5px;
	text-align:center;
	background-color:#e5eecc;
	border:solid 1px #c3c3c3;
	}
	#panel
	{
	padding:50px;
	display:none;
	}
	</style>
</head>
<body>
	<div id="flip">Click to slide down panel</div>
	<div id="flipUp">Click to slide up panel</div>
	<div id="toggle">toggle</div>
	<div id="panel">Hello world!</div>
	<script type="text/javascript">
		$("#flip").click(function(event) {
			$("#panel").slideDown('slow');
		});
		$("#flipUp").click(function(event) {
			/* Act on the event */
			$("#panel").slideUp('slow');
		});
		$("#toggle").click(function(event) {
			/* Act on the event */
			$("#panel").slideToggle('slow');
		});		
	</script>
</body>
</html>
