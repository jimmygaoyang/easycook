<?php
class MenuLoadPro extends CI_Controller{
	
	public function __construct()
  {
    parent::__construct();
    $this->load->library("LogService");
    $this->module_name = "MENU_LOAD_PRO";
    $host= gethostname();
    $this->IP = gethostbyname($host);
  }

  public function process($recData){

    $data = file_get_contents('php://input');
    echo $data;
    $busData = json_decode($data ,true);
    $filename =  $_SERVER['DOCUMENT_ROOT'] ."/htdocs/menus/".$busData['menuID'].".json";
    $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"$filename ");
      $handle = fopen($filename, "r");//读取二进制文件时，需要将第二个参数设置成'rb'
      
      //通过filesize获得文件大小，将整个文件一下子读到一个字符串中
      $contents = fread($handle, filesize ($filename));
       $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"file content $contents ");
      fclose($handle);
      //解析
      echo json_encode($contents);
  }
}