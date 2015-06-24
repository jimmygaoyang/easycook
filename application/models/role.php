
<?php
class Role extends MY_Model
{
	function __construct()
	{
		parent::__construct();
		$this->table_name = "Role";
		$this->fields = $this->db->list_fields($this->table_name);
		$this->primary_key = "Role_Id";
	}
}