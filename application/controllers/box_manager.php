<?php 
class Box_manager extends CI_Controller{
	
   /**
   * FUNCTION: construct()
   * 
   * Constructor function of the controller
   * 
   * @param  none
   * @return none
   */
    function __construct()
    {
       parent::__construct();
       $this->load->library("LogService");
       $this->load->model('Box');
       $this->load->model('User');
       
       $this->data['title'] = "box_manager";
       $this->module_name = "BOX MANAGER";
       $host= gethostname();
       $this->IP = gethostbyname($host);
    }
   /**
   * FUNCTION: add()
   * 
   * add box
   * 
   * @param  none
   * @return none
   */
   function add()
   {
   	   	$data = file_get_contents('php://input');
   		$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"$data ");
		$busData = json_decode($data ,true);
   }


}