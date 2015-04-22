<?php 

/*===========================================================================

                           D E V I C E    M A N A G E R                      

DESCRIPTION
  This file handles all the communication between the device and the O&M server.
All the communication takes place through an encrypted socket connection.   

Copyright (c) 2011 by Cluster Wireless, Incorporated.  All Rights Reserved.

===========================================================================*/

/*===========================================================================

                              EDIT HISTORY FOR FILE

  This section contains comments describing changes made to the module.
  Notice that changes are listed in reverse chronological order.



when         who     what, where, why
--------     ---     ----------------------------------------------------------
03/02/2012   ERD     defined an encrypted interface for device communication.


==============================================================================*/
require_once("messages.php");
include(dirname(__FILE__) . "/../application/libraries/Database.php");
class device_manager 
{

    /**
     * The master socket to receive the device messages
     * @var resource 
     */
    public $master;
    
    protected $udp;
    protected $db;
    
    /**
     * Array containing all the connected devices 
     * @var array
     */
    public $clients;

    /**
     * Array containing the device socket resource
     * @var array
     */
    public $device_sock_resource;

    /**
     * Array containing the device ids
     * @var array
     */
    public $device_id;

    /**
     * variable to keep track of the device socket resources
     */
    public $index = 0;

    /**
     * Array containing the recorded time of the last messsage received
     */
    public $recorded_time;

    /**
     * stream context for the device socket
     */
    public $device_context;

    /**
     * Array containing the common name in the device certificates
     */
    public $device_common_name;

    public $fm;

    /**
     * FUNCTION: __construct()
     * 
     * constructor function
     * 
     * @param  No inputs 
     * @return No value
     */
    public function __construct() 
    {
        $this->db = new Database("CookMaster", "root", "jinsui");
        //$this->load->library("LogService");
        $this->config = parse_ini_file("doms_config.ini");
        $this->clients = array();
        $this->module_name = "DEVICE MANAGER";
        $host= gethostname();
        $this->IP = gethostbyname($host);
    }


    /**
     * FUNCTION: index()
     * 
     * index function of the controller
     *
     * @param  No inputs
     * @return No value
     */
    public function index() 
    {
        $this->master = $this->start(); 
    }


    /**
     * FUNCTION: start()
     * 
     * starts the server by calling the functions to create server sockets,
     * waits on select function call
     * calls the functions to accept the client connections and handle the messages
     *
     * @param  No inputs
     * @return No value
     */
    public function start() 
    {
        $write = array();  // Array containing the sockets which changed status
        $except = array();

        // create master socket to listen on the defined port
        $port = $this->config['DEVICE_MANAGER_PORT'];
        $udp_port = $this->config['DM_UDP_SERVER_PORT'];
        
        
        $this->master = $this->create_tcp_socket("0.0.0.0", $port);   
        if ($this->master === FALSE) 
        {
            $this->db->logservice->log($this->module_name, "FATAL","ALARM",$this->IP, "Socket Creation Error");
            return;
        }

        $this->udp = $this->create_udp_socket($udp_port);
        
        if (!$this->udp )
        {
          $this->db->logservice->log($this->conf['module_name'], "ERROR", "ALARM",$this->IP, "Udp Socket Creation error");
          return;
        }
        
        $this->db->logservice->log($this->module_name, "DEBUG", "EVENT", $this->IP,"listening on port: $port...for device messages");
        $this->db->logservice->log($this->module_name, "DEBUG", "EVENT", $this->IP, "listening on port: $udp_port...for device messages");

        // infinite loop
        while (true) 
        {
            // build the array of sockets to select
          
            $read = array_merge(array($this->master), array($this->udp),$this->clients);

            // waiting on select
            $select = stream_select($read, $write, $except, NULL);
            if ($select === FALSE) 
            {
               $this->db->logservice->log($this->module_name, "FATAL","ALARM", $this->IP,"Socket select error");
               break;
            }

            if ($select > 0) 
            {
              $this->handle_socket($read);
            }
        }// end of infinite loop         
    }


    /**
     * FUNCTION: create_ssl_socket()
     * 
     * Creates a server stream socket,binds and listens on a port.
     *
     * @param string $address address on which the socket listens
     * @param string $port    port on which the socket listens
     * 
     * @return mixed socket resource on success, boolean FALSE on failure    
     */
        public function create_tcp_socket($address, $port) 
    {

        //create a stream context for our SSL settings
        $this->device_context = stream_context_create();

        //create a stream socket on IP:Port
        $socket = stream_socket_server("tcp://{$address}:{$port}", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $this->device_context);
        if (!$socket)
        {
          return FALSE;
        }
            
        return $socket;
    }


    
    public function relist_missed_notification_messages($device_id)
    {
      $threshold_time = time() - 5; //Five seconds from current time
      $threshold_string = date('Y-m-d H:i:s', $threshold_time);

      $rs = $this->db->query("update Device_Notification set Status = '0',Last_Modified = now() where Device_Id = $device_id 
                              and Message not like 'RESET'
                              and Last_Modified > '$threshold_string' and Status = '1'");
      if($rs !== false)
      {
        $this->db->logservice->log($this->module_name, "INFO", "GENERAL",$this->IP, "Relisting missed device notification messages");
        $dcm_list = $this->db->query("select Ip, Port from DOMS_Address where Description = 'dcm' and Status = 'active'");
        while($this->db->SingleRecord($dcm_list))
        {
          $sockfd = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
          if($sockfd != false)
          {
            $rv = socket_sendto($sockfd, "wakeup", 6, 0, $this->db->Record['Ip'], $this->db->Record['Port']);
            socket_close($sockfd);
          }
        }     
      }
    }
    
    public function create_udp_socket($udp_port)
    {
      $this->udp = @stream_socket_server("udp://127.0.0.1:$udp_port",$errno,$errstr,STREAM_SERVER_BIND);
      return $this->udp;
    }

    /**
     * FUNCTION: handle_socket()
     *
     * Handles the different client connections and messages from the  
     * different clients
     *
     * @param  array $read array of sockets which changed status  
     *          
     * @return No value    
     */
    public function handle_socket($read) 
    {
        global $DEVICE_DEREG_MSG;

        //if the master's status changed,it means a new client would like to connect
        if (in_array($this->master, $read)) 
        {
         /*
          * Stream Select can hold only 1024 sockets. Check if maximum is reached
          */
          if(sizeof($this->clients) >= 1022) //Server and UDP wakeup sockets use one fd each
          {
            $this->db->logservice->log($this->module_name, "FATAL", "ALARM",$this->IP, "Maximum number of active sockets present. Cannot accept connection");
          }
          else
          {
            // attempt to create a new socket
            $socket = @stream_socket_accept($this->master, "-1", $clientIp);
            $ip = str_getcsv($clientIp, ":");

            //socket creation error
            if($socket === FALSE) 
            {
              $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Cannot accept the device connection");
            }
            else //if socket created successfuly,add it to the clients array and write message log 
            {              
                //Set the socket to non-blocking mode. Temporary fix
                stream_set_blocking($socket, FALSE);
                // $this->device_context_options = stream_context_get_options($this->device_context);
                // $cert = openssl_x509_parse($this->device_context_options["ssl"]["peer_certificate"]);
                // $cuid = strtolower($cert["subject"]["CN"]);    

                // $prev_cuid_index = $this->get_cuid_index($cuid);
                // if($prev_cuid_index != -1)
                // {
                //   $this->db->logservice->log($this->module_name, "INFO", "DEBUG", $this->IP,"Disconnecting duplicate socket connection..");
                //   $this->disconnect_device($this->clients[$prev_cuid_index]);     
                // }
                
                $this->clients[sizeof($this->clients)] = $socket;
                // $this->device_common_name[sizeof($this->clients)-1]=$cuid;
                
                
                // $result = $this->db->query("select Device_Name from Device_Specification where  Device_CUID='$cuid'");
                // if ($this->db->SingleRecord($result))
                // {
                //   $whom = $this->db->Record['Device_Name'];
                //   $this->db->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Device $whom connected to the server");
                // }
                // else
                // {
                //   $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "Unknown device connected to the server");
                // }
                
                $this->db->logservice->log($this->module_name, "DEBUG", "EVENT", $this->IP,"New Device connected:  $socket ($ip[0] : $ip[1])");
            }
            
            if (sizeof($read) == 1) 
            {
                return;
            }
          }
        }

        // foreach client that is ready to be read 
        foreach ($read as $client) 
        {
            // we don't read data from the master socket
            if ($client != $this->master) 
            {
                //Set read timeout to recover the device manager when read blocks
              //  stream_set_timeout($client, 180);
                $input = @fread($client, 1024);
              /*  $info = stream_get_meta_data($client);
                if ($info['timed_out'])
                {
                  $this->db->logservice->log($this->module_name, "ERROR","EVENT", $this->IP,"Stream read timed out for $client");
                  continue;
                  //Read timed out. Continue to next client
                }
                */
                if($client == $this->udp)
                {
                  if($input === "wakeup")
                  {
                    $this->db->logservice->log($this->module_name, "DEBUG","EVENT", $this->IP,"Got UDP trigger : $input");
                    $this->send_connected_device_messages();
                  }
                  else if($input === "cleanup")
                  {
                    $this->db->logservice->log($this->module_name, "DEBUG","EVENT", $this->IP, "Got cleanup trigger : $input");
                    $this->cleanup_zombie_devices();
                  }
                  else
                  {
                    $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Wrong UDP Message : $input");
                  }
                }
                else
                {
                  $info = stream_get_meta_data($client);
                  // if socket_read() returned false, the client has been disconnected
                  if ((strlen($input) == 0) && ($info['eof'] == 1)) 
                  {
                    if (in_array($client, $this->clients)) 
                    {
                       // deregistering the device
                       $device_id = $this->get_device_id($client);
                       $this->db->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Device $device_id closed connection");
                       $this->device_deregistration("$DEVICE_DEREG_MSG $device_id \r\n", FALSE); 
                       $this->relist_missed_notification_messages($device_id);
                       
                    }
                    // disconnect client
                    $this->disconnect_device($client);
                  }
                  else   // else, we received a normal message   
                  {
                    $inx = 0;
                    $parsed = str_getcsv($input, "\r\n");
                    $str_count = count($parsed);
                    while ($inx != $str_count) 
                    {
                      //Remove whitespaces at the beginning and end of string
                      $data = trim($parsed[$inx]);
                      $inx++;
                      //Replace the NULL character with space
                      $data = str_replace("\0", " ", $data);
                      if ($data == "" || $data == " " || $data == "\n" || $data == "\r\n")
                        continue;
                      if (in_array($client, $this->clients))
                        $this->identify_device_message($data, $client);
                    }
                  }
                }
            }
        }
    }


    /** FUNCTION: identify_device_message
     * 
     * Method called after any message is received from the device 
     * The type of the message received is identified based on 
     * the message id and appropriate functions are called.
     *
     * @param string   $data    message sent from the device
     * @param resource $client  device socket resource
     * @return None
     */
    public function identify_device_message($data, $client) 
    {
        global $DEVICE_REG_MSG, $DEVICE_DEREG_MSG, $STATUS_MSG, $ALIVE_MSG, $EVENT_MSG, $DIAGNOSTIC_MSG ,$KPI_MSG,$BUS_MSG;

        $this->db->logservice->log($this->module_name, "ERROR", "ALARM", $this->IP,$data);

        // $var = str_getcsv($data, ' ', '"');
        // $whom = ""; // device name
        // $device_id = 0;
 
        // if ($var[0] == $DEVICE_REG_MSG) 
        // {
        //     if (!isset($var[1]) || !isset($var[2]) || !isset($var[3])) 
        //     {
        //         $this->db->logservice->log($this->module_name, "ERROR", "ALARM", $this->IP,"Invalid Device Registration message..Parameters missing");
        //         return;
        //     }

        //     $cuid = strtolower($var[2]);
        //    // if(isset($this->device_common_name[$cuid]) && strcmp($this->device_common_name[$cuid],$cuid) != 0)
        //     if($this->get_cuid_index(strtolower($cuid)) == -1)
        //     {
        //        $this->db->logservice->log($this->module_name, "ERROR", "ALARM", $this->IP,"CUID does not match");
        //        $this->disconnect_device($client);
        //        return;
        //     }
             
        //     //getting the device name
        //     $result = $this->db->query("select Device_Name from Device_Specification where Device_CUID='$cuid'");
        //     if ( $this->db->SingleRecord($result))
        //     {
        //       $whom = $this->db->Record['Device_Name'];
        //     }
        //     else
        //     {
        //       $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not read device details");
        //     }
        // }
        // else
        // {
        //   $device_id = $this->get_device_id($client);
        //   $result = $this->db->query("select Device_Name from Device_Specification where  Device_Id='$device_id'");
        //   if ($this->db->SingleRecord($result))
        //   {
        //     $whom = $this->db->Record['Device_Name'];
        //   }
        //   else
        //   {
        //     $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "Resource Id:$client");
        //     $this->db->logservice->log($this->module_name, "ERROR", "ALARM", $this->IP,"Could not read device details..DeviceId:$device_id");
            
        //   }
        // }        
        // $this->db->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP, "[From_Device:$whom]$data");
        // $flag = 0;
        // for ($i = 0; $i < $this->index; $i++) 
        // {
        //     if ($this->device_id[$i] == $device_id) // device id is present in the array
        //     { 
        //         $flag = 1;
        //         break;
        //     }
        // }
        // if ($flag == 1) 
        // {   // device is active
        //     $this->recorded_time[$i] = getdate();
        // }

        // switch ($var[0]) 
        // {
        //     case $DEVICE_REG_MSG : /* -----------------------------------------
        //                                 Device Registration Message
        //                             ---------------------------------------- */
        //                             $this->device_registration($data, $client);
        //                             break;


        //     case $DEVICE_DEREG_MSG : /* -------------------------------------------
        //                                      Device Deregistration Message
        //                              --------------------------------------------- */
        //                              $this->device_deregistration($data, TRUE);
        //                              break;

        //     case $STATUS_MSG :       /* -----------------------------------------
        //                                       Status Message from Device
        //                               ---------------------------------------- */
        //                              $this->update_device_sleep_time($device_id);
        //                              $this->set_last_contact_time($device_id);
        //                              $this->status($data, $device_id);
        //                              break;

        //     case $EVENT_MSG :        /* -----------------------------------------
        //                                        Event Message from Device
        //                               ---------------------------------------- */
        //                               $this->update_device_sleep_time($device_id);
        //                               $this->set_last_contact_time($device_id);
        //                               $this->events($data, $device_id);
        //                               break;
        //     case $KPI_MSG :        /* -----------------------------------------
        //                                        KPI Message from Device
        //                               ---------------------------------------- */
        //                               $this->update_device_sleep_time($device_id);
        //                               $this->set_last_contact_time($device_id);
        //                               $this->process_kpi_message($data, $device_id);
        //                               break;
        //     case $BUS_MSG :        /* -----------------------------------------
        //                                        BUS Message from Device
        //                               ---------------------------------------- */
        //                               $this->update_device_sleep_time($device_id);
        //                               $this->set_last_contact_time($device_id);
        //                               $this->process_bus_message($data, $device_id);
        //                               break;

        //     default:  $this->db->logservice->log($this->module_name, "ERROR", "ALARM", $this->IP,"\"$data\" - INVALID MESSAGE RECEIVED FROM DEVICE");            
        // }
    }
    
    
    /** FUNCTION: update_device_sleep_time
     *
     * This method is called whenever a device message is received.
     * This method updates the sleep time based on the keepalive period sent during 
     * device registration.
     *
     * @param integer $device_id - Device ID
     *
     * @return none
     */
    public function update_device_sleep_time($device_id)
    {
      $result = $this->db->query("select Keep_Alive_Period from Device_Specification where Device_Id = $device_id");
      if (!$this->db->SingleRecord($result))
      {
        $this->db->logservice->log($this->module_name, "ERROR","ALARM", $this->IP, "Could not read device details");
      }
      else
      {
        $dev_keep_alive = $this->db->Record['Keep_Alive_Period'];
        $sleep_time = date("Y-m-d H:i:s",(time() + $dev_keep_alive + $this->config['KEEPALIVE_GRACE_PERIOD']));
        $result = $this->db->query("update Device_Specification set Last_Modified = now(), Sleep_Time = '$sleep_time' where  Device_Id=$device_id");
        if($result === 'fail')
        {
          $this->db->logservice->log($this->module_name, "ERROR", "ALARM", $this->IP,"Could not update device sleep time");
        }
      }
    }

    /** FUNCTION: set_last_contact_time
     *
     * This method is called whenever a device message is received.
     * This method updates the last_contact_time. 

     *
     * @param integer $device_id - Device ID
     *
     * @return none
     */
    public function set_last_contact_time($device_id)
    {
      $result = $this->db->query("select * from Device_Specification where Device_Id = $device_id");
      if (!$this->db->SingleRecord($result))
      {
        $this->db->logservice->log($this->module_name, "ERROR","ALARM", $this->IP, "Could not read device details");
      }
      else
      {
        //$dev_keep_alive = $this->db->Record['Keep_Alive_Period'];
        //$sleep_time = date("Y-m-d H:i:s",(time() + $dev_keep_alive + $this->config['KEEPALIVE_GRACE_PERIOD']));
        $last_contact_time = date("Y-m-d H:i:s");
        $result = $this->db->query("update Device_Specification set Last_Modified = now(), Last_Contact_Time = '$last_contact_time' where  Device_Id=$device_id");
        if($result === 'fail')
        {
          $this->db->logservice->log($this->module_name, "ERROR", "ALARM", $this->IP,"Could not update device last contact time");
        }
        else
        {
          $this->db->logservice->log($this->module_name, "INFO", "EVENT", $this->IP,"Last contact time updated");
        }
      }
    }


    /** FUNCTION: device_registration
     *
     * Method called after the REG_F message is received from the device 
     *
     * MSG FORMAT:   REG_F VERSION CUID LICENCE_TYPE
     *
     * after receiving the CUID, this function will check the database for 
     * the CUID,if it's available it makes the status as active and enter the DEVICE IP
     * in database. Then it sends the DEVICE_ID in the ACK message
     *
     * ACK MSG FORMAT: REG_R DEVICE_ID
     *
     * @param string $data
     * @param resource $client
     *
     * @return boolean SUCCESS | FAILURE
     */
    public function device_registration($data, $client) 
    {
        global $DEVICE_REG_RESP, $DEVICE_REG;

        $dev_heart_beat = 0;

        //Get The Device IP Address
        $device_ip = stream_socket_get_name($client, true);
        $str = str_getcsv($device_ip, ":");
        $device_ip = $str[0];
        $dev_keep_alive = $this->config['DEFAULT_DEV_KEEPALIVE'];
        //storing the space seperated string in associative arrays
        $values = str_getcsv($data, ' ', '"');

        if (!isset($values[1]) || !isset($values[2]) || !isset($values[3])) 
        {
          $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Invalid device registration..Parameters missing");
            return FALSE;
        }

        $middleware_version = $values[1];
        $cuid = $values[2];

        //Check Whether Incoming device Is Present In The Databse
        
        $device_id = 0;
        $result = $this->db->query("select Device_Id from Device_Specification where  Device_CUID='$cuid'");
        if ($this->db->SingleRecord($result))
        {
          $device_id = $this->db->Record['Device_Id'];
        }
        else
        {
          $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not read device details");
        //  return;
        }
        
        if ($device_id) 
        {
            $flag = 1;
            for ($i = 0; $i < $this->index; $i++) 
            {
                if ($this->device_id[$i] == $device_id) 
                {
                    $flag = 0;
                    break;
                }
            }

            $today = getdate();
           if ($flag == 0) // if device id already registered
            { 
              //TMP_fix CODE HERE
          
              $this->disconnect_device($this->device_sock_resource[$i]);
              //TMP_FIX ENDS HERE
                // overwriting device's socket resource
                //$this->device_sock_resource[$i] = $client; //COMMENTED FOR TMP_fix
                //$this->recorded_time[$i] = $today; //COMMENTED FOR TMP_fix
            } 
          //  else // device registering for the first time //COMMENTED FOR TMP_fix
          //  { //COMMENTED FOR TMP_fix
                //storting device socket resource and the device id
                $this->device_sock_resource[$this->index] = $client;
                $this->device_id[$this->index] = $device_id;
                $this->recorded_time[$this->index] = $today;
                $this->index++;
          //  }//COMMENTED FOR TMP_fix

            // updating device status and device IP
            $date = date('Y-m-d h:i:s');
            if(isset($values[5]) && (is_numeric($values[5])))
            {
              //TODO Handle Device 0 keepalive properly
              if($values[5] == 0)
                $dev_keep_alive = 99999999999 - $this->config['KEEPALIVE_GRACE_PERIOD'];
              else 
                $dev_keep_alive = $values[5];
            }

            if(isset($values[6]))
            {
              $dev_heart_beat = $values[6];
            }


            $sleep_time = date("Y-m-d H:i:s",(time() + $dev_keep_alive + $this->config['KEEPALIVE_GRACE_PERIOD']));
    
            $last_contact_time = date("Y-m-d H:i:s");
          
            $result = $this->db->query("update Device_Specification set Device_Status = 'active', Device_Ip = '$device_ip',
                Last_Modified = now(),Wakeup_Attempts = 0, Keep_Alive_Period = $dev_keep_alive, Sleep_Time = '$sleep_time', Last_Contact_Time = '$last_contact_time',Heartbeat_Interval = $dev_heart_beat where  Device_Id=$device_id");
          
            //date_default_timezone_set("UTC");
            $date = gmdate('Y-m-d H:i:s');
            $date_format = array();
            $date = explode(" ", $date);
            $date_format['date'] = $date[0];
            $date_format['Timestamp'] = 'T';
            $date_format['time'] = $date[1];
            $date_format['Zone'] = 'Z';
            $date = implode("",$date_format);
            
            $test_data = "EVENT $device_id $date RESOLVED SERV DEVSTAT Device active"; 
 //           $this->fm->handle_serverevents($test_data,0);

            $result = $this->db->query("select count(*) from KPI_Small where  Device_Id=$device_id and Parameter='Middleware_Version'");
            if ($this->db->SingleRecord($result))
            {
              $result = $this->db->Record['count(*)'];
            }            
            // check if  there is any kpi entry for this device
            if ($result == 0) 
            {
                // insert kpi data
                $result = $this->db->query("insert into KPI_Small (Device_Id,Parameter,Value,Last_Modified)
                    values ($device_id,'Middleware_Version','$middleware_version',now())");
                if($result === "fail")
                {
                  $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not create KPI data");
                }
            } 
            else
            {
                $result = $this->db->query("update KPI_Small set  Value = '$middleware_version',
                  Last_Modified = now() where Device_Id = $device_id and Parameter = 'Middleware_Version'");
                if($result === "fail")
                {
                  $this->db->logservice->log($this->module_name, "ERROR", "ALARM", $this->IP,"Could not update KPI details");
                }
            }

          if(isset($values[4]))
          {
           if($values[4] == 1)
           {
             
            $boot_time = gmdate("Y-m-d H:i:s"); 

            $result = $this->db->query("select count(*) from KPI_Small where  Device_Id=$device_id and Parameter='Boot_Time'");
            if ($this->db->SingleRecord($result))
            {
              $result = $this->db->Record['count(*)'];
            }            
            // check if  there is any kpi entry for this device
            if ($result == 0) 
            {
                // insert kpi data
                $result = $this->db->query("insert into KPI_Small (Device_Id,Parameter,Value,Last_Modified)
                    values ($device_id,'Boot_Time','$boot_time',now())");
                if($result === "fail")
                {
                  $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not create KPI data");
                }
            } 
            else
            {
                $result = $this->db->query("update KPI_Small set  Value = '$boot_time',
                  Last_Modified = now() where Device_Id = $device_id and Parameter = 'Boot_Time'");
                if($result === "fail")
                {
                  $this->db->logservice->log($this->module_name, "ERROR", "ALARM", $this->IP,"Could not update KPI details");
                }
            }

           }
          }


          /*
            $result = $this->db->query("select count(*) from KPI_Parameters where  Device_Id=$device_id");
            if ($this->db->SingleRecord($result))
            {
              $result = $this->db->Record['count(*)'];
            }            
            // check if  there is any kpi entry for this device
            if ($result == 0) 
            {
                // insert kpi data
                $result = $this->db->query("insert into KPI_Parameters (Device_Id,Middleware_Version,Last_Modified)
                    values ($device_id,'$middleware_version',now())");
                if($result === "fail")
                {
                  $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not create KPI data");
                }
            } 
            else if($result == 1)
            {
                $result = $this->db->query("update KPI_Parameters set  Middleware_Version = '$middleware_version',
                  Last_Modified = now() where Device_Id = $device_id");
                if($result === "fail")
                {
                  $this->db->logservice->log($this->module_name, "ERROR", "ALARM", $this->IP,"Could not update KPI details");
                }
            }
            */

            //sending the registration response to the device

            $msg = $DEVICE_REG_RESP . " " . $device_id . "\r\n";

            $this->send($client, $msg);

            //sending the sms no to the device
            $this->send_sms_no($client, $device_id);

            //sending the pending messages to the device
            $this->send_device_messages($device_id, $client);
            
            if ($this->config["ADMS_NOTIFY"] == "On") {
              //sending the device details to utility
              $this->send_device_details($device_id);
            }
        } 
        else //device NOT PRESENT IN THE DATABASE 
        {
          $this->db->logservice->log($this->module_name, "ERROR", "ALARM", $this->IP,"Device not present in the database");
          $message = "$DEVICE_REG_RESP 0 \r\n";
          $this->send($client, $message);
          $this->db->logservice->log($this->module_name, "INFO", "DEBUG", $this->IP,"Disconnecting invalid device..");
          $this->disconnect_device($client);
        }
    }


    /** FUNCTION: send_device_details
     *
     * This functions enters the device details notification to the utility
     * into the utility notification table, which the utility manager polls
     * periodically to send the notificaitons to the utility. 
     *
     * @param  integer $device_id - Device ID
     * 
     * @return None
     */
    public function send_device_details($device_id) 
    {
        global $DEVICE_DETAILS;
        
        $result = $this->db->query("select * from Device_Specification where  Device_Id=$device_id");
        if ($this->db->SingleRecord($result))
        {
          $utility_id = $this->db->Record['Utility_Id'];
          $device_id = $this->db->Record['Device_Id'];
          $device_name = $this->db->Record['Device_Name'];
          $device_status = $this->db->Record['Device_Status'];
          $latitude = $this->db->Record['Latitude'];
          $longitude = $this->db->Record['Longitude'];
        }
        

        $device_details = array();
        $device_details[] = $DEVICE_DETAILS;
        $device_details[] = $device_id;
        $device_details[] = $device_name;
        $device_details[] = $device_status;
        $device_details[] = $utility_id;
        $device_details[] = $latitude;
        $device_details[] = $longitude;

        $send_msg = implode(",", $device_details);
        $date = date('Y-m-d h:i:s');
        
        $result = $this->db->query("insert into Utility_Notification (Device_Id,Utility_Id,Recorded_Time,Last_Modified,Status,Message)
            values
               ($device_id,$utility_id,now(),now(),'0','$send_msg')");
        if($result === "fail")
        {
          $this->db->logservice->log($this->module_name, "ERROR", "ALARM", $this->IP,"Could not create Utility Notification");
        }
    }


    /** FUNCTION: send_device_messages
     *
     * This function retrieves the pending messages for the device in the database
     * and send them to the device. 
     *
     * @param  integer   $device_id  device id
     * @param  resource  $client     socket resource of the device
     * 
     * @return None
     */
    public function send_device_messages($device_id, $client) 
    {
      $result = $this->db->query("select Message, Device_Notification_Id from Device_Notification where Device_Id= '$device_id' and Status = '0'");
      while ($this->db->SingleRecord($result))
      {
        $device_notification_id = $this->db->Record['Device_Notification_Id'];
        $message = $this->db->Record['Message'];
        $return = $this->send($client,$message. "\r\n");
        if ($return == false)
        {
          $this->db->logservice->log($this->module_name, "ERROR","ALARM",$this->IP, "Socket write failed for device id :$device_id");
          $this->disconnect_device($client);
          $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "Disconnecting device socket..");
          $this->db->logservice->log($this->module_name, "INFO", "EVENT", $this->IP, " Setting device status to sleep..");
          
          $rv = $this->db->query("update Device_Specification set Device_Status='sleep', Last_Modified = now(), Wakeup_Attempts = 0 where Device_Id = $device_id");

          //date_default_timezone_set("UTC");
          $date = gmdate('Y-m-d H:i:s');
          $date_format = array();
          $date = explode(" ", $date);
          $date_format['date'] = $date[0];
          $date_format['Timestamp'] = 'T';
          $date_format['time'] = $date[1];
          $date_format['Zone'] = 'Z';
          $date = implode("",$date_format);
          
          $test_data = "EVENT $device_id $date RESOLVED SERV DEVSTAT Device in sleep state"; 
//          $this->fm->handle_serverevents($test_data,0);      
          $dcm_list = $this->db->query("select Ip, Port from DOMS_Address where Description = 'dcm' and Status = 'active'");
          while($this->db->SingleRecord($dcm_list))
          {
            $sockfd = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if($sockfd != false)
            {
              $rv = socket_sendto($sockfd, "wakeup", 6, 0, $this->db->Record['Ip'], $this->db->Record['Port']);
              socket_close($sockfd);
            }
          }
          break;
        }
        else
        {
          //  $result1 = $this->db->query("delete from Device_Notification where Device_Notification_Id= '$device_notification_id'");
              $result1 = $this->db->query("update Device_Notification set Status = '1', Last_Modified = now() where Device_Notification_Id = $device_notification_id");
              if($result1 === false)
              {
                $this->db->logservice->log($this->module_name, "ERROR","ALARM",$this->IP, "Unable to update Device Notification");
              }
        }
      }  
    }


    /** FUNCTION: send_connected_device_messages
     *
     * This function calls the send_device_messages function for al
     * connected devices 
     * 
     * @return None
     */
    public function send_connected_device_messages()
    {
      foreach($this->clients as $client)
      {
        $device_id = $this->get_device_id($client);
        if($device_id != 0)
        {
          $this->send_device_messages($device_id,$client);
        }
      }
    }


    /** FUNCTION: send_sms_no
     *
     * This function is used to send the SMS no to the device
     * 
     * @param resource $client     Socket resource of the device
     * @param int      $device_id  Device Id
     * 
     * @return None
     */
    public function send_sms_no($client, $device_id) 
    {
      global $DEVICE_CONFIG_MSG;
      $result = $this->db->query("select SMS_No from Device_Specification where Device_Id= $device_id");
      if( $this->db->SingleRecord($result))
      {
        $sms_no = $this->db->Record['SMS_No'];
        $send_data = $DEVICE_CONFIG_MSG . " " . $sms_no . " " . "\r\n";
        $this->send($client, $send_data);
      }
    }


    /** FUNCTION: device_deregistration
     *
     * Method called after the device Deregistration message(device_DEREG_F) is received
     * MSG FORMAT: DEREG_F device_id
     *
     * after receiving the device_id, it will check the databse for the status of device,
     * if the status is active, it will change it to inactive. 
     *
     * @param  string   $data                   Deregistration message
     * @param  boolean  $utility_notification   TRUE/FALSE
     * 
     * @return boolean success/fail
     */
    public function device_deregistration($data, $utility_notification,$device_status='sleep') 
    {
        global $DEVICE_DEREG;
        
        //storing the space seperated string in array
        $dereg_msg = str_getcsv($data, ' ', '"');

        if (!isset($dereg_msg[1])) 
        {
          $this->db->logservice->log($this->module_name, "ERROR", "GENERAL", $this->IP,"Invalid device deregistration msg..Parameters missing");
            return;
        }
        $device_id = $dereg_msg[1];

        // set device status to sleep
        
        $result = $this->db->query("update Device_Specification set Device_Status='$device_status' , Last_Modified = now(), Wakeup_Attempts = 0 where Device_Id = $device_id");
        
        
        $client = $this->get_device_socket($device_id);
        $this->disconnect_device($client);
        
        if ($this->config["ADMS_NOTIFY"] == "On") {
          $result = $this->db->query("select * from Device_Specification where  Device_Id=$device_id");
          if ($this->db->SingleRecord($result) && $utility_notification === TRUE)
          {
            $utility_id = $this->db->Record['Utility_Id'];
            $device_details = array();
            $device_details[] = $DEVICE_DEREG;
            $device_details[] = $device_id;
            $send_msg = implode(",", $device_details);
            
            $result = $this->db->query("insert into Utility_Notification (Device_Id,Utility_Id,Recorded_Time,Last_Modified,
                Status,Message ) values
                ($device_id,$utility_id,now(),now(),'0','$send_msg')");
            if($result === "fail")
            {
              $this->db->logservice->log($this->module_name, "ERROR", "ALARM", $this->IP,"Could not create Utility Notification");
            }
          }
        } 
         if($device_status === 'sleep')
         {
         	//date_default_timezone_set("UTC");
         	$date = gmdate('Y-m-d H:i:s');
         	$date_format = array();
         	$date = explode(" ", $date);
         	$date_format['date'] = $date[0];
         	$date_format['Timestamp'] = 'T';
         	$date_format['time'] = $date[1];
         	$date_format['Zone'] = 'Z';
         	$date = implode("",$date_format);
         	
          $test_data = "EVENT $device_id $date RESOLVED SERV DEVSTAT Device in sleep state"; 
 //         $this->fm->handle_serverevents($test_data,0);
         }
         else if($device_status === 'dormant')
         {
         	//date_default_timezone_set("UTC");
         	$date = gmdate('Y-m-d H:i:s');
         	$date_format = array();
         	$date = explode(" ", $date);
         	$date_format['date'] = $date[0];
         	$date_format['Timestamp'] = 'T';
         	$date_format['time'] = $date[1];
         	$date_format['Zone'] = 'Z';
         	$date = implode("",$date_format);
         	
          $test_data = "EVENT $device_id $date EMERGENCY SERV DEVSTAT Device in dormant state"; 
 //         $this->fm->handle_serverevents($test_data,0);
         }
          
        
         return TRUE;
    }


    /** FUNCTION: status
     *
     * Method called after the Status Message is received from device
     * MSG FORMAT:   STATUS <different status messages>
     *
     * @param  string $data       Status Message
     * @param  string $device_id  Device ID
     * 
     * @return None
     */
    public function status($data, $device_id) 
    {
        $status_msg = str_getcsv($data, ' ', '"');

        if (!isset($status_msg[1])) 
        {
          $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Invalid Status message..Parameters missing");
            return;
        }

        switch ($status_msg[1]) 
        {
            case "APP":
                $this->appstatus($data, $device_id);
                break;
            case "LINK":
                $this->linkstatus($data, $device_id);
                break;
            case "DIAG":
              $this->diagnostics($data, $device_id);
              break;
            case "DEV":
                $this->devicestatus($data, $device_id);
                break;
            case "LICENSE":
                $this->licensestatus($data, $device_id);
                break;
            case "LOG":
              if(strcmp($status_msg[2],'ALL') == 0)
              {
                $module_msg = substr($data, 15);
                if(false === $module_msg)
                {
                  $this->db->logservice->log($this->module_name, "ERROR", "GENERAL", $this->IP,"Module list could not be extracted from $data");
                }
                else
                {
                  $this->get_logmodules($module_msg, $device_id);
                }
              }
              else
              {
                $this->get_logstatus($data, $device_id);
              }
              break;
            default:
                $this->db->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP, "Invalid Status message");
        }
    }


    /** FUNCTION: linkstatus
     *
     * Method called if the Status Message received from device is link status
     * MSG FORMAT:   STATUS LINK APPL_MAC WIFI_RSSI
     *
     * @param  string  $data       link status message
     * @param  integer $device_id  Device ID
     * 
     * @return None
     */
    public function linkstatus($data, $device_id) 
    {
        $app_mac = "";
        $app_rssi = "";

        $status_msg = str_getcsv($data, ' ', '"');

        if (!isset($status_msg[2]) || !isset($status_msg[3])) 
        {
          $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "Invalid link status message..Parameters missing");
            return;
        }

        $app_mac = $status_msg[2];
        $app_rssi = $status_msg[3];

        $app_mac = str_getcsv($app_mac, ':');
        $app_mac = implode($app_mac);


        $result = $this->db->query("select count(*) from Appliance_Status where  Appliance_MAC='$app_mac'");
        if ($this->db->SingleRecord($result))
        {
          $result = $this->db->Record['count(*)'];
        }

        if ($result == 0) 
        {
          // status for new mac, insert it
          $result = $this->db->query("insert into Appliance_Status values('',$device_id,'$app_mac','$app_rssi',now())");
        } 
        else 
        {
            // if status exists for the mac, update it
            $result = $this->db->query("update Appliance_Status set WIFI_RSSI = '$app_rssi',Device_Id = $device_id,
                       Last_Modified = now() where Appliance_MAC = '$app_mac'");
            if($result === "fail")
            {
              $this->db->logservice->log($this->module_name, "ERROR","ALARM",$this->IP, "Could not update appliance status");
            }
        }
    }


    /** FUNCTION: appstatus
     *
     * Method called if the application Status Message is received from device
     *
     * MSG FORMAT:   STATUS APP APPNAME VERSION STATUS <ERRORMSG>
     *
     * @param  string  $data       App status message
     * @param  integer $device_id  Device ID
     * 
     * @return None
     */
    public function appstatus($data, $device_id)  
    {
        $app_id = 0;
        $app_name = "";
        $app_version = "";
        $app_status = "";
        $error_msg = "";
        $platform_id = 0;
        $utility_id = 0;

        $status_msg = str_getcsv($data, ' ', '"');

        if (!isset($status_msg[2]) || !isset($status_msg[3]) || !isset($status_msg[4]) || !isset($status_msg[5])) 
        {
            $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "Invalid app status message..Parameters missing");
            return;
        }

        $app_name = $status_msg[2];
        $version = str_getcsv($status_msg[3], '@', '"');
        $app_version = $version[0];
        if(count($version) != 2)
        {
           $this->db->logservice->log($this->module_name, "DEBUG","GENERAL", $this->IP, "No App config specified");
           $config_name = "noconfig";
        }
        else
        {
          $configuration_id = $version[1];
          $config_name = str_getcsv($configuration_id, '/', '"');
          $config_name = $config_name[1];
        }
        
        $app_status = $status_msg[4];
        $error_msg = array();
        for ($i = 5; $i <= count($status_msg) - 1; $i++)
        {
          $error_msg[] = $status_msg[$i];
        }
        $error_msg = implode(" ", $error_msg);
        
        $result = $this->db->query("select * from Device_Specification where Device_Id=$device_id");
        if ($this->db->SingleRecord($result))
        {
          $platform_id = $this->db->Record['Platform_Id'];
          $utility_id = $this->db->Record['Utility_Id'];
        }
       
        $result = $this->db->query("select Module_Id from Module_Specification where 
           Name = '$app_name' and Version = '$app_version' and  Utility_Id = $utility_id and Platform_Id = $platform_id and 
            Status = 'active'" );
        if ($this->db->SingleRecord($result))
        {
          $app_id = $this->db->Record['Module_Id'];
        }
        else 
        {
          $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "Application does not exist");
          return;
        }
        $module_configuration_ids = array();
        $result = $this->db->query("select * from MC_Association where Module_Id=$app_id");
        while ( $this->db->SingleRecord($result))
        {
          $module_configuration_ids[] = $this->db->Record['Module_Configuration_Id'];
        }
        if(empty($module_configuration_ids))
        {
          $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "Module Config Association doesnot exist");
          return;
        }
        
        $config_id = 0;
        foreach($module_configuration_ids as $module_configuration_id)
        {
         $result = $this->db->query("select * from Module_Configuration where Module_Configuration_Id=$module_configuration_id");
         if ($this->db->SingleRecord($result))
         {
           $version_config  = $this->db->Record['Configuration_Name'];
           if(strcmp($config_name, $version_config)==0)
           {
              $config_id = $module_configuration_id;
              break;
           }
           else
           {
             continue;
            }
          }
          else
          {
             $this->db->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP, "Could not read Module Config details");
             continue;
          }
        }

        if($config_id == 0)
        {
             $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP, "Module Config doesnot exist");
             return;
        }
        
        // select the application id from HM_Association
        $result = $this->db->query("select count(*) from DM_Association where Device_Id=$device_id and Module_Id = $app_id");
        if ($this->db->SingleRecord($result))
        {
          $unique = $this->db->Record['count(*)'];
        }
        $data = array();
        if ($unique == 0) 
        {
            $result = $this->db->query("insert into DM_Association values ('',$device_id,$app_id,$config_id,'$app_status','$error_msg',now())");
            if($result === "fail")
            {
              $this->db->logservice->log($this->module_name, "ERROR","ALARM",$this->IP, "Could not create device module association");
            }
        } 
        else 
        { 
           /* if($app_status == 'UNINSTALLED' || $app_status == 'UPGRADING')
            {
             $result = $this->db->query("delete from DM_Association where Module_Id = $app_id and Device_Id = $device_id");
             if($result === "fail")
             {
              $this->db->logservice->log($this->module_name, "ERROR","ALARM",$this->IP, "Could not delete uninstalled module entry");
             }
            }
            else
            {*/
             $result = $this->db->query("update DM_Association set Module_Configuration_Id = $config_id,Module_Status ='$app_status',
               Error_Message = '$error_msg',Last_Modified = now() where Module_Id = $app_id and Device_Id = $device_id");
             if($result === "fail")
             {
              $this->db->logservice->log($this->module_name, "ERROR","ALARM",$this->IP, "Could not update application status");
             }
            //}
            
        }
        //$this->fm->check_appmatch($device_id);
    }

    /** FUNCTION: devicestatus
     *
     * Method called if the Status Message received from device is device status
     * MSG FORMAT:   STATUS DEV CONFIG CONFIG_NAME STATE ERROR_MSG
     *
     * @param  string  $data       device status message
     * @param  integer $device_id  Device ID
     * 
     * @return None
     */
    public function devicestatus($data, $device_id) 
    {
        $config_id = "";
        $status = "";
        $error_msg = "";

        $status_msg = str_getcsv($data, ' ', '"');

        if (count($status_msg) < 6) 
        {
            $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Invalid device status message..Parameters missing");
            return;
        }

        $config_id = $status_msg[3];
        $status = $status_msg[4];
        $error_msg = array();
        for ($i = 5; $i <= count($status_msg) - 1; $i++)
        {
          $error_msg[] = $status_msg[$i];
        }
        $error_msg = implode(" ", $error_msg);
        
        $result = $this->db->query("select count(*) from Device_Config_Status where Device_Id=$device_id");
        if ($this->db->SingleRecord($result))
        {
          $unique = $this->db->Record['count(*)'];
        }
        $data = array();
        if($unique == 0)
        {
            $result = $this->db->query("insert into Device_Config_Status values ('',$device_id,$config_id,'$status','$error_msg',now())");
            if($result === "fail")
            {
              $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not create Device_Config_Status ");
            }
        }
        else
        {
            $result = $this->db->query("update Device_Config_Status set Device_Config_Id = $config_id,Status ='$status',
                Error_Message = '$error_msg',Last_Modified = now() where Device_Id = $device_id");
            if($result === "fail")
            {
              $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not update Device_Config_Status");
            }
        }
    }

     /** FUNCTION: licensestatus
     *
     * Method called if the Status Message received from device is license status
     * MSG FORMAT:   STATUS LICENSE VERSION TYPE START_DATE END_DATE
     *
     * @param  string  $data       license status message
     * @param  integer $device_id  Device ID
     * 
     * @return None
     */
    public function licensestatus($data, $device_id) 
    {
        $version = "";
        $type = "";
        $start_date = "";
        $end_date = "";

        $status_msg = str_getcsv($data, ' ', '"');

        if (count($status_msg) < 6) 
        {
            $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Invalid license status message..Parameters missing");
            return;
        }

        $version = $status_msg[2];
        $type = $status_msg[3];
        $start_date = $status_msg[4];
        $end_date = $status_msg[5];
       
        $result = $this->db->query("select count(*) from License_Details where Device_Id=$device_id");
        if ($this->db->SingleRecord($result))
        {
          $unique = $this->db->Record['count(*)'];
        }
        $data = array();
        if($unique == 0)
        {
            $result = $this->db->query("insert into License_Details values ('',$device_id,'$version','$type','$start_date','$end_date',now())");
            if($result === "fail")
            {
              $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not create License_Status ");
            }
            else
            {
              $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "License_Status added");
            }
        }
        else
        {
            $result = $this->db->query("update License_Details set License_Version = '$version',License_Type = '$type',

                Start_Date = '$start_date',End_Date = '$end_date',Last_Updated = now() where Device_Id = $device_id");
            if($result === "fail")
            {
              $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not update License_Status");
            }
            else
            {
              $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "License_Status updated");
            }
        }
    }


    public function get_logmodules($data, $device_id)
    {
      $loop_stat = 0;
      $timestamp = date('Y-m-d h:i:s');
      
      $del_stat = $this->db->query("delete from Device_Log_Status where Device_Id=$device_id");
      if ($del_stat === "fail")
      {
        $this->db->logservice->log($this->module_name, "ERROR","GENERAL", $this->IP,"Diag Log Status - Could not clear entries with device ID $device_id");
        return;
      }
      
      $module_list = explode('@@@', $data);
      if(false === $module_list)
      {
        $this->db->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP, "Could not split module list $data");
        return;
      }
      if(empty($module_list))
      {
        $this->db->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP, "Module list empty - Logging");
        return;
      }
      foreach($module_list as $row)
      {
        if(!empty($row))
        {
          $module = explode(' ', $row);
          if((false === $module) || (count($module) != 2))
          {
            $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Invalid Module parameters in $row");
          }
          else
          {
            
            $is_key_module_name = strstr($module[0], "=");
            if(false === $is_key_module_name)
            {
              $this->db->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP, "Module Name parameter not found in $module[0]");
            }
            else
            {
              $module_name = substr($module[0],7);
              
              $is_key_mw = strstr($module[1], "TYPE=");
              if(false === $is_key_mw)
              {
                $this->db->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP, "Module Type parameter not found in $module[1]");
              }
              else
              {
                $is_middleware = substr($module[1], 5);
                $this->db->logservice->log($this->module_name, "INFO","EVENT",$this->IP, "Module Name is $module_name. Is_Middleware is $is_middleware");
                
                if(strcmp("MIDDLEWARE",$is_middleware) == 0)
                  $type = 'MW';
                else if(strcmp("APP",$is_middleware) == 0)
                  $type = 'APP';
                  
                
                $device_log_status_id = $this->db->query("insert into Device_Log_Status(Device_Id,Module,Type,Received_Time,Last_Modified)
                    values ('$device_id','$module_name','$type',now(),now())");
                
                if($device_log_status_id === "fail")
                {
                  $this->db->logservice->log($this->module_name, "ERROR","ALARM", $this->IP,"Unable to insert into Device Log Status table");
                }
                else
                {
                  $loop_stat = 1;
                }          
              }
            }
          }
        }
      }
      if($loop_stat == 1)
      {
        $msg = "QUERY LOG ALL-APP";
        $stat = $this->create_notification($device_id, $msg);
        if(false === $stat)
        {
          $this->db->logservice->log($this->module_name,"ERROR","ALARM",$this->IP, "Unable to insert into device notificaton table");
        }
        
        $msg = "QUERY LOG ALL-MW";
        $stat = $this->create_notification($device_id,$msg);
        if(false === $stat)
        {
          $this->db->logservice->log($this->module_name,"ERROR","ALARM",$this->IP,"Unable to insert into device notificaton table");
        }
      }
    }
    
    public function get_logstatus($data, $device_id)
    {
      
      $this->db->logservice->log($this->module_name, "INFO", "GENERAL",$this->IP, "Status message is $data");
      $log_message = explode(' ', $data);
      if(count($log_message) != 6)
      {
        $this->db->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP,"Invalid number of tokens in $data");
        return;
      }
      
      $mod_name = explode('=',$log_message[2]);
      if((false === $mod_name) || (count($mod_name) != 2))
      {
        $this->db->logservice->log($this->module_name, "ERROR","GENERAL", $this->IP,"Invalid Module Name parameter : $log_message[2]");
        return;   
      }
      
      if(strcmp("MODULE",$mod_name[0]) != 0)
      {
        $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Invalid Module Name Keyword in : $log_message[2]");
        return;
      }
      
      $msg_level = explode('=',$log_message[3]);
      if((false === $msg_level) || (count($msg_level) != 2))
      {
        $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Invalid Message Level parameter : $log_message[3]");
        return;
      }
      
      if(strcmp("MSG_LVL",$msg_level[0]) != 0)
      {
        $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Invalid Message Level Keyword in : $log_message[3]");
        return;
      }
      
      $log_level = explode('=',$log_message[4]);
      if((false === $log_level) || (count($log_level) != 2))
      {
        $this->db->logservice->log($this->module_name, "ERROR","GENERAL", $this->IP,"Invalid Log Level parameter : $log_message[4]");
        return;
      }
      
      if(strcmp("LOG_LVL",$log_level[0]) != 0)
      {
        $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Invalid Log Level Keyword in : $log_message[4]");
        return;
      }
      
      $plat_level = explode('=',$log_message[5]);
      if((false === $plat_level) || (count($plat_level) != 2))
      {
        $this->db->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP,"Invalid Plat Level parameter : $log_message[5]");
        return;
      }
      
      if(strcmp("PLAT_LVL",$plat_level[0]) != 0)
      {
        $this->db->logservice->log($this->module_name, "ERROR","GENERAL", $this->IP,"Invalid Plat Level Keyword in : $log_message[5]");
        return;
      }
      
      //All success. Now update the table
      $result = $this->db->query("update Device_Log_Status set Log_Level = '$log_level[1]',
          Message_Level = '$msg_level[1]',
          Platform_Level = '$plat_level[1]',
          Last_Modified = now() 
          where Device_Id = $device_id and 
          Module = '$mod_name[1]'");
      if($result === "fail")
      {
        $this->db->logservice->log($this->module_name, "ERROR","ALARM",$this->IP, "Could not update Device_Log_Status");
      }
    }
    
    /**
     * FUNCTION : diagnostics
     * Handle Diagnostic messages.
     * 
     * @param  string  $data       App status message
     * @param  integer $device_id  Device ID
     * 
     * @return None 
     */
    
    public function diagnostics($data, $device_id)
    {
      $diag_msg = str_getcsv($data, ' ', '"');
      switch($diag_msg[2])
      {
        case "HEAP":
          $this->get_heap_stats($data, $device_id);
          break;
        case "STACK":
          $this->get_stack_stats($data, $device_id);
          break;
        case "CRASH":
          $this->process_crash_msg($data, $device_id);      
          break;
        default:
          $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP,"Invalid Diag parameter $diag_msg[3]");
          break;
      }      
    }

    /**
     * FUNCTION: get_heap_stats
     * This function parses the heap statistics message and updates 
     * the heap statistics table.
     * 
     * @param unknown_type $data
     * @param unknown_type $device_id
     */
    public function get_heap_stats($data, $device_id)
    {
      
      $heap_data = explode("@@@", $data);
      $cnt = count($heap_data);
      
      if($cnt != 4)
      {
        $this->db->logservice->log($this->module_name, "ERROR","ALARM", $this->IP,"Invalid Heap Stat Message $data");
        return;
      }
      
      $tot_size = strlen($data);
      
      $recv_size = $tot_size - strlen($heap_data[0]) - 3;
      //This is working.. continue with length check at the top
      
      $this->db->logservice->log($this->module_name, "INFO","EVENT",$this->IP, "Printing Heap stat message:");
      $this->db->logservice->log($this->module_name, "INFO","EVENT",$this->IP, $data);
      
      $header = explode(' ', $heap_data[0]);
      if(count($header) == 0)
      {
        //This case should never happen. This can only be a parse-error
        $this->db->logservice->log($this->module_name, "ERROR","ALARM",$this->IP, "Heap stat header empty");
        return;
      }

      $data_size = $header[3];    
      
      if($data_size != $recv_size)
      {
        $this->db->logservice->log($this->module_name, "ERROR","GENERAL", $this->IP,"Message length mismatch. Expected: $data_size Received: $recv_size");
        return;
      }
      
      $diag_status_id = $this->db->query("insert into Diag_Status values ('','$device_id','heap',now(),'')");
      
      if($diag_status_id === "fail")
      {
        $this->db->logservice->log($this->module_name,"ERROR","GENERAL",$this->IP,"Unable to Insert into Diag Status Table");
        return;
      }  
    
      $diag_status_id = $this->db->last_insertid();

      for($i = 1; $i<$cnt-1; $i++)
      {
        $heap = explode(' ',$heap_data[$i]);
        if(count($heap) == 0)
        {
          $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Heap stat row empty");
        }
        elseif(count($heap) != 4)
        {
          $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Invalid number of tokens in $heap");
        }
        else 
        {
          if(strcmp($heap[0], "HAL") == 0)
          {
            $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP,"Displaying HAL Heap Stats:");        
            $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP,$heap_data[$i]);
            $type = 'MIDDLEWARE';  
            $insert = 1;
          }  
          elseif(strcmp($heap[0], "APP") == 0)
          {
            $this->db->logservice->log($this->module_name, "INFO","EVENT",$this->IP, "Displaying App Heap Stats:");
            $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, $heap_data[$i]);
            $type = 'APPLICATION';
            $insert = 1;
          }
          else
          {
            $this->db->logservice->log($this->module_name, "ERROR", "GENERAL",$this->IP,"Invalid heap type $heap[0]");
          } 
          
          if($insert == 1)
          {
	    
            $diag_heap_id = $this->db->query("insert into Diag_Heap
               values ('','$diag_status_id','$data_size','$type','$heap[1]',
               '$heap[2]','$heap[3]',now(),'')");
            if($diag_heap_id === "fail")
            {
              $this->db->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP, "Error inserting into Diag Heap Table");
            }
          }
        } 
      }    
    }
    
    
    public function get_stack_stats($data, $device_id)
    {
      $stack_data = explode("@@@", $data, 2);
      $header = explode(' ', $stack_data[0]);
      $data_size = $header[3];      
      
      $recvd_size = strlen($stack_data[1]);
      
      if($recvd_size != $data_size)
      {
        $this->db->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP, " Message length mismatch. Expected: $data_size Received: $recvd_size");
        return;
      }
      
      $stack = explode('@@@', $stack_data[1]);
      $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP,"Printing Stack Details:");

      $count = count($stack) - 2;
      
      $tmp = explode("= ",$stack[0]);
      
      if(count($tmp) != 2)
      {
        $this->db->logservice->log($this->module_name,"ERROR", "GENERAL",$this->IP,"Invalid stack count msg: $stack[0]");
        return;
      }
      
      $stack_count = $tmp[1];
      
      if($stack_count != $count)
      {
        $this->db->logservice->log($this->module_name,"ERROR","GENERAL", $this->IP,"Invalid number of stack stats. Expected:$stack_count Received:$count");
        return;
      }
      
      /*
       * Everything Ok. Now populate the tables with received data
       */

      $diag_status_id = $this->db->query("insert into Diag_Status (Device_Id,Status_Type,Last_Modified,Description)
          values ($device_id,'stack',now(),'')");
      
      if($diag_status_id === "fail")
      {
        $this->db->logservice->log($this->module_name,"ERROR","GENERAL", $this->IP,"Unable to Insert into Diag Status Table");
        return;
      }
      $diag_status_id = $this->db->last_insertid();
      $diag_stack_id = $this->db->query("insert into Diag_Stack (Diag_Status_Id,Size,Task_Count,Last_Modified,Description)
          values ($diag_status_id,'$recvd_size','$stack_count',now(),'')");
      if("fail" === $diag_stack_id)
      {
        $this->db->logservice->log($this->module_name,"ERROR","GENERAL",$this->IP, "Unable to Insert into Diag Stack Table");
        return;
      }
      $diag_stack_id = $this->db->last_insertid();
      
      for($i = 1; $i <= $count; $i++)
      {
        $stack_row = explode(' ',$stack[$i]);
        $tokens = count($stack_row);
        if($tokens < 4)
        {
          $this->db->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP,"Stack Details cannot be extracted for a task with Diag_Stack_Id $diag_stack_id");
          return;
        } 
        
        $this->db->logservice->log($this->module_name, "INFO","EVENT",$this->IP, $stack[$i]);
        $task_size_index = $tokens - 3;
        
        $stack_size = $stack_row[$task_size_index]; 
        $stack_usage = $stack_row[$task_size_index+1];
        $free_size = $stack_row[$task_size_index+2];
        
        $task_name = "";
        for($j = 0; $j < $task_size_index; $j++)
        {
          $task_name = $task_name.$stack_row[$j];
          if($j != ($task_size_index - 1))
            $task_name = $task_name." ";
        }
        
        $ret = $this->db->query("insert into Diag_Task (Diag_Stack_Id,Task_Name,
            Task_Size,Stack_Usage,Free_Size,Last_Updated)
            values ($diag_stack_id,'$task_name','$stack_size','$stack_usage',
            '$free_size',now())");
        
        if("fail" === $ret)
        {
          $this->db->logservice->log($this->module_name, "ERROR","GENERAL",$this->IP,"Unable to update Stack details for Diag_Stack_Id $diag_stack_id");
        }
        
      }
    }
    
    
    public function process_crash_msg($data, $device_id)
    {
      $crash_msg = explode('@@@',$data);
      if(count($crash_msg) != 2)
      {
        $this->db->logservice->log($this->module_name, "ERROR","ALARM",$this->IP,"Invalid number of crash parameters in $data");
        return;
      }
      
      $crash_header = explode(' ',$crash_msg[0]);
      if(count($crash_header) != 4)
      {
        $this->db->logservice->log($this->module_name, "ERROR","ALARM",$this->IP,"Invalid number of crash header parameters in $crash_msg[0]");
        return;
      }
      
      $filename_size = $crash_header[3];
      
      if($filename_size != strlen($crash_msg[1]))
      {
        $this->db->logservice->log($this->module_name, "ERROR","ALARM",$this->IP,"Expected $filename_size bytes of data (filename). Received ".strlen($crasb_msg[1]));
        return;
      }
      
      /*
       * Everything Ok. Now populate the tables with received data
      */
      
      $diag_status_id = $this->db->query("insert into Diag_Status (Device_Id,Status_Type,Last_Modified,Description)
          values ($device_id,'crash',now(),'')");
      
      if($diag_status_id === "fail")
      {
        $this->db->logservice->log($this->module_name,"ERROR","GENERAL",$this->IP,"Unable to Insert into Diag Status Table");
        return;
      }
      $diag_status_id = $this->db->last_insertid();
      
      $diag_crash_id = $this->db->query("insert into Diag_Crash (Diag_Status_Id,status,File_Name,
          File_Size,Bytes_Received,Received_Time,Last_Modified)
          values ($diag_status_id,'0', '$crash_msg[1]',0,0,now(),now())");
      
      if($diag_crash_id === false)
      {
        $this->db->logservice->log($this->module_name,"ERROR","GENERAL",$this->IP,"Unable to Insert into Diag Crash Table");
        return;
      }
      
      $this->db->logservice->log($this->module_name,"INFO","EVENT",$this->IP,"Crash file is: $crash_msg[1]");
      //On successful insert send device notification msg, querying FILE contents
      
      $msg = "QUERY FILE ".$crash_msg[1];
     /* $notification_array = array('Device_Id' => $device_id,
                             'Message' => $msg, 'Status' => '0',
                             'Recorded_Time' => date("Y-m-d h:i:s"),
                             'Last_Modified' => date("Y-m-d h:i:s"));*/
      $stat = $this->create_notification($device_id, $msg);
      if(false === $stat)
      {
        $this->db->logservice->log($this->module_name,"ERROR","GENERAL",$this->IP,"Unable to insert into device notificaton table");
      }
    }
    
    
  
    
    /** FUNCTION : stop
     *
     * Stop the server, disconnect all the conected clients, 
     * close the master socket
     * 
     * @param  None
     * @return None
     */
    public function stop() 
    {
        foreach ($this->clients as $client) 
        {
            fclose($client);
        }

        $this->clients = array();

        fclose($this->master);
    }


    /** FUNCTION: disconnect
     *
     * Disconnect a device, when it closes the connection
     * 
     * @param  resource $client Socket resource of the device 
     * @return bool     true/false
     */
    public function disconnect_device($client) 
    {

        $device_id = $this->get_device_id($client);

        // close socket
        @fclose($client);

        $key = array();
        if (sizeof($this->clients) != 0) 
        {
            // remove the client from the clients array
            $key = array_keys($this->clients, $client);
        }
        if (sizeof($key) != 0) 
        {
            for ($i = ($key[0] + 1); $i <= (sizeof($this->clients) - 1); $i++) 
            {
                $this->clients[$i - 1] = $this->clients[$i];
                $this->device_common_name[$i - 1] = $this->device_common_name[$i];
            }
            unset($this->device_common_name[sizeof($this->clients) - 1]);
            unset($this->clients[sizeof($this->clients) - 1]);
            
        }

        $key = array();
        if (sizeof($this->device_sock_resource) != 0) 
        {
            //remove the device socket resource, device id and recorded time from the array
            $key = array_keys($this->device_sock_resource, $client);
        }
        if (sizeof($key) != 0) 
        {
            for ($i = ($key[0] + 1); $i <= (sizeof($this->device_sock_resource) - 1); $i++) 
            {
                $this->device_sock_resource[$i - 1] = $this->device_sock_resource[$i];
                $this->device_id[$i - 1] = $this->device_id[$i];
                $this->recorded_time[$i - 1] = $this->recorded_time[$i];
            }
            unset($this->device_sock_resource[sizeof($this->device_sock_resource) - 1]);
            unset($this->device_id[sizeof($this->device_id) - 1]);
            unset($this->recorded_time[sizeof($this->recorded_time) - 1]);
            $this->index = $this->index - 1;

            
            $result = $this->db->query("select Device_Name from Device_Specification where Device_Id=$device_id");
            if ( $this->db->SingleRecord($result))
            {
              $device_name = $this->db->Record['Device_Name'];
            }

          $this->db->logservice->log($this->module_name, "DEBUG","EVENT",$this->IP,"Device disconnected: $device_name" . "(" . $client . ")");
           
        }
        return true;
    }


    /** FUNCTION: get_device_Id
     *
     * Method to get the device id with the device sock resource
     *
     * @param  resource $device_sock_resource  socket resource of the device
     * 
     * @return integer  $device_id             Device ID
     */
    public function get_device_id($device_sock_resource) 
    {
        $flag = 0;
        for ($i = 0; $i < $this->index; $i++) 
        {
            if ($this->device_sock_resource[$i] == $device_sock_resource)  // device socket resource is present in the array
            {
                $flag = 1;
                break;
            }
        }
        if ($flag == 1)  // device socket resource exists
        {
            $device_id = $this->device_id[$i]; // corresponding device id
        }
        else// device is not active
        {
            return 0;
        }
        return $device_id;
    }

    
    /** FUNCTION: get_cuid_index
     *
     * Method to get the index in global array given CUID
     *
     * @param integer  $cuid  Device CUID
     *
     * return resource $index Index in global array
     */
    public function get_cuid_index($cuid)
    {
      $flag = 0;
      $index = -1;

      for ($i = 0; $i < sizeof($this->device_common_name); $i++)
      {
        if ($this->device_common_name[$i] === $cuid)  // device id is present in the array
        {
          $flag = 1;
          break;
        }
      }
      if ($flag == 1)  
      {
        $index = $i;
      }
      
      return $index;
    }

    /** FUNCTION: get_device_socket
     *
     * Method to get the device socket resource with the device id
     *
     * @param integer  $device_id  Device ID
     * 
     * return resource $client     Device socket resource
     */
    public function get_device_socket($device_id) 
    {
        $flag = 0;
        for ($i = 0; $i < $this->index; $i++) 
        {
            if ($this->device_id[$i] == $device_id)  // device id is present in the array
            {
                $flag = 1;
                break;
            }
        }
        if ($flag == 1)  // device is active
        {
            $client = $this->device_sock_resource[$i];
        }
        else // device is not active
        {
            return 0;
        }
        return $client;
    }


    /** FUNCTION: send
     *
     * Any message to the device is send through this function
     *
     * @param resource $client  Device Socket resource
     * @param string   $data    Message to be sent to the device
     * 
     * @return bool
     */
    public function send($client, $data) 
    {
        $device_id = $this->get_device_id($client);

        $device_name = "";
        if ($device_id != 0) 
        {
          $result = $this->db->query("select Device_Name from Device_Specification where Device_Id=$device_id");
          if ( $this->db->SingleRecord($result))
          {
            $device_name = $this->db->Record['Device_Name'];
          }
        }
        $this->db->logservice->log($this->module_name, "DEBUG", "EVENT",$this->IP,"[To device:" . $device_name . "]" . $data);        
        return fwrite($client, $data);
    }

    public function cleanup_zombie_devices()
    {
      global $DEVICE_DEREG_MSG;
      $result = $this->db->query("select Device_Id from Device_Specification where Sleep_Time <= now() and Device_Status = 'active'");
      if($result === false)
      {
        echo "false\n";
      }
      while ($this->db->SingleRecord($result))
      {
        $device_id = $this->db->Record['Device_Id'];
        echo "Device ID for cleanup is $device_id\n";
        $client = $this->get_device_socket($device_id);
        $this->db->logservice->log($this->module_name, "DEBUG", "GENERAL",$this->IP, "Identified a zombie device. Deregistering: $device_id");
        
        $this->device_deregistration("$DEVICE_DEREG_MSG $device_id \r\n", FALSE, 'sleep');

        $date = gmdate('Y-m-d H:i:s');
        $date_format = array();
        $date = explode(" ", $date);
        $date_format['date'] = $date[0];
        $date_format['Timestamp'] = 'T';
        $date_format['time'] = $date[1];
        $date_format['Zone'] = 'Z';
        $date = implode("",$date_format);
        
        $test_data = "EVENT $device_id $date RESOLVED SERV DEVSTAT Device in sleep state"; 
  //      $this->fm->handle_serverevents($test_data,0);
        
        $this->disconnect_device($client);
      }
    }

    /** FUNCTION: events
     *
     * This function handles the event messages from the device
     *
     * @param string $data       Event message from the device
     * @param int    $device_id  Device ID
     * 
     * @return None
     */
    public function events($data, $device_id) 
    {
        $this->db->logservice->log($this->module_name, "TRACE", "EVENT",$this->IP,"Entering the function to handle event msgs");
        $event_msg = str_getcsv($data, ' ', '"');

        if (!isset($event_msg[1]) || !isset($event_msg[2]) || !isset($event_msg[3]) || !isset($event_msg[4])) 
        {
          $this->db->logservice->log($this->module_name, "ERROR","ALARM", $this->IP,"Invalid event message..Parameters missing");
            return;
        }

        if (strcmp($event_msg[4], "APP") == 0) 
        {
            $this->appevent($data, $device_id);
        } 
        else if (strcmp($event_msg[4], "MW") == 0) 
        {
            $this->mwevent($data, $device_id);
        } 
        else if (strcmp($event_msg[4], "SYS") == 0) 
        {
            $this->sysevent($data, $device_id);
        } 
        else 
        {
            return;
        }
    }

     /** FUNCTION: process_kpi_message
     *
     * This function handles the kpi messages from the device
     *
     * @param string $data       kpi message from the device
     * @param int    $device_id  Device ID
     * 
     * @return None
     */
    public function process_kpi_message($data, $device_id) 
    {
        $this->db->logservice->log($this->module_name, "TRACE", "EVENT",$this->IP,"Processing KPI message");

        $status_msg  = str_getcsv($data,' ','"');

        /* if the parameter is null, then the message is not completer, drop it */
        if(!isset($status_msg[1]) || !isset($status_msg[2]) || !isset($status_msg[3]) || !isset($status_msg[4]))
        {
         $this->db->logservice->log($this->module_name,"ERROR","ALARM",$this->IP,"Invalid kpi data message,Parameters missing");
         return;     
        }  
        else
        {
    
        $hub_id = $status_msg[1];

        $start_index = $status_msg[2];

        $rec_time = $status_msg[3];
        
        $option = $status_msg[4];

        $value = '';
 
        if(isset($status_msg[5]))
        {
          $value = $status_msg[5];
        }

        if($option === "GPS")
        { 
           $stat = $this->db->query("select * from Device_Specification where Device_Id = $hub_id");

           $gps_lis = explode(',', $value);

           if($this->db->numrows($stat) > 0)
           {
              $upd_stat = $this->db->query("update Device_Specification set Latitude = '$gps_lis[0]', Longitude = '$gps_lis[1]',Last_Modified = now() where Device_Id = $hub_id ");
	  
              if($upd_stat === "fail")
              {
                 $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "update to Device_Specification table failed");
              }
              else
              {
                 $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "update to Device_Specification table success");
              }
           }
           else  
           {
              $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "DeviceId not found in the database");
           }
		    	   
           $stat = $this->db->query("select * from KPI_Small where Device_Id = $hub_id and Parameter = 'GPS_LAST_RECEIVED' ");

           if(isset($gps_lis[2]) && isset($status_msg[6]))
           {
             $gps_lis[2] =  $gps_lis[2] . ' ' .$status_msg[6];

             if($this->db->numrows($stat) > 0)
             {
              $upd_stat = $this->db->query("update KPI_Small set Value = '$gps_lis[2]',Last_Modified = now() where Device_Id = $hub_id and  Parameter = 'GPS_LAST_RECEIVED' ");
 
              if($upd_stat === "fail")
              {
                $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "update to KPI_Small table failed");
              }
              else
              {
                $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "update to KPI_Small table success");
              }
             }
             else
             {
                $result  = $this->db->query("Insert into KPI_Small (Device_Id,Parameter,Value,Last_Modified,Description) values ($hub_id,'GPS_LAST_RECEIVED','$gps_lis[2]',now(),'')");
	  
                if($result === "fail")
                {
                  $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not create KPI data in KPI_Small table");
                }
                else
                {
                  $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "Kpi Entry Added in KPI_Small table");
                }
             }
          }
		}
       else
       {
         $stat = $this->db->query("select * from KPI_Small where Device_Id = $hub_id and Parameter = '$option' ");

         $uboot = explode(' ',$data,6);

         if($this->db->numrows($stat) > 0)
         {
           if(isset($uboot[5]))
           {
            $upd_stat = $this->db->query("update KPI_Small set Value = '$uboot[5]',Last_Modified = now() where Device_Id = $hub_id and Parameter = '$option' ");
            if($upd_stat === "fail")
            {
              $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "update to KPI_Small table failed");
            }
            else
            {
              $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "update to KPI_Small table success");
            }
           }
           else
           {
             $upd_stat = $this->db->query("update KPI_Small set Value = '',Last_Modified = now() where Device_Id = $hub_id and Parameter = '$option' ");
            if($upd_stat === "fail")
            {
             $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "update to KPI_Small table failed");
            }
            else
            { 
             $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "update to KPI_Small table success");
            } 
           } 
          }
        else
        { 
          if(isset($uboot[5]))
          {
            $result  = $this->db->query("Insert into KPI_Small (Device_Id,Parameter,Value,Last_Modified,Description) values ($hub_id,'$option','$uboot[5]',now(),'')");

            if($result === "fail")
            {
              $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not create KPI data in KPI_Small table");
            }
            else
            {
              $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "Kpi Entry Added in KPI_Small table");
            } 
          }
         else
         {
            $result  = $this->db->query("Insert into KPI_Small (Device_Id,Parameter,Value,Last_Modified,Description) values ($hub_id,'$option','',now(),'')");

            if($result === "fail")
            {
              $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not create KPI data in KPI_Small table");
            }
            else
            {
              $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "Kpi Entry Added in KPI_Small table");
            }  
         }
       }//
     }
    }  
   }

     /** FUNCTION: process_bus_message
     *
     * This function handles the bus messages from the device
     *
     * @param string $data       bus message from the device
     * @param int    $device_id  Device ID
     * 
     * @return None
     */
     public function process_bus_message($data, $device_id)
     {
        $this->db->logservice->log($this->module_name, "TRACE", "EVENT",$this->IP,"Processing BUS message");

        $status_msg  = str_getcsv($data,' ','"');

        /* if the parameter is null, then the message is not completer, drop it */
        if(!isset($status_msg[1]) || !isset($status_msg[2]) || !isset($status_msg[3]) || !isset($status_msg[4]))
        {
         $this->db->logservice->log($this->module_name,"ERROR","ALARM",$this->IP,"Invalid business data message,Parameters missing");
         return;     
        }  
        else
        {
    
          $hub_id = $status_msg[1];

          $rec_time = $status_msg[2];
          
          $option = $status_msg[3];

          $msg = $status_msg[4];

          switch ($option) {
            case 'QYINFO':{
               $qyfileds  = str_getcsv($msg,',','"');

               $stat = $this->db->query("select * from CorporInfo where Device_Id = $hub_id ");
               if ($this->db->numrows($stat) > 0)  
               {
                 $upd_stat = $this->db->query("update CorporInfo set CorporName = '$qyfileds[0]'，Nsrsbh = '$qyfileds[1]', 
                  Address = '$qyfileds[2]',TelNum = '$qyfileds[3]' where Device_Id = $hub_id ");
                if($upd_stat === "fail")
                 $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "update to CorporInfo table failed");
                else
                 $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "update to CorporInfo table success");
               }
               else
               {
                  $result  = $this->db->query("Insert into CorporInfo (Device_Id,CorporName,Nsrsbh,Address,TelNum) values ($hub_id,'$qyfileds[0]','$qyfileds[1]','$qyfileds[2]','$qyfileds[3]')");

                  if($result === "fail")
                    $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not create bus data in CorporInfo table");
                  else
                    $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "bus Entry Added in CorporInfo table");
               }
               break;
            }   
            case 'SWINFO':
            {
               $swfileds  = str_getcsv($msg,',','"');

               $stat = $this->db->query("select * from FiscalInfo where Device_Id = $hub_id ");
               if ($this->db->numrows($stat) > 0)  
               {
                 $upd_stat = $this->db->query("update FiscalInfo set SWJGDM = '$swfileds[0]',KPQSRQ = '$swfileds[1]', 
                  KPJZRQ = '$swfileds[2]',HYFL = '$swfileds[3]',KPXM = '$swfileds[4]',DZMaxSum = '$swfileds[5]' where Device_Id = $hub_id ");
                if($upd_stat === "fail")
                 $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "update to FiscalInfo table failed");
                else
                 $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "update to FiscalInfo table success");
               }
               else
               {
                  $result  = $this->db->query("Insert into FiscalInfo (Device_Id,SWJGDM,KPQSRQ,KPJZRQ,HYFL,KPXM,DZMaxSum)
                   values ($hub_id,'$swfileds[0]','$swfileds[1]','$swfileds[2]','$swfileds[3]','$swfileds[4]','$swfileds[5]')");

                  if($result === "fail")
                    $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not create bus data in FiscalInfo table");
                  else
                    $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "bus Entry Added in FiscalInfo table");
               }
               break;
            }
            case 'CURINFO':
                 $statusfileds  = str_getcsv($msg,',','"');

                 $stat = $this->db->query("select * from CorporStatus where Device_Id = $hub_id ");
                 if ($this->db->numrows($stat) > 0)  
                 {
                   $upd_stat = $this->db->query("update CorporStatus set CurInvNum = '$statusfileds[0]',CurInvCode = '$statusfileds[1]', 
                    CurDaySum = '$statusfileds[2]' where Device_Id = $hub_id ");
                  if($upd_stat === "fail")
                   $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "update to CorporStatus table failed");
                  else
                   $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "update to CorporStatus table success");
                 }
                 else
                 {
                    $result  = $this->db->query("Insert into CorporStatus (Device_Id,CurInvNum,CurInvCode,CurDaySum)
                     values ($hub_id,'$statusfileds[0]','$statusfileds[1]','$statusfileds[2]')");

                    if($result === "fail")
                      $this->db->logservice->log($this->module_name, "ERROR", "ALARM",$this->IP, "Could not create bus data in CorporStatus table");
                    else
                      $this->db->logservice->log($this->module_name, "INFO", "EVENT",$this->IP, "bus Entry Added in CorporStatus table");
                 }
                break;  
            
              default:
                break;
          }
        }
     }

    /** FUNCTION: mwevent
     *
     * This function handles the middleware events from the device
     *
     * @param string  $data       Middleware event message from the device    
     * @param inteter $device_id  Device ID
     * 
     * @return bool
     */
    public function mwevent($data, $device_id) 
    {

        $this->db->logservice->log($this->module_name, "TRACE","EVENT", $this->IP,"Handling Middleware event");

//        $rv = $this->fm->handle_mwevents($data);
  
        if (false === $rv)
        {
            $this->db->logservice->log($this->module_name, "ERROR","ALARM", $this->IP,"handle_mwevents error");
        }
        else
        {
            $this->db->logservice->log($this->module_name, "TRACE","EVENT", $this->IP,"handle_mwevents success");
        }
    }


    /** FUNCTION: appevent
     *
     * This function handles the application events from the device
     *
     * @param string  $data      Application event message from the device
     * @param inteter $device_id Device ID
     * 
     * @return bool
     */
    public function appevent($data, $device_id) 
    {
        $this->db->logservice->log($this->module_name, "TRACE","EVENT", $this->IP,"Handling Application event");
     
 //       $rv = $this->fm->handle_appevents($data,$device_id);
  
        if (false === $rv) {
              $this->db->logservice->log($this->module_name, "ERROR","ALARM", $this->IP,"handle_appevents error");
            }
        else
        {
            $this->db->logservice->log($this->module_name, "TRACE","EVENT", $this->IP,"handle_appevents success");
        }

     }

    public function sysevent($data, $device_id) 
    {
        $this->db->logservice->log($this->module_name, "TRACE","EVENT", $this->IP,"Handling System event");
     
  //      $rv = $this->fm->handle_sysevents($data,$device_id,0);
  
        if (false === $rv) {
              $this->db->logservice->log($this->module_name, "ERROR","ALARM", $this->IP,"handle_sysevents error");
            }
        else
        {
            $this->db->logservice->log($this->module_name, "TRACE","EVENT", $this->IP,"handle_sysevents success");
        }
    }
    
    /**
     * FUNCTION: create_notification()
     *
     * This function created and entry in the device_notification table
     * corresponding to any query messages to the device.
     *
     * @param  integer device_id
     * @param  integer message
     * @return none
     */
    public function create_notification($device_id, $message)
    {
    
      $result = $this->db->query("select count(*) from Device_Notification where  Device_Id=$device_id and Message = '$message'");
      if ($this->db->SingleRecord($result))
      {
        $count = $this->db->Record['count(*)'];
      }
    
      if($count == 0)
      {
        $result = $this->db->query("insert into Device_Notification (Device_Id,Message,Status,Recorded_Time,Last_Modified)
            values
          ($device_id,'$message','0',now(),now())");
      }
      else
      {
        $result = $this->db->query("update Device_Notification set Status='0', Recorded_Time = now(),Last_Modified = now()
          where Device_Id = $device_id  and Message = '$message'");
        if($result === "fail")
        {
           $this->db->logservice->log($this->module_name, "ERROR","ALARM", $this->IP,"Device Notification update failed");
        }
      }
    }
}
$dm = new device_manager();

$dm->index();
?>
