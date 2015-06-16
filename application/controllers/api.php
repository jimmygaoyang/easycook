<?php 
class Api extends CI_Controller{
	
  public function __construct()
  {
    parent::__construct();

    $this->load->library("LogService");
    $this->module_name = "Api_MANAGER";
    $host= gethostname();
    $this->IP = gethostbyname($host);
  }

	public function index()
	{
	}
	public function process()
	{
		

		$data = file_get_contents('php://input');
		$busData = json_decode($data ,true);
		switch ($busData["cmdID"]) {
			case '4':
				# code...
				$rsp = $this->menuLoad($busData);
				break;
			case "12":
				# code...
				$rsp = $this->adMenuLoad($busData);
				break;
			default:
				# code...
				break;
		}
		echo $rsp ;

	}
	//menu下载
	public function menuLoad($recData)
	{
		   $filename =  $_SERVER['DOCUMENT_ROOT'] ."/htdocs/menus/".$recData['menuID'].".json";
	    	$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"$filename ");
	      $handle = fopen($filename, "r");//读取二进制文件时，需要将第二个参数设置成'rb'
	      
	      //通过filesize获得文件大小，将整个文件一下子读到一个字符串中
	      $contents = fread($handle, filesize ($filename));
	       $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"file content $contents ");
	      fclose($handle);
	      //解析
	      echo $contents;
	}
	//推荐广告下载
		public function adMenuLoad($recData)
	{
		   $filename =  $_SERVER['DOCUMENT_ROOT'] ."/htdocs/menus/"."adMenuList.json";
	    	$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"$filename ");
	      $handle = fopen($filename, "r");//读取二进制文件时，需要将第二个参数设置成'rb'
	      
	      //通过filesize获得文件大小，将整个文件一下子读到一个字符串中
	      $contents = fread($handle, filesize ($filename));
	       $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"file content $contents ");
	      fclose($handle);
	      //解析
	      echo $contents;
	}
}
