<!DOCTYPE html>
<html>
<head>
	<title><?php echo $title?>ECOOK</title>
	<meta charset = "UTF-8"></meta>
	<link rel="stylesheet" type="text/css" href="/htdocs/bootstrap/css/bootstrap.min.css">
	<script type="text/javascript" src="/htdocs/js/jquery-2.1.3.min.js"></script>
	<script src="/htdocs/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
	<form role="form">
	   <div class="form-group">
	      <label for="name">名称</label>
	      <input type="text" class="form-control" id="name" 
	         placeholder="请输入名称">
	   </div>
	   <div class="form-group">
	      <label for="inputfile">文件输入</label>
	      <input type="file" id="inputfile">
	      <p class="help-block">这里是块级帮助文本的实例。</p>
	   </div>
	   <div class="checkbox">
	      <label>
	      <input type="checkbox"> 请打勾
	      </label>
	   </div>
	   <button type="submit" class="btn btn-default">提交</button>
	</form>
	<h2>内联表单</h2>
	<form class="form-inline" role="form">
	   <div class="form-group">
	      <label class="sr-only" for="name1">名称</label>
	      <input type="text" class="form-control" id="name1" 
	         placeholder="请输入名称">
	   </div>
	   <div class="form-group">
	      <label class="sr-only" for="inputfile1">文件输入</label>
	      <input type="file" id="inputfile1">
	   </div>
	   <div class="checkbox">
	      <label>
	      <input type="checkbox"> 请打勾
	      </label>
	   </div>
	   <button type="submit" class="btn btn-default">提交</button>
	</form>
	
	<h2>水平表单</h2>
	<form class="form-horizontal" role="form">
	   <div class="form-group">
	      <label for="firstname" class="col-sm-2 control-label">名字</label>
	      <div class="col-sm-10">
	         <input type="text" class="form-control" id="firstname" 
	            placeholder="请输入名字">
	      </div>
	   </div>
	   <div class="form-group">
	      <label for="lastname" class="col-sm-2 control-label">姓</label>
	      <div class="col-sm-10">
	         <input type="text" class="form-control" id="lastname" 
	            placeholder="请输入姓">
	      </div>
	   </div>
	   <div class="form-group">
	      <div class="col-sm-offset-2 col-sm-10">
	         <div class="checkbox">
	            <label>
	               <input type="checkbox"> 请记住我
	            </label>
	         </div>
	      </div>
	   </div>
	   <div class="form-group">
	      <div class="col-sm-offset-2 col-sm-10">
	         <button type="submit" class="btn btn-default">登录</button>
	      </div>
	   </div>

	   <div class="form-group">
	   		<label for="inputText">请输入文本</label>
	   		<textarea class="form-control" rows="3"></textarea>
	   </div>
	</form>
</body>
</html>

