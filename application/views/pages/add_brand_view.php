<!DOCTYPE html>
<html>
<head>
	<meta charset = "UTF-8"></meta>
	<title><?php echo $title?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="/htdocs/bootstrap/css/bootstrap.min.css">
	<script type="text/javascript" src="/htdocs/js/jquery-2.1.3.min.js"></script>
	<script src="/htdocs/bootstrap/js/bootstrap.min.js"></script>	
</head>
<body>	
	<div class="row"><h2 class="col-md-4 col-md-offset-3" ><b>创建一个品牌</b></h2></div>
	 <form role="form" class="form-horizontal" method="post" accept-charset="utf-8" action="<?= $this->config->base_url(); ?>index.php/brand_manager/add_brand">
	   <div class="form-group">
	      <label class="col-md-2 control-label" for="BrandName">品牌名称</label>
		  <input class="col-md-4" type="text" class="form-control" id="BrandName" name="BrandName"
	         placeholder="请输入品牌名称">
	   </div>
	   <div class="form-group">
	      <label class="col-md-2 control-label" for="CorporName">公司名称</label>
		  <input class="col-md-4" type="text" class="form-control" id="CorporName" name="CorporName"
	         placeholder="请输入公司名称">
	   </div>
	   <div class="form-group">
	      <label class="col-md-2 control-label" for="TelNum">公司电话</label>
		  <input class="col-md-4" type="text" class="form-control" id="TelNum" name="TelNum"
	         placeholder="请输入公司电话">
	   </div>
	   <div class="form-group">
	      <label class="col-md-2 control-label" for="Address">公司地址</label>
		  <input class="col-md-4" type="text" class="form-control" id="Address" name="Address"
	         placeholder="请输入公司地址">
	   </div>
	   <div class="form-group">
	      <label class="col-md-2 control-label" for="URL">公司网址</label>
		  <input class="col-md-4" type="text" class="form-control" id="URL" name="URL"
	         placeholder="请输入公司网址">
	   </div>
	   <div class="col-md-4 col-md-offset-3" ><button type="submit" class="btn btn-default">提交</button></div>
	</form>
</body>
</html>
