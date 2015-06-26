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
       $this->load->model('Material');
       $this->load->model('Material_kind');
       $this->load->model('brand');
       
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

      if (($busData["material_kind_id"] !== -1)&&($busData["brand_id"] !== -1)&&($busData["material_id"] !== -1))//若三者都是有固定值
         {
              //查找是否有重号box
          $criteria = array();
          $criteria["and"] = array("Box_Mac" => $busData["box_mac"]);
          $result = $this->Box->count_results($criteria);
          if ($result>0) {

            $rspData["userID"] =1;
            $rspData["state"] = "ERRO";
            $rspData["erroInfo"] = "该账户已有该编号box";

            $rspInfo = json_encode($rspData);
            echo $rspInfo;
            return;
          }
          //没有 添加box
          $data_in = array();
          $data_in["User_Id"] = $busData["user_id"];
          $data_in["Box_Mac"] = $busData["box_mac"];
          $data_in["Material_Id"] = $fbusData["material_id"];
          $data_in["Material_Kind_Id"] = $busData["material_kind_id"];
          // $data_in["SW_Ver"] = $busData["SW_Ver"];
          $result = $this->Box->create($data_in);


            $rspData["userID"] =1;
            $rspData["state"] = "OK";
            $rspData["erroInfo"] = "BoxID添加成功";
            $rspInfo = json_encode($rspData);
            echo $rspInfo;
            return;
      }

      //处理flavor_kind_name
      $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,$busData["material_id"]);
      if ($busData["material_kind_id"] == -1)//未设置种类
      {
          //查找是否有“未知”种类
            $criteria = array();
            $criteria["and"] = array("Name" => $busData["material_kind_name"]);
            $result = $this->Material_kind->count_results($criteria);
            if ($result <= 0)//没有“未知”种类
            {
              //添加“未知”种类
              $data_in = array();
              $data_in["Name"] = $busData["material_kind_name"];
              $result = $this->Material_kind->create($data_in);  
            }


      }

      //处理brand_name
      if ($busData["brand_id"] == -1)//未设置品牌
      {
          //查找是否有“未知”品牌
            $criteria = array();
            $criteria["and"] = array("Name" => $busData["brand_name"]);
            $result = $this->brand->count_results($criteria);
            if ($result <= 0)//没有“未知”品牌
            {
              //添加“未知”品牌
              $data_in = array();
              $data_in["Name"] = $busData["brand_name"];
              $result = $this->brand->create($data_in);  
            }


      }


      //读取kind中的种类id
      $criteria = array();
      $criteria["and"] = array("Name" => $busData["material_kind_name"]);
      $KindDat = $this->Material_kind->read($criteria);
      //读取Brand中的品牌id
      $criteria = array();
      $criteria["and"] = array("Name" => $busData["brand_name"]);
      $brandDat = $this->brand->read($criteria);

      if ($busData["material_id"] == -1) {
        //查找是否有“未知”flavor
            $criteria = array();
            $criteria["and"] = array("Name" => $busData["material_name"]);
            $result = $this->Material->count_results($criteria);
            if ($result <= 0)//没有未知flavor
            {
              //添加“未知”flavor
               $data_in = array();
               $data_in["Material_kind_id"] = $KindDat[0]["Material_Kind_Id"];
               $data_in["Name"] = $busData["material_name"];
               $data_in["Brand_id"] = $brandDat[0]["Brand_Id"];
               $data_in["Ref"] = 1;
               $result = $this->Material->create($data_in); 


            }
      }
      //读取flavor中的id
      $criteria = array();
      $criteria["and"] = array("Name" => $busData["material_name"]);
      $flavorDat = $this->Material->read($criteria);
      //添加box
      //查找是否有重号box
      $criteria = array();
      $criteria["and"] = array("Box_Mac" => $busData["box_mac"]);
      $result = $this->Box->count_results($criteria);
      if ($result>0) {

        $rspData["userID"] =1;
        $rspData["state"] = "ERRO";
        $rspData["erroInfo"] = "该账户已有该编号box";

        $rspInfo = json_encode($rspData);
        echo $rspInfo;
        return;
      }
      //没有就添加box
      $data_in = array();
      $data_in["User_Id"] = $busData["user_id"];
      $data_in["Box_Mac"] = $busData["box_mac"];
      $data_in["Material_Id"] = $flavorDat[0]["Material_Id"];
      $data_in["Material_Kind_Id"] = $KindDat[0]["Material_Kind_Id"];
      // $data_in["SW_Ver"] = $busData["SW_Ver"];
      $result = $this->Box->create($data_in);


        $rspData["userID"] =1;
        $rspData["state"] = "OK";
        $rspData["erroInfo"] = "BoxID为1";

        $rspInfo = json_encode($rspData);
        echo $rspInfo;
   }


}