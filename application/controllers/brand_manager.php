<?php

class Brand_manager extends CI_Controller {

	  /**
	   * Constructor
	   * 
	   */
	  function __construct() {
	    parent::__construct();
	    $this->load->model("brand");
		$this->load->library("LogService");
	    $this->module_name = "Brand_manager";
	    $host= gethostname();
		$this->IP = gethostbyname($host);
	    //$this->load->model("brand");
	  }

	public function index()
	{


		$criteria = array();
	    $options = array();
	    $options['sort'] = array("convert(Name using gb2312)" => "desc");
	    // $options['limit']['limit'] = 10;
	    $count = $this->brand->count_results('');
	    $brands = $this->brand->read_sort_limit('','',$options);

	    
	    $this->data['brands'] = $brands;
	    $this->data['count'] = $count;
		$this->data['title'] = "品牌表";
		$this->load->view('/pages/brand_view', $this->data);
	}

	public function add_brand()
	{
		$this->load->model("brand");
		$this->load->helper('form');
		$this->load->library('form_validation');

		$data['title'] = 'Create a news brand';

		$this->form_validation->set_rules('BrandName', 'BrandName', 'required');

		if ($this->form_validation->run() === FALSE)
		{

		$this->load->view('pages/add_brand_view');

		}
		else
		{

			$data_in = array();
			$data_in["Name"] = $this->input->post("BrandName");
			$data_in["CorporName"] = $this->input->post("CorporName");
			$data_in["TelNum"] = $this->input->post("TelNum");
			$data_in["Address"] = $this->input->post("Address");
			$data_in["URL"] = $this->input->post("URL");
			$data_in["Ref"] = 0;
      
			$oem_id = $this->brand->create($data_in);
			if($oem_id)
			{
				$this->load->view('pages/success');
				return TRUE;
			}
			else
			{
				$this->load->view('pages/failure');
				return FALSE;
			}

		 }
	}

	public function delete_brand()
	{

	}

	public function fuzzy_find_by_name()
	{
		$data = file_get_contents('php://input');
   		$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"$data ");
		$busData = json_decode($data ,true);

		$like["Name"] = $busData["brandName"];
	    $options["sort"] = array("Ref"=>"desc");
	    $brand_result = $this->brand->read_sort('', '', $options,$like);
	    $rspBrand = array();
	    if (!empty($brand_result)) {
	    	# code...
	    	$s = var_export($brand_result,true);
	    	$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,$s);
	    	
	    	foreach ($brand_result as $brand ) {
	    		# code...
	    		$BrandBean["brand_id"]=$brand["Brand_Id"];
	    		$BrandBean["name"]=$brand["Name"];
	    		$BrandBean["corpor_name"]=$brand["CorporName"];
	    		$BrandBean["tel_name"]=$brand["TelNum"];
	    		$BrandBean["address"]=$brand["Address"];
	    		$BrandBean["url"]=$brand["URL"];
	    		$BrandBean["ref"]=$brand["Ref"];
				$rspBrand[] = $BrandBean;
	    	}
	    	$s = var_export($rspBrand,true);
	    	$this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,$s);
	    }

        $rspData["brandList"] =$rspBrand;
        $s = var_export($rspData,true);
        $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,$s);

        $rspInfo = json_encode($rspData);
        $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,$rspInfo);
        echo $rspInfo;

	}

}