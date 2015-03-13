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
		<div class="container table-responsive">
		<h2>表格1</h2>
		<p> .table 为任意表格添加基本样式 (只有横向分隔线):</p>            
		<table class="table  table-hover table-condensed">
			<thead>
			  <tr>
			    <th>ID</th>
			    <th>品牌</th>
			    <th>公司名称</th>
			    <th>电话</th>
			    <th>地址</th>
			    <th>网址</th>
			    <th>引用次数</th>
			  </tr>
			</thead>
			<tbody>
			 <?php if(count($brands) == 0): ?>
		         <tr>
		           <td colspan='12'>没有记录</td>
		         </tr>
		     <?php else: ?>     
		     <?php foreach($brands as $brand): ?>
		         <tr>
		          <td><?= $brand['Brand_Id'] ?></td>
		          <td><?= $brand['Name'] ?></td>
		          <td><?= $brand['CorporName'] ?> </td>
		          <td><?= $brand['TelNum'] ?></td>
		          <td><?= $brand['Address'] ?></td>
		          <td><?= $brand['URL'] ?></td>
		          <td><?= $brand['Ref'] ?></td>
		         </tr>
		    <?php endforeach ?>
		    <?php endif ?>
			</tbody>
		</table>
		<h2>表格2</h2>
			<table class="table">
				<thead>
					<tr>
						<th>#</th>
				    	<th>Firstname</th>
			  		</tr>
				</thead>
				<tbody>
					<tr class="active">
					    <td>1</td>
					    <td>Anna</td>
					</tr>
					<tr class="success">
					    <td>2</td>
					    <td>表示成功的操作</td>
					</tr>
					<tr class="info">
					    <td>3</td>
					    <td>表示信息变化的操作</td>
					</tr>
					<tr class="warning">
					    <td>4</td>
					    <td>表示警告的操作</td>
					</tr>
					<tr class="danger">
					    <td>5</td>
					    <td>表示危险的操作</td>
					</tr>
				</tbody>
			</table>
		</div>
</body>
</html>
