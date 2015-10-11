<?php
class Menu_manager extends CI_Controller{
	
	public function __construct()
  {
    parent::__construct();
    $this->load->library("LogService");
    $this->module_name = "MENU_MANAGER";
    $host= gethostname();
    $this->IP = gethostbyname($host);
  }

  public function upload_file($recData){

    $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"jinru");
    $base_path = $_SERVER['DOCUMENT_ROOT'] ."/htdocs/menus/".$recData; //接收文件目录  
    $target_path = $base_path . basename ( $_FILES ['uploadfile'] ['name'] );  
    if (move_uploaded_file ( $_FILES ['uploadfile'] ['tmp_name'], $target_path )) {  
        $array = array ("code" => "1", "message" => $_FILES ['uploadfile'] ['name'] );  
        echo json_encode ( $array );  
    } else {  
        $array = array ("code" => "0", "message" => "There was an error uploading the file, please try again!" . $_FILES ['uploadfile'] ['error'] );  
        echo json_encode ( $array );  
    } 

  }
}