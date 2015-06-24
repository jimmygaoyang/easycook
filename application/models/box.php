<?php
class Box extends MY_Model
{
	function __construct()
	{
		parent::__construct();
		$this->table_name = "Box";
		$this->fields = $this->db->list_fields($this->table_name);
		$this->primary_key = "Box";
	}
}