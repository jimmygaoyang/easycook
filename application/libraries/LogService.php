<?php

/*==============================================================================

                    L O G G I N G    S E R V I C E    C L A S S 

                                S O U R C E   F I L E

DESCRIPTION
  This file defines the various functions for logging the messages into log file.

Copyright (c) 2012 by Cluster Wireless, Incorporated.  All Rights Reserved.

==============================================================================*/

/*==============================================================================

                              EDIT HISTORY FOR FILE

  This section contains comments describing changes made to the module.
  Notice that changes are listed in reverse chronological order.


when         who     what, where, why
--------     ---     ----------------------------------------------------------
27/12/2012   SJS    Created base functions to log the messages in a file.             

==============================================================================*/
require_once("colours.php");

class LogService 
{

  /**
   * Path of the logs directory
   * @var string 
   */
  protected $log_path = "/home/gy/CodeIgniter-2.2-stable/easycook/Logs/";

  /**
   * Name of the log file
   * @var string 
   */
  protected $log_file;

  /**
   * Name of the configuration file
   * @var string 
   */
  protected $log_config_file_name = "log_config.ini";

  /**
   * Array containing the configuration parameters
   * @var array 
   */
  protected $config;

  /**
   * Variable to determine if the log messages are to be displayed or not
   * @var  boolean
   */
  protected $show_error = FALSE;

  /**
   * Array containing the log levels
   * @var array 
   */
  protected $level = array('TRACE', 'DEBUG', 'INFO', 'CRITICAL', 'ERROR', 'FATAL');

  /**
   * Configuration array to enable the display of the errors
   * Error display enabled when $config['show_error'] is TRUE
   * (default error display is disabled)
   * @param array $config
   */
  public function __construct($config = NULL) 
  {

    // Create the log directory
    if (!is_dir($this->log_path)) 
    {
      mkdir($this->log_path, 0777, TRUE);
    }
    //$this->log_file = $this->log_path . "Log_" . gmdate('Y-m-d') . ".log";

    if (isset($config['show_error'])) 
    {
      $this->show_error = $config['show_error'];
    }

    // Parse the log config file.
    $this->config = parse_ini_file($this->log_config_file_name);
    if ($this->show_error != FALSE) 
    {
      echo "\n+-------------------+----------+------+-------+---------------+----------------+-----------+------+--------------------------------------------\n";

      echo   "+      TIMESTAMP    +   HOST   + LEVEL+   ID  +     MODULE    +      CLASS     +   METHOD  + LINE +   MESSAGE                    
\n"; 
      echo   "+-------------------+----------+------+-------+---------------+----------------+-----------+------+--------------------------------------------\n";
    }
  }

  /**
   * Function to write the log message into the file
   * @param string $module Name of the module
   * @param string $level Log level
   * @param string $message Message to be logged in the file
   * @return boolean 
   * TRUE In case of successfull file write
   * FALSE In case of file write fail or log level off
   */
  public function log($module, $level, $id, $host, $message) 
  {
    
    if ($this->config['LOG_' . $level] == 1) 
    {
          if($this->config['LOG_' . $id] == 1)
            {
               return $this->write_log($module, $level, $id, $host, $message);
            } 
    }
    else 
    {
      //echo "Log level write disabled ..!!!";
      return FALSE;
    }
  }

  /**
   * Function to add the various level of log information and write it into the file
   * @param string $module Name of the module
   * @param string $level Log level
   * @param string $message Message to be logged in the file
   * @return boolean 
   * TRUE In case of successfull file write
   * FALSE In case of file write fail
   */
  public function write_log($module, $level, $id, $host, $message) 
  {
     $this->log_file = $this->log_path . "Log_" . gmdate('Y-m-d') . ".log";
     $flag = 0;
     $colors = new Colors();
     $debug = debug_backtrace();
     $log[] = "[".date('Y-m-d H:i:s')."]";
     $log[] = $host;
     $log[] = $level;
     $log[] = $id;
     $log[] = $module;
     $log[] = "CLASS:" . $debug[2]['class'];
     $log[] = "METHOD:" . $debug[2]['function'];
     $log[] = "LINE:" . $debug[1]['line'];
     $log[] = $message;

     $log_string = implode(" ", $log) . "\n";
     if (!file_exists($this->log_file)) {
      $flag = 1;
     }
     $file=$this->log_file;
     //exec("sudo chown root $file");
     //exec("sudo chgrp root $file");
     $file_handle = @fopen($this->log_file, 'a');
     @chown($file,"root");
     if (!$file_handle) {
      return FALSE;
     } else {

      if ($flag) {        chmod($this->log_file, 0777);
      }      
      if (!@fwrite($file_handle, $log_string)) {
        return FALSE;
      }

      @fclose($file_handle);
    }

    if ($this->show_error != FALSE) 
    {
      if(strcmp($level,$this->level[4])==0 || strcmp($level,$this->level[5])==0)
      {
        $level = $colors->getColoredString("\033[1m$level\033[0m", "red");
        //$message = $colors->getColoredString("\033[1m$message\033[0m", "red");
      }
      else if(strcmp($level,$this->level[3])==0)
      {
        $level = $colors->getColoredString("\033[1m$level\033[0m", "yellow");
        //$message = $colors->getColoredString("\033[1m$message\033[0m", "yellow");
      }
      else
      {
        $level = $colors->getColoredString("\033[1m$level\033[0m", "blue");
        //$message = $colors->getColoredString("\033[1m$message\033[0m", "blue");
      }
      
      $mask = "|%19.19s|%09s |%-7s |%7.7s |%-15.15s |%-15.15s |%-10.10s |%5s |%-40s \n";
      printf($mask,gmdate('Y-m-d H:i:s'), $host, $level, $id, $module, $debug[2]['class'], $debug[2]['function'], $debug[1]['line'],$message);
    }

    return TRUE;
  }
}


/* End of file LogService.php */ 
/* Location: ./applications/adms/libraries/LogService.php */
