<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8"></meta>
	<title><?php echo $title?>_ECOOK</title>
	<link rel="stylesheet" type="text/css" href="/htdocs/bootstrap/css/bootstrap.min.css">
	<script type="text/javascript" src="/htdocs/js/jquery-2.1.3.min.js"></script>
	<script src="/htdocs/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container-fluid">
<div class="row" style="border-bottom:4px solid #000">
	<div class="col-md-2 col-sm-2 clearfix">
	<p style = "font-size:28px;"><b>COOKMASTER</b></p>
	</div>
	<div class="col-md-2   col-sm-2 " >
		<form role="form">
			<div class="form-group">
		   		<input class="form-control" rows="1" style = "font-size:20px;"></input>
		   </div>
		</form>
	</div>
	<div class="col-md-3 col-sm-2">
		<div class="row">
			<div class="col-md-4 col-sm-2"><a href='<?= $this->config->base_url(); ?>index.php/brand_manager' style = "font-size:20px;">菜谱</a></div>
			<div class="col-md-4 col-sm-5"><a style = "font-size:20px;">菜市场</a></div>
			<div class="col-md-4 col-sm-5"><a href='<?= $this->config->base_url(); ?>index.php/brand_manager' style = "font-size:20px;">排行榜</a></div>
		</div>
	</div>
</div>

</div>


</body>
</html>