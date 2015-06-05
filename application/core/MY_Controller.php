<?php  if (! defined('BASEPATH')) exit('No direct script access allowed');

/*===========================================================================

                           M Y    C O N T R O L L E R                      

DESCRIPTION
  This file base controller for all the UI controllers.

Copyright (c) 2011 by Cluster Wireless, Incorporated.  All Rights Reserved.

===========================================================================*/

/*===========================================================================

                              EDIT HISTORY FOR FILE

  This section contains comments describing changes made to the module.
  Notice that changes are listed in reverse chronological order.



when         who     what, where, why
--------     ---     ----------------------------------------------------------
03/02/2012   ERD     defined a base controller for handling sessions.
                     All the UI controllers should include this to validate session.

==============================================================================*/
@session_start();
class My_Controller extends CI_Controller
{

     /**
     * FUNCTION: construct()
     *
     * Constructor function
     *
     * @param  No inputs
     * @return No value
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model("device_specification");
        $this->load->model("package");
        $this->load->model("group_specification");
        $this->load->model("role");
        $config = parse_ini_file("/cluster/Cluster_Portfolio_Management/applications/doms/controllers/device_manager_server/doms_config.ini");
        $this->data['version'] = "Version_" . $config['VERSION'];
        
        $this->_check_auth();
        $this->check_user_status();
        $this->_verify_access();
        $this->data['session_data'] = $this->session->userdata($_SERVER['SERVER_NAME'] . "logged_in");
        date_default_timezone_set("UTC");
    }


     /**
     * FUNCTION: _check_auth()
     *
     *  Function to check the user login authentication
     *
     * @param  No inputs
     * @return No value
     */
    private function _check_auth()
    {
        if ( ! $this->session->userdata($_SERVER['SERVER_NAME'] . "logged_in"))
        {
          if($this->input->is_ajax_request())
          {
            $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate, no-transform, max-age=0, post-check=0, pre-check=0");
            $this->output->set_header("Pragma: no-cache");
            echo '<script>window.location = "login/error/1";</script>';
          }
          else
          {
            //If no session, redirect to login page
            $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate, no-transform, max-age=0, post-check=0, pre-check=0");
            $this->output->set_header("Pragma: no-cache");
            redirect('index.php/login/error/1', 'refresh');
          }
        }
    }


    private function check_user_status() {
      $error = 0;
      $this->load->model("users");
      $this->load->model("utility");
      
      $data = $this->session->userdata($_SERVER['SERVER_NAME'] . "logged_in");

      $util_criteria = array();
      $util_criteria["and"] = array("Utility_Id" => $data["utility_id"], "Utility_Status" => 'active');
      $util_result = $this->utility->read($util_criteria);

      if (!empty($util_result)) {
        $criteria_role = array();
        $criteria_role["and"] = array("Role_Name" => $data["role_name"], "Status" => 'active');
        $this->load->model("role");
        
        $role_result = $this->role->read($criteria_role);

        if (!empty($role_result)) {       
          $criteria = array();
          $criteria["and"] = array("User_Id" => $data["user_id"], "Status" => 'active');
          $this->load->model("users");
          $result = $this->users->read($criteria);

          if (empty($result)) {
            $error = 4;
          }
        }
        else {
          $error = 5;
        } 
      }
      else {
        $error = 3;
      }
      
      if ($error != 0) {
          //If no session, redirect to login page
          $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate, no-transform, max-age=0, post-check=0, pre-check=0");
          $this->output->set_header("Pragma: no-cache");
          redirect("index.php/login/error/".$error, 'refresh');        
      }
    }
    
     /**
     * FUNCTION: _verify_access()
     *
     *  Function to check if the logged in user has access
     *  to the page requested. 
     *
     * @param  No inputs
     * @return No value
     */
    private function _verify_access()
    {
      $carrier_access = array( 'add_device',
                               'add_product',
                               'add_oem',
                               'create_utility',
                               'dashboard',
                               'device_details',
                               'device_update',
                               'device_specs',
                               'login',
                               'oem_details',
                               'product_details', 'status',
                               'utility_details','services','help','diag_query','dev_log_manager','dev_stack_manager','dev_heap_manager',
                             'device_crash','device_stack','device_heap', 'add_bulk_device'
      		);

      $utility_access = array( 'add_to_group', 'application_details', 'assign_devconfig',
                               'assign_package','create_group','create_package',
                               'createappconfig', 'createdevconfig',
                               'dashboard', 'device_config', 'device_details',
                               'device_specs', 'device_update', 'diag_query','dev_log_manager','dev_stack_manager','dev_heap_manager',
                               'device_crash','device_stack','device_heap',
                               'group_details','login','modify_group',
                               'oem_details','package_details','product_details',
                               'send_notification','uploadFile','utility_details','services',
                               'help', 'status'
                             );
 
      $common_access = array( 'add_to_group', 'application_details', 'assign_devconfig',
                               'assign_package','create_group','create_package',
                               'createappconfig', 'createdevconfig',
                               'dashboard', 'device_config', 'device_details',
                               'device_provision', 'device_specs', 'device_update', 'diag_query','dev_log_manager','dev_stack_manager','dev_heap_manager',
                               'device_crash','device_stack','device_heap',
                               'group_details','login','modify_group',
                               'oem_details','package_details','product_details',
                               'send_notification','uploadFile','utility_details','services',
                               'help', 'status'
                             );      
      
      $device_id_funcs = array('load_device_specs_view','update');

      $group_id_funcs = array('by_group');
      
      $package_id_funcs = array('by_package');
      
      $data['session_data'] = $this->session->userdata($_SERVER['SERVER_NAME'] . "logged_in");

      $role = $data['session_data']['role_name'];
      if(strcmp($role,"admin")==0)
      {
         return;
      }
      else if(strcmp($role,"carrier")==0 && in_array($this->uri->segment(1),$carrier_access) )
      {
         return;
      }
      else if(strcmp($role,"utility")==0 && in_array($this->uri->segment(1),$utility_access) )
      {
         if(strcmp($this->uri->segment(1),"device_specs") == 0 && in_array($this->uri->segment(2),$device_id_funcs))
         {
           if(strcmp($this->uri->segment(2),"update") == 0)
             $device_id = $this->uri->segment(4,0);
           else
            $device_id = $this->uri->segment(3,0); 
           
           if($device_id != 0)
           {
             $utility_id = $data['session_data']['utility_id'];
             
             $criteria["and"] = array("Utility_Id" => $utility_id , "Device_Id" => $device_id );
             $count = $this->device_specification->count_results($criteria);
             if($count > 0)
               return;
             else
               redirect('index.php/dashboard', 'refresh');
           } 
         }
         else if ( (strcmp($this->uri->segment(1),"application_details") == 0 || strcmp($this->uri->segment(1),"group_details") == 0 ) && in_array($this->uri->segment(2),$package_id_funcs))
         {
           $package_id = $this->uri->segment(3,0);
           if($package_id != 0)
           {
             $utility_id = $data['session_data']['utility_id'];

             $criteria["and"] = array("Utility_Id" => $utility_id , "Package_Id" => $package_id);
             $count = $this->package->count_results($criteria);
             if($count > 0)
               return;
             else
             redirect('index.php/dashboard', 'refresh');
           }
         }
         else if ( strcmp($this->uri->segment(1),"device_details") == 0 && in_array($this->uri->segment(2),$group_id_funcs))
         {
           $group_id = $this->uri->segment(3,0);
           if($group_id != 0)
           {
             $utility_id = $data['session_data']['utility_id'];
         
             $criteria["and"] = array("Utility_Id" => $utility_id , "Group_Id" => $group_id);
             $count = $this->group_specification->count_results($criteria);
             if($count > 0)
               return;
             else
               redirect('index.php/dashboard', 'refresh');
           }
         }
         
      }
      else if (in_array($this->uri->segment(1),$common_access)) {
        
       if(strcmp($this->uri->segment(1),"device_specs") == 0 && in_array($this->uri->segment(2),$device_id_funcs))
        {
           if(strcmp($this->uri->segment(2),"update") == 0)
             $device_id = $this->uri->segment(4,0);
           else
            $device_id = $this->uri->segment(3,0); 
           
           if($device_id != 0)
           {
             $utility_id = $data['session_data']['utility_id'];
             
             $criteria["and"] = array("Utility_Id" => $utility_id , "Device_Id" => $device_id );
             $count = $this->device_specification->count_results($criteria);
             if($count > 0)
               return;
             else
               redirect('index.php/dashboard', 'refresh');
           } 
         }
         else if ( (strcmp($this->uri->segment(1),"application_details") == 0 || strcmp($this->uri->segment(1),"group_details") == 0 ) && in_array($this->uri->segment(2),$package_id_funcs))
         {
           $package_id = $this->uri->segment(3,0);
           if($package_id != 0)
           {
             $utility_id = $data['session_data']['utility_id'];

             $criteria["and"] = array("Utility_Id" => $utility_id , "Package_Id" => $package_id);
             $count = $this->package->count_results($criteria);
             if($count > 0)
               return;
             else
             redirect('index.php/dashboard', 'refresh');
           }
         }
         else if ( strcmp($this->uri->segment(1),"device_details") == 0 && in_array($this->uri->segment(2),$group_id_funcs))
         {
           $group_id = $this->uri->segment(3,0);
           if($group_id != 0)
           {
             $utility_id = $data['session_data']['utility_id'];
         
             $criteria["and"] = array("Utility_Id" => $utility_id , "Group_Id" => $group_id);
             $count = $this->group_specification->count_results($criteria);
             if($count > 0)
               return;
             else
               redirect('index.php/dashboard', 'refresh');
           }
         } 
      }
      else
      {
        $this->session->unset_userdata($_SERVER['SERVER_NAME'] . "logged_in");
        redirect('index.php/login/error/2', 'refresh');
      }
    }
    
    public function get_tz_time($utc_time, $time_zone) {
      
      if (strtotime($utc_time)) {
        $dateTime = new DateTime($utc_time,new DateTimeZone('UTC')); 
        $dateTime->setTimeZone(new DateTimeZone($time_zone)); 
        return $dateTime->format('Y-m-d H:i:s');    
      } 
      else {
        return $utc_time;
      }  
    }
    
}
