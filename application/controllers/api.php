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
		$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"Receive data $data ");
		echo $data;
	}
}