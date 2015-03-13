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
	<p>This is a paragraph.</p>
	<p>This is another paragraph.</p>
	<ol>
	<li>List item 1</li>
	<li>List item 2</li>
	<li>List item 3</li>
	</ol>
	<p id="after">add after</p>
	<button id="btn1">Append text</button>
	<button id="btn2">Append list items</button><br>
	<button id="btn3">prepend text</button>
	<button id="btn4">prepend list items</button><br>
	<button id="btn5" onclick="afterText()">after add</button>
	<script type="text/javascript">
		$(function(){
			$("#btn1").click(function(event) {
				/* Act on the event */
				$("p").append(" <b>Appended text</b>.");
			});
			$("#btn2").click(function(event) {
				/* Act on the event */
				$("ol").append("<li>Appended item</li>");
			});
			$("#btn3").click(function(event) {
				/* Act on the event */
				$("p").prepend('Some text');
			});
			$("#btn4").click(function(event) {
				/* Act on the event */
				$("ol").prepend("<li>prepended item</li>");
			});
		})

		function afterText()
		{
		var txt1="<b>I </b>";                    // Create element with HTML
		var txt2=$("<i></i>").text("love ");     // Create with jQuery
		var txt3=document.createElement("big");  // Create with DOM
		txt3.innerHTML="jQuery!";
		$("#after").after(txt1,txt2,txt3);          // Insert new elements after img
		}
	</script>
</body>
</html>
