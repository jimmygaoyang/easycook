
<?php
class Me_Ma_Association extends MY_Model
{
	function __construct()
	{
		parent::__construct();
		$this->table_name = "Me_Ma_Association";
		$this->fields = $this->db->list_fields($this->table_name);
		$this->primary_key = "Me_Ma_Association";
	}
}