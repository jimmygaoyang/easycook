<?php
class Brand_manager extends MY_Controller {

	  /**
	   * Constructor
	   * 
	   */
	  function __construct() {
	    parent::__construct();
	    $this->data['title'] = "品牌表";
	    $this->load->model("brand");
	  }

	public function index()
	{
		$criteria = array();
	    $options = array();
	    $options['sort'] = array("convert(Name using gb2312)" => "asc");
	    // $options['limit']['limit'] = 10;
	    // $options['limit']['offset'] = 0;
	    if(strcmp($this->data['session_data']['role_name'],"utility")!=0)
	    {
	      $count = $this->device_specification->count_results('');
	      $devices = $this->device_specification->read_sort_limit('','',$options);
	    }
	    else
	    {
	       $criteria["and"] = array("Utility_Id" => $this->data['session_data']['utility_id']);
	       $count = $this->device_specification->count_results($criteria);
	       $devices = $this->device_specification->read_sort_limit($criteria,'',$options);
	    }
	    
	    $this->data['devices'] = $devices;
	    $this->data['count'] = $count;
		$this->load->view('brand_view', $this->data);
	}

	Brand_manager
}