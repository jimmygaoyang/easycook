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
	<p><a href="http://www.w3cschool.cc" id="w3s">W3Cschool.cc</a></p>
	<p id="test1">This is a paragraph.</p>
	<p id="test2">This is another paragraph.</p>
	<p>Input field: <input type="text" id="test3" value="Mickey Mouse"></p>

	<button id="btn1">Set Text</button>
	<button id="btn2">Set HTML</button>
	<button id="btn3" class="info">Set Value</button><br>
	<button id="btn4">function</button>
	<button id="btn5">function1</button>

	<script type="text/javascript">
		$(function(){
			$("#btn1").click(function(event) {
				/* Act on the event */
				$("#test1").text('Hello World!');
			});
			$("#btn2").click(function(event) {
				/* Act on the event */
				$("#test2").html("<b>Hello world!</b>");
			});
			$("#btn3").click(function(event) {
				/* Act on the event */
				$("#test3").val("donlag duck");
			});

			$("#btn4").click(function(event) {
					/* Act on the event */
				$("#test1").text(function(i,origText){
      		return "Old text: " + origText + " New text: Hello world! (index: " + i + ")"; 
    			});
			});

			$("#btn5").click(function(event) {
				/* Act on the event */
				$("#w3s").attr({
 					"href" : "http://www.w3cschool.cc/jquery",
      				"title" : "W3Cschool jQuery Tutorial"
				});
			});

  });

	</script>
</body>
</html>
</html>