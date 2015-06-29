<?php
class Material_manager extends CI_Controller
{
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
       $this->load->model('Material');
       $this->load->model('brand');
       
       $this->data['title'] = "material_manager";
       $this->module_name = "MATERIAL MANAGER";
       $host= gethostname();
       $this->IP = gethostbyname($host);
    }

    function fuzzy_find_by_name()
    {
   		$data = file_get_contents('php://input');
   		$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"$data ");
		$busData = json_decode($data ,true);


		$like["Name"] = $busData["materialName"];
	    $options["sort"] = array("Ref"=>"desc");
	    $material_result = $this->Material->read_sort('', '', $options,$like);
	    $rspMaterial = array();
	    if (!empty($material_result)) {
	    	# code...
	    	$s = var_export($material_result,true);
	    	$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,$s);
	    	
	    	foreach ($material_result as $material ) {
	    		# code...
	    		$MaterialBean["material_id"]=$material["Material_Id"];
	    		$MaterialBean["material_kind_id"]=$material["Material_Kind_Id"];
	    		$MaterialBean["name"]=$material["Name"];
	    		$MaterialBean["brand_id"]=$material["Brand_Id"];
	    		$MaterialBean["ref"]=$material["Ref"];

	    		//读取Brand中的品牌
				$criteria = array();
				$criteria["and"] = array("Brand_Id" => $material["Brand_Id"]);
				$brandDat = $this->brand->read($criteria);

				$MaterialBean["brand_name"]=$brandDat[0]["Name"];
				$rspMaterial[] = $MaterialBean;
	    	}
	    	$s = var_export($rspMaterial,true);
	    	$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,$s);
	    }

        $rspData["materialList"] =$rspMaterial;
        $s = var_export($rspData,true);
        $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,$s);

        $rspInfo = json_encode($rspData);
        $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,$rspInfo);
        echo $rspInfo;
    }
}