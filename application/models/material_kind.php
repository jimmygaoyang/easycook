
<?php
class Material_kind extends MY_Model
{
	function __construct()
	{
		parent::__construct();
		$this->table_name = "Material_Kind";
		$this->fields = $this->db->list_fields($this->table_name);
		$this->primary_key = "Material_Kind_Id";
	}
}