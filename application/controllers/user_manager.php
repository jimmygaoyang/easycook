<?php 

class User_manager extends CI_Controller{

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
       $this->load->model('User');
       $this->load->model('Role');
       
       $this->data['title'] = "login";
       $this->module_name = "USER MANAGER";
       $host= gethostname();
       $this->IP = gethostbyname($host);
    }

   /**
   * FUNCTION: register()
   * 
   * register function
   * 
   * @param  none
   * @return none
   */
   function register()
   {
   		$data = file_get_contents('php://input');
   		$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"$data ");
		$busData = json_decode($data ,true);
		//查找是否有重名
		$criteria = array();
		$criteria["and"] = array("Name" => $busData["name"]);
		$result = $this->User->count_results($criteria);

		if ($result > 0) {
			$rspData["userID"] =0;
			$rspData["state"] = "ERRO";
			$rspData["erroInfo"] = "该用户名已经被使用";

			$rspInfo = json_encode($rspData);
			echo $rspInfo;
			return;
		}
		$data_in = array();
		$data_in["Role_Id"] = 2;
		$data_in["Password"] = $busData["password"];
		$data_in["Name"] = $busData["name"];
		$data_in["Phone"] = $busData["name"];
		$data_in["UpLoad"] = 0;
		$data_in["DownLoad"] = 0;
		$data_in["Status"] = 0;
  
		$result = $this->User->create($data_in);
		if ($result) {
			$criteria = array();
			$criteria["and"] = array("Name" => $busData["name"]);
			$userDat = $this->User->read($criteria);
			if (!empty($userDat)) {
				
				$rspData["userID"] =$userDat[0]["User_Id"];
				$rspData["state"] = "OK";
				$rspData["erroInfo"] = "注册成功ID为".$userDat[0]["User_Id"];

				$rspInfo = json_encode($rspData);
				echo $rspInfo;
			}

		}
   }
   /**
   * FUNCTION: login()
   * 
   * login function
   * 
   * @param  none
   * @return none
   */
   function login()
   {
   	   	$data = file_get_contents('php://input');
   		$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"$data ");
		$busData = json_decode($data ,true);
		//查找该用户名与密码
		$criteria = array();
		$criteria["and"] = array("Name" => $busData["name"],"Password" => $busData["password"]);
		$userDat = $this->User->read($criteria);
		if (!empty($userDat)) {
				
			//更新用户的状态
			$data_in = array('Status' => 1);
      		$upd_criteria['and'] = array('User_Id' => $userDat[0]['User_Id']);
      		$result = $this->User->update($data_in, $upd_criteria);
	      	if($result !== false)
	      	{
				$rspData["userID"] =$userDat[0]["User_Id"];
				$rspData["state"] = "OK";
				$rspData["erroInfo"] = "登陆成功ID为".$userDat[0]["User_Id"];

				$rspInfo = json_encode($rspData);
				echo $rspInfo;
		    }

		}
   }


}