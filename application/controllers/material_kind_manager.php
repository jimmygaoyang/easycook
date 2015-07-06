<?php
class Material_kind_manager extends CI_Controller
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
       $this->load->model('Material_kind');
  
       
       $this->data['title'] = "material_kind_manager";
       $this->module_name = "MATERIAL KIND_MANAGER";
       $host= gethostname();
       $this->IP = gethostbyname($host);
    }


        function fuzzy_find_by_name()
    {
   		$data = file_get_contents('php://input');
   		$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"$data ");
		$busData = json_decode($data ,true);


		$like["Name"] = $busData["kindName"];
	    $kind_result = $this->Material_kind->read_sort('', '', '',$like);
	    $rspKind = array();
	    if (!empty($kind_result)) {
	    	# code...
	    	$s = var_export($kind_result,true);
	    	$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,$s);
	    	
	    	foreach ($kind_result as $kind ) {
	    		# code...
	    		$KindBean["kind_name"]=$kind["Name"];
	    		$KindBean["material_kind_id"]=$kind["Material_Kind_Id"];
	    		$KindBean["Info"]=$kind["Description"];
				$rspKind[] = $KindBean;
	    	}
	    	$s = var_export($rspKind,true);
	    	$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,$s);
	    }

        $rspData["materialKindList"] =$rspKind;
        $s = var_export($rspData,true);
        $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,$s);

        $rspInfo = json_encode($rspData);
        $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,$rspInfo);
        echo $rspInfo;
    }



}