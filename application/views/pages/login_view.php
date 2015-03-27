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
	<div class="row" style="border-bottom:1px solid #000">
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
				<div class="col-md-4 col-sm-2"><a href='<?= $this->config->base_url(); ?>index.php/brand_manager' style = "font-size:20px;color:#000">菜谱</a></div>
				<div class="col-md-4 col-sm-5"><a style = "font-size:20px;color:#000">菜市场</a></div>
				<div class="col-md-4 col-sm-5"><a href='<?= $this->config->base_url(); ?>index.php/brand_manager' style = "font-size:20px;color:#000">排行榜</a></div>
			</div>
		</div>
	</div>
	<div class="row" style="margin-top:50px">
		<div class="col-md-6 col-sm-6 col-md-offset-1">
			<div style="width:100%;height:300px;border:1px solid #000;-webkit-border-radius: 15px;">
				<div id="myCarousel" class="carousel slide">
				   <!-- 轮播（Carousel）指标 -->
				   <ol class="carousel-indicators">
				      <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
				      <li data-target="#myCarousel" data-slide-to="1"></li>
				      <li data-target="#myCarousel" data-slide-to="2"></li>
				   </ol>   
				   <!-- 轮播（Carousel）项目 -->
				   <div class="carousel-inner">
				      <div class="item active">
				         <img src="/htdocs/images/1.jpg" alt="First slide">
				      </div>
				      <div class="item">
				         <img src="/htdocs/images/2.jpg" alt="Second slide">
				      </div>
				      <div class="item">
				         <img src="/htdocs/images/3.jpg" alt="Third slide">
				      </div>
				   </div>
				   <!-- 轮播（Carousel）导航 -->
				   <a class="carousel-control left" href="#myCarousel" 
				      data-slide="prev">&lsaquo;</a>
				   <a class="carousel-control right" href="#myCarousel" 
				      data-slide="next">&rsaquo;</a>
				</div>
			</div>
		</div>
		<div class="col-md-4 col-sm-4" padding:20px>
			<div class= "col-md-12" style="padding:50px;width:100%;height:300px;border:1px solid #000;-webkit-border-radius: 15px;">
				<form class="form-horizontal" method="post">

				<div class="control-group">
				<label class="control-label" for="username">用户名</label>
				<span class="controls">
				<input type="text" class="input-xlarge" id="username" name="username">
				</span>
				</div>
				<div class="control-group">
				<label class="control-label" for="password">密码</label>
				<span class="controls">
				<input type="password" class="input-xlarge" id="password" name="password">
				</span>
				</div>
				<div class="form-actions">
				<button type="submit" class="btn btn-primary">登陆</button>
				</div>
				</form>
			</div>
		</div>
	</div>
</div>


</body>
</html>