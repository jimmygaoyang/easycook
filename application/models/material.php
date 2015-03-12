
<?php
class Material extends MY_Model
{
	function __construct()
	{
		parent::__construct();
		$this->table_name = "Material";
		$this->fields = $this->db->list_fields($this->table_name);
		$this->primary_key = "Material";
	}

	

}