<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*==============================================================================

                             L O G I N                


DESCRIPTION
  This controller loads the login page.

Copyright (c) 2012 by Cluster Wireless, Incorporated.  All Rights Reserved.

==============================================================================*/

/*==============================================================================

EDIT HISTORY FOR FILE

This section contains comments describing changes made to the module.
Notice that changes are listed in reverse chronological order.



when         who     what, where, why
--------     ---     -----------------------------------------------------------
08/01/2013   ERD     Option to display the login page
==============================================================================*/

class Login extends CI_Controller {


  /**
   * FUNCTION: construct()
   * 
   * Constructor function of the controller
   * 
   * @param  none
   * @return none
   */
    function __construct()
    {
       parent::__construct();
       
       $this->load->helper(array('form'));
       // $this->load->model('user','',TRUE);
       // $this->load->model('role','',TRUE);
       
       $this->data['title'] = "login";
       $this->module_name = "USER INTERFACE";
       $host= gethostname();
       $this->IP = gethostbyname($host);
    }
     


  /**
   * FUNCTION: index()
   * 
   * Index function of the controller
   * Loads the view file for user login
   * 
   * @param  none
   * @return none
   */
    public function index()
    {
        // if ($this->session->userdata($_SERVER['SERVER_NAME'] . "logged_in"))
        // {
        //    // redirect('index.php/dashboard', 'refresh');
        //    //redirect('index.php/device_mgmt', 'refresh');
        // }
        // else
        // {
        //    $this->load->view('/pages/login_view',$this->data);
        // }

        $this->load->view('/pages/login_view',$this->data);
      }


    public function error($error = "") 
    {
      $this->data["error"] = $error;
      $this->load->view('login_view',$this->data);
    }
    
  /**
   * FUNCTION: submit()
   * 
   * This function handles the form validation in the server side
   * 
   * @param  none
   * @return none
   */
    function submit()
    {
        //This method will have the credentials validation
        // $this->load->library('form_validation');
        // $this->form_validation->set_error_delimiters('<span class="error">', '</span>');
        // $this->form_validation->set_rules('name', 'Username', 'trim|required|xss_clean');
        // $this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean|callback_check_database');
        // if($this->form_validation->run() == FALSE)
        // {
        //    $this->index();
        // }
        // else
        // {
        //    // redirect('index.php/dashboard', 'refresh');
        //    redirect('index.php/device_mgmt', 'refresh');
        // }
    }


  /**
   * FUNCTION: check_database()
   * 
   * This is a callback function called during form validation to 
   * check the username and password matches. 
   * 
   * If the password matches, a session is created for the user.
   * If not, the user is not allowed to login.
   * 
   * @param  string  password
   * @return boolean TRUE/FALSE
   */
    function check_database($password)
    {
       // $criteria = array();
       // $criteria["and"] = array ( "User_Name" => $this->input->post('name'), "Password" => md5($password) );
       // //query the database
       // $user = $this->users->read($criteria);
       // if($user)
       // {
       //    $sess_array = array();
       //    $this->user_id = $user[0]["User_Id"];
       //    $this->user_name = $user[0]["User_Name"];
       //    $this->role_id = $user[0]["Role_Id"];
       //    $this->utility_id = $user[0]["Utility_Id"];
       //    $role = $this->role->read_by_key($this->role_id);
       //    if($role)
       //      {
       //         $this->role_name = $role["Role_Name"];
       //      }
         
       //    $utility = $this->utility->read_by_key($this->utility_id);
       //    if($utility)
       //      {
       //         $this->utility_name = $utility["Utility_Name"];
       //      }
         
       //    $sess_array = array (
       //     'user_id'   => $this->user_id,
       //     'user_name' => $this->user_name,
       //     'role_id'   => $this->role_id,
       //     'utility_id'=> $this->utility_id,
       //     'role_name' => $this->role_name,
       //     'utility_name' => $this->utility_name 
       //    );
       //    //$user = $this->user_name;
       //    $this->session->set_userdata($_SERVER['SERVER_NAME'] . "logged_in", $sess_array);
       //    //access logging
       //    $user = $this->user_name;
       //    $this->logservice->log($this->module_name,"TRACE","ACCESS",$this->IP,"User logged in :$user");
       //    return TRUE;
       // }
       // else
       // {
       //    $this->form_validation->set_message('check_database', 'Enter valid username or password');
       //    return FALSE;
       // }
    }


  /**
   * FUNCTION: logout()
   * 
   * This function handles logout feature.
   * 
   * @param  none
   * @return none
   */
    public function logout()
    {
        $tmp = $this->session->userdata($_SERVER['SERVER_NAME'] .'logged_in');
        $user = $tmp['user_name'];
        //access logging
        $this->logservice->log($this->module_name,"TRACE","ACCESS",$this->IP,"User logged out :$user");
        $this->session->unset_userdata($_SERVER['SERVER_NAME'] . "logged_in");
        $this->data['status'] = 1;
        $this->load->view('login_view',$this->data);
    }
    
    
}
