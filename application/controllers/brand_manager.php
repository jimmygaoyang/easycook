<?php

class Brand_manager extends CI_Controller {

	  /**
	   * Constructor
	   * 
	   */
	  // function __construct() {
	  //   //parent::__construct();
	  //   $this->data['title'] = "品牌表";
	  //   //$this->load->model("brand");
	  // }

	public function index()
	{
		$this->load->model("brand");
		$criteria = array();
	    $options = array();
	    $options['sort'] = array("convert(Name using gb2312)" => "desc");
	    // $options['limit']['limit'] = 10;
	    $count = $this->brand->count_results('');
	    $brands = $this->brand->read_sort_limit('','',$options);

	    
	    $this->data['brands'] = $brands;
	    $this->data['count'] = $count;
		$this->data['title'] = "品牌表";
		$this->load->view('brand_view', $this->data);
	}

}