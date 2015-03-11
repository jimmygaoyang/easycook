<?php  if (! defined('BASEPATH')) exit('No direct script access allowed');


/*==============================================================================

                      M Y _ M O D E L    C L A S S 


DESCRIPTION
  This file defines the various core database operations performed

Copyright (c) 2012 by Cluster Wireless, Incorporated.  All Rights Reserved.

==============================================================================*/

/*==============================================================================

                              EDIT HISTORY FOR FILE

  This section contains comments describing changes made to the module.
  Notice that changes are listed in reverse chronological order.


when         who     what, where, why
--------     ---     ----------------------------------------------------------
01/08/2013   ERD    Created base functions for the core database operations             

==============================================================================*/
class My_Model extends CI_Model
{
  /**
   * Name of the module to which the class belongs
   * @var string 
   */
  protected $module_name;
  
    /**
     * table_name 
     * 
     * name of the table
     * @var string 
     */
  public $table_name;
  
  
    /**
     * fields  
     * 
     * list of fields in the table
     * @var array
     */  
  public $fields;
  
  
    /**
     * primary_key 
     * 
     * primary key of the table
     * @var string 
     */  
  public $primary_key;
  
  
    /**
     *  VERIFY_FIELD_NAMES
     *
     *  This toggles the verification of field names before 
     *  data is run through the database. (Set to TRUE to enable) 
     *
     *  @var boolean
     */  
  const VERIFY_FIELD_NAMES = FALSE;
  
  
    /**
     * __construct 
     * 
     * The constructor function
     * 
     * @return void
     */  
  public function __construct($config = FALSE)
  {
    parent::__construct();

    if (empty($config)) {
      $config = parse_ini_file("database_config.ini");    
    }
    
    $this->db = $this->load->database($config, TRUE);  
    
    
    $this->load->library("LogService");
    $this->module_name = "DB MANAGER";
    $host= gethostname();
    $this->IP = gethostbyname($host);
  }
  


    /**
     * confirm_keys
     *
     * This method checks to ensure that keys used in array are actually fields
     * in the database table.
     *
     * @param   array   $data_array     This is a keyed array who's keys are supposed to be database field names.
     * 
     * @return  boolean True  if all fields passed or VERIFY_FIELD_NAMES constant is set to FALSE  
     *                  False if fields failed.
     */
  public function confirm_keys($data_array = '') 
  {
      
      if(!self::VERIFY_FIELD_NAMES)
      {
        return TRUE;
      }
        // Verify if an array has been passed
      if (is_array($data_array)) 
      {
          // Go through each item
          foreach ($data_array as $potential_field => $value) 
          {
              // Check to see if the fieldname is in the array
              if (!in_array($potential_field, $this->fields)) 
              {
                  $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Confirm Key Failed:The key \"$potential_field\" does not match with the database fields");
                  return FALSE;
              }
          }
          if(empty($data_array))
          {
             $this->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP,"Empty Data array in confirm keys");
             return FALSE;
          }
          // If we made it here all fields must be true...
          $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"Confirm keys success");
          return TRUE;
      } 
      else 
      {
          // log the error that data_array was not an array
          $this->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP,"Parameter not set");
          return FALSE;
      }
    }


  /**
   * Set the options paramter to set select fields, sort and to set limit for the 
   * read operation
   * @param array $options
   */
  public function check_options($options) 
  {
    // check options array
    if (!empty($options)) 
    {
      // set distinct
      if (isset($options['distinct'])) 
      {
        $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Options distinct set");
        $this->db->distinct();
      }

      // set the sort order
      if (isset($options['sort'])) 
      {
        if (is_array($options['sort'])) 
        {
          foreach ($options['sort'] as $order_by => $direction) 
          {
            $this->db->order_by($order_by, $direction);
          }
          $this->logservice->log($this->module_name, "DEBUG", "GENERAL", $this->IP,"Options sort order by set");
        }
      }

      // set the limit and offset
      if (isset($options['limit'])) 
      {
        if (is_array($options['limit'])) 
        {
          if (isset($options['limit']['limit']) && isset($options['limit']['offset'])) 
          {
            $this->db->limit($options['limit']['limit'],$options['limit']['offset']);
          } 
          else if (isset($options['limit']['limit'])) 
          {
            $this->db->limit($options['limit']['limit']);
          }
          $this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP, "Options limit set");
        }
      }
    }
  }


    /**
     * create 
     *
     * This function creates an entry based on $data_in
     *
     * @param   array    $data_in       A keyed array of data key = field name, value = value
     * 
     * @return  boolean  True if successful / False if not
     */
  public function create($data_in = '')
  {
    // Verify if an array has been passed
    if (is_array($data_in)) 
    {
      // check the field names
      if (!$this->confirm_keys($data_in)) 
      {
        $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Confirm Keys Failed");
        return FALSE;
      }

      // insert the data!
      if ($this->db->insert($this->table_name, $data_in) !== FALSE) 
      {
          $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Data inserted successfully");
          $this->logservice->log($this->module_name, "DEBUG", "EVENT", $this->IP,"Insert Query: " . $this->db->last_query());
          return $this->db->insert_id();
      } 
      else 
      {
          $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "Data insertion error");
          $this->logservice->log($this->module_name, "ERROR", "EVENT", $this->IP,"Insert Query: " . $this->db->last_query());
          return FALSE;
      }
    }
    else 
    {
        $this->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Parameter not set");
        return FALSE;
    }
  }


    /**
     * update
     *
     * This function updates entries that meet the listed criteria with the input data.
     *
     * @param   array   $data_in     A keyed array with the data to insert (key = db field name, value = value to insert)
     * @param   array   $criteria    A keyed array with the criteria for selecting what entries to edit.
     * 
     * @return  mixed   Return Object of results on success, Boolean False on failure
     */
  public function update($data_in = '' , $criteria = '') 
  {
    // Verify if an array has been passed
    if (is_array($data_in)) 
    {
      // check the field names
      if (!$this->confirm_keys($data_in)) 
      {
        $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "Confirm keys failed");
        return FALSE;
      }
      else
      {
        // set the where condition
        if(is_array($criteria))
        {
          $this->set_criteria($criteria);
        }      
        
         // Returns false on problem, or object on success.
         $query = $this->db->update($this->table_name, $data_in);
         $this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP, "Update Query: " . $this->db->last_query());
         if($this->db->_error_number())
         {
           $this->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP,"ErrNo:" . $this->db->_error_number() .  $this->db->_error_message());
           if($this->db->_error_number() == 2006)
           {
             $this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP, "Re-establishing the connection to the database");
             $this->db->reconnect();
             $query = $this->db->update($this->table_name, $data_in);
           }
         }
         return $query;
      }
    } 
    else 
    {
       $this->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Parameter not set");
       return FALSE;
    }
  }


    /**
     * set_criteria
     *
     * This function sets the 'where' condition in the database query statement
     *
     * @param   array   $criteria    A keyed array with the criteria for selecting entries (key=field_name, value=field_value)
     * 
     * @return  boolean   Returns TRUE if successful / FALSE on failure
     */
  
  public function set_criteria ($criteria = '')
  {
    // check if the 'and' array is set
    if(isset($criteria['and']))
    {
      if (is_array($criteria['and'])) 
      {
        if(empty($criteria['and']))
        {
          $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Empty array passed");
          return FALSE;
        }
        else
        {
          $this->db->where($criteria['and']);
          return TRUE;
        }
      }
    }
  
    // check if the 'or' array is set
    if(isset($criteria['or']))
    {
      if (is_array($criteria['or'])) 
      {
        if(empty($criteria['or']))
        {
          $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "Empty array passed");
          return FALSE;
        }
        else
        {
          $this->db->or_where($criteria['or']);
          return TRUE;
        }
      }
    }
  }


  /**
   * read_by_key
   * 
   * Get a single record by creating a WHERE clause with
   * a value for your primary key
   *
   * @param string $primary_value The value of your primary key
   * @return object
   */
  public function read_by_key($primary_value)
  {
    $this->db->where($this->primary_key, $primary_value);
    $query = $this->db->get($this->table_name);
    $this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP, "Read_by_key Query:" . $this->db->last_query());
    if($this->db->_error_number())
    {
      $this->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "ErrNo:" . $this->db->_error_number() .  $this->db->_error_message());
      if($this->db->_error_number() == 2006)
      {
        $this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP, "Re-establishing the connection to the database");
        $this->db->reconnect();
        $query = $this->db->get($this->table_name);
      }
    }
    return  $query->row_array();
  }


/**
 * read
 *
 * This function retrieves a series of db entries based on criteria.
 *
 * @param   array   $criteria       A keyed array of criteria key = field name, value = value, key may also contain comparators (=, !=, >, etc..)
 * @param   array   $fields         An array of fields to be selected / null if all fields should be selected
 * @param   array   $options        An array with key 'distinct' set if distinct records should be retrieved    
 *
 * @return  mixed   Return Object of results on success, Boolean False on failure
 */
  public function read($criteria = '', $fields = '', $options = '', $like_array = '') 
  {
    // Verify if an array has been passed
    if (is_array($fields)) 
    {
       $this->db->select($fields);    
    }

    //set the criteria
    if(is_array($criteria))
    {
      if(!$this->set_criteria($criteria))
      {
        $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "set_criteria error");
        return FALSE;
      }
    }
    
    if(is_array($like_array))
    {
      if(!$this->db->like($like_array))
      {
        {
          $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "Set like error");
          return FALSE;
        }
      }
    }
    //check for options
    if(is_array($options))
    {
      if(isset($options['distinct']))
      {
        $this->db->distinct();
      }
    }
    
    $query = $this->db->get($this->table_name);
    $this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP,"Read Query: " .$this->db->last_query());
    if($this->db->_error_number())
    {
      $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "ErrNo:" . $this->db->_error_number() .  $this->db->_error_message());
      if($this->db->_error_number() == 2006)
      {
        $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Re-establishing the connection to the database");
        $this->db->reconnect();
        $query = $this->db->get($this->table_name);
      }
    }
    return $query->result_array();
  }


  
  public function my_read($options = '')
  {
    $array = array('GDC_Association.Status' => 'active' ,
'DG_Association.Status' => 'active'
    );
  	$this->db->select('Device_Specification.Device_Id,Device_Specification.Device_Name ,Group_Specification.Group_Name,
  			Device_Configuration.Configuration_Name, 
  			Device_Specification.Device_Status ,Utility.Utility_Name ,
  			OEM.OEM_Name,Device_Specification.Device_IP,Device_SerialType.Type,
  			Device_Specification.Device_Serial_No,Device_Specification.SMS_No,
        OEM_Product.OEM_Product_Name,Platform.Platform_Name,Device_Specification.Description');
  	$this->db->from('Device_Specification');
  	$this->db->join('DG_Association', 'Device_Specification.Device_Id = DG_Association.Device_Id');
  	$this->db->join('GDC_Association', 'GDC_Association.Group_Id = DG_Association.Group_Id');
  	$this->db->join('Group_Specification', 'Group_Specification.Group_Id =  DG_Association.Group_Id');
  	$this->db->join('Device_Configuration', 'Device_Configuration.Device_Configuration_Id = GDC_Association.Device_Config_Id');
  	$this->db->join('Utility', 'Utility.Utility_Id = Device_Specification.Utility_Id');
  	$this->db->join('OEM_Product', 'OEM_Product.OEM_Product_Id = Device_Specification.OEM_Product_Id');
  	$this->db->join('OEM', 'OEM.OEM_Id = OEM_Product.OEM_Id');
  	$this->db->join('Device_SerialType', 'Device_SerialType.Device_SerialType_Id = Device_Specification.Device_SerialType_Id');
  	$this->db->join('Platform', 'Platform.Platform_Id = Device_Specification.Platform_Id');
  	$this->db->where($array);
  	if(is_array($options))
  	{
  		$this->check_options($options);
  	}
  	$query = $this->db->get();
  	$this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP, "my_read Query: " . $this->db->last_query());
  	return $query->result_array();
  }

    /**
     * read_sort
     *
     * This function is similar to the read function, in addition, we can pass the sort order in the options array
     *
     * @param   array   $criteria       A keyed array of criteria key = field name, value = value, key may also contain comparators (=, !=, >, etc..)
     * @param   array   $fields         An array of fields to be selected / null if all fields should be selected
     * @param   array   $options        An array with following keys
     *                    'distinct' - if distinct records should be retrieved
     *                     'sort' - keyed array (key=field_name , value=sort_direction)   
     *
     * @return  mixed   Return Object of results on success, Boolean False on failure
     */
  public function read_sort($criteria = '', $fields = '' , $options = '', $like_array = '') 
  {
    // Verify if an array has been passed
    if (is_array($fields)) 
    {
       $this->db->select($fields);
    } 

    // set the criteria
    if(is_array($criteria))
    {
      if(!$this->set_criteria($criteria))
      {
        $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "set_criteria error");
        return FALSE;
      }
    }

    if(is_array($like_array))
    {
      if(!$this->db->like($like_array))
      {
        {
          $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "Set like error");
          return FALSE;
        }
      }
    }
    // check the options
    if(is_array($options))
    {
      if(isset($options['distinct']))
      {
        $this->db->distinct();
      }
      
      if(isset($options['sort']))
      {
        if (is_array($options['sort'])) 
        {
          foreach ($options['sort'] as $order_by => $direction) 
          {
            $this->db->order_by($order_by, $direction);
          }
        }
      }
    }
    
      $query = $this->db->get($this->table_name);
      $this->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP, "Read_sort Query: " . $this->db->last_query());
      if($this->db->_error_number())
      {
        $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "ErrNo:" . $this->db->_error_number() .  $this->db->_error_message());
        if($this->db->_error_number() == 2006)
        {
          $this->logservice->log($this->module_name, "DEBUG","GENERAL",$this->IP, "Re-establishing the connection to the database");
          $this->db->reconnect();
          $query = $this->db->get($this->table_name);
        }
      }
      return $query->result_array();
  }



    /**
     * read_sort_limit
     *
     * This function is similar to the read_orderby function, in addition, we can pass the limit in the options array
     *
     * @param   array   $criteria       A keyed array of criteria key = field name, value = value, key may also contain comparators (=, !=, >, etc..)
     * @param   array   $fields         An array of fields to be selected / null if all fields should be selected
     * @param   array   $options        An array with following keys
     *                    'distinct' - if distinct records should be retrieved
     *                     'sort' - keyed array (key=field_name , value=sort_direction)
     *                     'limit' - keyed array (keys=limit, offset)  
     *
     * @return  mixed   Return Object of results on success, Boolean False on failure
     */  
  public function read_sort_limit( $criteria = '', $fields = '', $options = '', $like_array = '') 
  {
    // Verify if an array has been passed
    if (is_array($fields)) 
    {
       $this->db->select($fields);
    }
    
    // set the criteria
    if(is_array($criteria))
    {
      if(!$this->set_criteria($criteria))
      {
        $this->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP,"set_criteria error");
        return FALSE;
      }
    }
    
    if(is_array($like_array))
    {
      if(!$this->db->like($like_array))
      {
        {
          $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "Set like error");
          return FALSE;
        }
      }
    }
    
    //set the options
    if(is_array($options))
    {
      $this->check_options($options);
    }
    
    $query = $this->db->get($this->table_name);
    $this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP, "Read_sort_limit Query: " . $this->db->last_query());
    if($this->db->_error_number())
    {
       $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "ErrNo:" . $this->db->_error_number() .  $this->db->_error_message());
       if($this->db->_error_number() == 2006)
       {
         $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Re-establishing the connection to the database");
         $this->db->reconnect();
         $query = $this->db->get($this->table_name);
       }
    }
    return $query->result_array();
  }
  

  /**
   * Read the entries of the table based on multiple values of a column 
   * and within the time(in seconds)from the current time.
   * 
   * @param string $column_name Column Name whose values from the AND or OR condition.
   * @param array  $criteria
   *               i)  $criteria['and'] Array containing the values to form AND condition.
   *               ii) $criteria['or']  Array containing the values to form OR condition.
   * @param array  $criteria_where      A keyed array of criteria key = field name, value = value, key may also contain comparators (=, !=, >, etc..)
   * @param array  $options             An array with following keys
   *                   i) 'distinct' -  if distinct records should be retrieved
   *                   ii)  'sort'   -  keyed array (key=field_name , value=sort_direction)
   *                   iii)  'limit' -  keyed array (keys=limit, offset)
   *   
   * @return FALSE On error.
   * @return array On success : Array of the result based on the parameters.
   */
  public function read_by_column_values($column_name, $criteria = '', $criteria_where = '', $options = '', $fields = '') 
  {
    // Verify if an array has been passed
    if (is_array($fields)) 
    {
      $this->db->select($fields);
    }
    
    // set the criteria
    if (is_array($criteria_where)) 
    {
      if(!$this->set_criteria($criteria_where))
      {
        $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "set_criteria error");
        return FALSE;
      }
    }

    // Check if $column_name is present in the $this->fields array.
    if (!in_array($column_name, $this->fields)) 
    {
      $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "column \"$column_name\" doesnot match with table fields ");
      return FALSE;
    }

    // Validate $criteria['and'] parameter.
    if (!empty($criteria['and'])) 
    {
      $this->db->where_in($column_name, $criteria['and']);
    }

    // Validate $criteria['or'] parameter.
    if (!empty($criteria['or'])) 
    {
      $this->db->or_where_in($column_name, $criteria['or']);
    }

    if (!empty($criteria['not_and']))
    {
      $this->db->where_not_in($column_name, $criteria['not_and']);
    }
    
    // Validate $criteria['or'] parameter.
    if (!empty($criteria['not_or']))
    {
      $this->db->or_where_not_in($column_name, $criteria['not_or']);
    }
        
    //set the options
    if(is_array($options))
    {
      $this->check_options($options);
    }

    $query = $this->db->get($this->table_name);
    $this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP, "Read_by_column_values Query: " . $this->db->last_query());
    if($this->db->_error_number())
    {
       $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "ErrNo:" . $this->db->_error_number() .  $this->db->_error_message());
       if($this->db->_error_number() == 2006)
       {
         $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Re-establishing the connection to the database");
         $this->db->reconnect();
         $query = $this->db->get($this->table_name);
       }
    }
    return $query->result_array();
  }

/**

   * Read the entries of the table based on multiple values of multiple columns 
   * and within the time(in seconds)from the current time.
   * 
   * @param string $column_name Column Names whose values from the AND or OR condition.
   * @param array  $criteria

   *               i)  $criteria['and'] Array containing the values to form AND condition.
   *               ii) $criteria['or']  Array containing the values to form OR condition.
   * @param array  $criteria_where      A keyed array of criteria key = field name, value = value, key may also contain comparators (=, !=, >, etc..)
   * @param array  $options             An array with following keys
   *                   i) 'distinct' -  if distinct records should be retrieved
   *                   ii)  'sort'   -  keyed array (key=field_name , value=sort_direction)
   *                   iii)  'limit' -  keyed array (keys=limit, offset)
   *   
   * @return FALSE On error.
   * @return array On success : Array of the result based on the parameters.
   */
  public function read_by_multiple_column_values($column_name, $criteria = '', $criteria_where = '', $options = '', $fields = '') 
  {
    // Verify if an array has been passed
    if (is_array($fields)) 
    {
      $this->db->select($fields);
    }
    
    // set the criteria
    if (is_array($criteria_where)) 
    {
      if(!$this->set_criteria($criteria_where))
      {
        $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "set_criteria error");
        return FALSE;
      }
    }

    // Check if $column_names are present in the $this->fields array.
    foreach($column_name as $column)
    {
    if (!in_array($column, $this->fields)) 
    {
      $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "column \"$column\" doesnot match with table fields ");
      return FALSE;
    }
    }
    // Validate $criteria['and'] parameter.
    if (!empty($criteria['and'])) 
    {
      foreach($column_name as $column)
      {
      if(isset($criteria['and'][$column]))
      $this->db->where_in($column, $criteria['and'][$column]);
      }
    }

    // Validate $criteria['or'] parameter.
    if (!empty($criteria['or'])) 
    {
      foreach($column_name as $column)
      {
      if(isset($criteria['or'][$column]))
      $this->db->or_where_in($column, $criteria['or'][$column]);
      }
    }

    if (!empty($criteria['not_and']))
    {
      foreach($column_name as $column)
      {
      if(isset($criteria['not_and'][$column]))
      $this->db->where_not_in($column, $criteria['not_and'][$column]);
      }
    }
    
    // Validate $criteria['or'] parameter.
    if (!empty($criteria['not_or']))
    {
      foreach($column_name as $column)
      {
      if(isset($criteria['not_or'][$column]))
      $this->db->or_where_not_in($column, $criteria['not_or'][$column]);
      }
    }
        
    //set the options
    if(is_array($options))
    {
      $this->check_options($options);
    }

    $query = $this->db->get($this->table_name);
    $this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP, "Read_by_multiple_column_values Query: " . $this->db->last_query());
    if($this->db->_error_number())
    {
       $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "ErrNo:" . $this->db->_error_number() .  $this->db->_error_message());
       if($this->db->_error_number() == 2006)
       {
         $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Re-establishing the connection to the database");
         $this->db->reconnect();
         $query = $this->db->get($this->table_name);
       }
    }
    return $query->result_array();
  }
    /**
     * delete
     *
     * This function deletes entries based on criteria input.
     * 
     * @param   array   $criteria     A keyed array with the critera for selecting what entries to delete.
     * 
     * @return  mixed   Return Object of results on success, Boolean False on failure
     */
    public function delete($criteria = '') 
    {
      if (is_array($criteria)) 
      {
         // Set the Criteria
         if(!$this->set_criteria($criteria))
         {
            $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"set_criteria error");
            return FALSE;
         }
         $query = $this->db->delete($this->table_name);// Returns false on problem, or object on success.
         $this->logservice->log($this->module_name, "DEBUG", "EVENT", $this->IP, "Delete Query: " . $this->db->last_query());
         if($this->db->_error_number())
         {
           $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "ErrNo:" . $this->db->_error_number() .  $this->db->_error_message());
           if($this->db->_error_number() == 2006)
           {
             $this->logservice->log($this->module_name, "DEBUG", "GENERAL", $this->IP,"Re-establishing the connection to the database");
             $this->db->reconnect();
             $query = $this->db->delete($this->table_name);
           }
         }
         return $query;
     } 
     else 
     {
        $this->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP, "parameter not set");
        return FALSE;
     }
   }


    /**
     * count_all
     *
     * This function returns the number of entries in the table
     *
     *
     * @return  int   Returns the number of entries 
     */
    public function count_all() 
    {
        $query = $this->db->count_all($this->table_name);
        $this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP, "Count_all Query: " . $this->db->last_query());
        if($this->db->_error_number())
        {
          $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "ErrNo:" . $this->db->_error_number() .  $this->db->_error_message());
          if($this->db->_error_number() == 2006)
          {
            $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Re-establishing the connection to the database");
            $this->db->reconnect();
            $query = $this->db->count_all($this->table_name);
          }
        }
        return $query;
    }


    /**
     * count_results
     *
     * This function counts the number of entries based on the criteria
     *
     * @param   array   $criteria       A keyed array of criteria key = field name, value = value, key may also contain comparators (=, !=, >, etc..)
     *
     * @return  int   Returns the number of entries satisfying the criteria
     */
  public function count_results($criteria = '') 
  {
      // set the criteria
      if(is_array($criteria))
      {
        if(!$this->set_criteria($criteria))
        {
          $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "set_criteria error");
          return FALSE; 
        }
      }
      
        $this->db->from($this->table_name);
        $query = $this->db->count_all_results();
        $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Count_results Query: " . $this->db->last_query());
        if($this->db->_error_number())
        {
          $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "ErrNo:" . $this->db->_error_number() .  $this->db->_error_message());
          if($this->db->_error_number() == 2006)
          {
            $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Re-establishing the connection to the database");
            $this->db->reconnect();
            $query = $this->db->count_all_results();
          }
        }
        return $query;
    }


    /**
     * is_entry_unique
     *
     * This function checks if there is any entry satisfying the criteria
     *
     * @param   array   $criteria       A keyed array of criteria key = field name, value = value, key may also contain comparators (=, !=, >, etc..)
     *
     * @return  boolean   TRUE if unique, otherwise, FALSE
     */
    public function is_entry_unique($criteria = '')
    {
        $result = $this->count_results($criteria);
        if($this->db->_error_number())
        {
          $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "ErrNo:" . $this->db->_error_number() .  $this->db->_error_message());
          if($this->db->_error_number() == 2006)
          {
            $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Re-establishing the connection to the database");
            $this->db->reconnect();
            $result = $this->count_results($criteria);
          }
        }
        if ($result == 0) 
        {
            $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "is_entry_unique Query: " . $this->db->last_query());
            $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Entry will be unique");
            return TRUE;
        } 
        else 
        {
            $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "is_entry_unique Query: " . $this->db->last_query());
            $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Entry will not be unique");
            return FALSE;
        }
    }
   
    public function truncate()
    {
      $this->db->empty_table($this->table_name);
      $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "truncate Query: " . $this->db->last_query());
    }
    
    public function transaction_start()
    {
      $this->db->trans_begin();
      $this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP, "Begin Transaction");
      return $this->db->trans_status();
    }

    public function commit()
    {
    $this->db->trans_commit();
    $this->logservice->log($this->module_name, "DEBUG", "EVENT", $this->IP,"Commit Transaction");
    return $this->db->trans_status();
    }

    public function rollback()
    {
    $this->db->trans_rollback();
    $this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP, "Rollback Transaction");
    return $this->db->trans_status();
    }


  public function read_by_column_like_values($column_name, $criteria = '', $criteria_where = '', $options = '', $fields = '') 
  {
    // Verify if an array has been passed
    if (is_array($fields)) 
    {
      $this->db->select($fields);
    }
    
    // set the criteria
    if (is_array($criteria_where)) 
    {
      if(!$this->set_criteria($criteria_where))
      {
        $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "set_criteria error");
        return FALSE;
      }
    }

    // Validate $criteria['and'] parameter.
    if (!empty($criteria['and'])) 
    {
      if (isset($column_name["and"]))
        $this->db->where_in($column_name["and"], $criteria['and']);
    }

    // Validate $criteria['or'] parameter.
    if (!empty($criteria['or'])) 
    {
      if (isset($column_name["or"]))
        $this->db->or_where_in($column_name["or"], $criteria['or']);
    }

    if (!empty($criteria['not_and']))
    {
      if (isset($column_name["not_and"]))
        $this->db->where_not_in($column_name["not_and"], $criteria['not_and']);
    }
    
    if (!empty($criteria['not_or']))
    {
      if (isset($column_name["not_or"]))
        $this->db->or_where_not_in($column_name["not_or"], $criteria['not_or']);
    }

    if (!empty($criteria['and_like']))
    {
      if (isset($column_name["and_like"])) {
        foreach ($criteria["and_like"] as $value) {
          $this->db->like($column_name["and_like"], $value);
        }
      }
    }
    
    // Validate $criteria['or'] parameter.
    if (!empty($criteria['or_like']))
    {
      if (isset($column_name["or_like"])) {
        foreach ($criteria["or_like"] as $value) {
          $this->db->or_like($column_name["or_like"], $value);
        }
      }
    }
    
    if (!empty($criteria['not_and_like']))
    {
      if (isset($column_name["not_and_like"])) {
        foreach ($criteria["not_and_like"] as $value) {
          $this->db->not_like($column_name["not_and_like"], $value);
        }        
      }
    }
    
    // Validate $criteria['or'] parameter.
    if (!empty($criteria['not_or_like']))
    {
      if (isset($column_name["not_or_like"])) {
        foreach ($criteria["not_or_like"] as $value) {
          $this->db->or_not_like($column_name["not_or_like"], $value);
        }        
      }
    }
            
    //set the options
    if(is_array($options))
    {
      $this->check_options($options);
    }

    $query = $this->db->get($this->table_name);
    $this->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP, "Read_by_column_values Query: " . $this->db->last_query());
    if($this->db->_error_number())
    {
       $this->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "ErrNo:" . $this->db->_error_number() .  $this->db->_error_message());
       if($this->db->_error_number() == 2006)
       {
         $this->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Re-establishing the connection to the database");
         $this->db->reconnect();
         $query = $this->db->get($this->table_name);
       }
    }
    return $query->result_array();
  }
}
