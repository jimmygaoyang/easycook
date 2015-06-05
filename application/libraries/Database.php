<?php
/*===========================================================================

                    C L U S T E R    D A T A B A S E  C L A S S 
                     
                                S O U R C E    F I L E

DESCRIPTION
  This source file defines the various functions for interacting to the MYSQL 
  database.

Copyright (c) 2011 by Cluster Wireless, Incorporated.  All Rights Reserved.

===========================================================================*/

/*===========================================================================

                              EDIT HISTORY FOR FILE

  This section contains comments describing changes made to the module.
  Notice that changes are listed in reverse chronological order.


when         who     	what, where, why
--------     ---     	----------------------------------------------------------
28/12/2011   RTD     	created various functions to access the database      
28/06/2012   ERD     	Modified and Added some functionalities
23/08/2013   Venkatesh  Added functions for trasaction start,commit and rollback
02/09/2013   Venkatesh  Added the last_insertid function
==============================================================================*/
require_once("LogService.php");

class Database
{
    var $Host     = "localhost"; // Hostname of our MySQL server.
    var $Database = "CookMaster"; // Logical database name on that server.
    var $User     = "root"; // User and Password for login.
    var $Password = "cluster";
    
    var $Db_name = "mysql";
    
    var $Link_ID  = 0;  // Result of mysql_connect().
    var $Query_ID = 0;  // Result of most recent mysql_query().
    var $Record   = array();  // current mysql_fetch_array()-result.

    var $Errno    = 0;  // error state of query...
    var $Error    = "";
    protected $module_name;
    
    protected $dbh = null;
/**   CONSTRUCTOR
 *  create instantiation for Logging class
 *    
 */ 
  function __construct($database=null,$username=null,$password=null,$show_error=true) 
  {
     $this->config = parse_ini_file("db_config.ini");
     $this->Host = $this->config['host'];
     $this->Database = $database;
     $this->User = $this->config['user'];
     $this->Password = $this->config['password'];
     if(isset($this->config['db_name']))
        $this->Db_name = $this->config['db_name'];
     else
        $this->Db_name = "mysql";
    
     /*logging*/
     if($show_error)
     $log_config  = array("show_error" => TRUE);
     else
      $log_config  = array("show_error" => FALSE);
     $this->logservice = new logservice($log_config);
     $this->module_name = "Database";
     $host= gethostname();
     $this->IP = gethostbyname($host);
     date_default_timezone_set("UTC");
  }

  /**FUNCTION:   CONNECT
   * this function creates a MYSQL connection using username,password.
   * it selects the desired database
   * @return resource Link_ID
   *       
   */
  function Connect()
  {
    /*if( 0 == $this->Link_ID )
        $this->Link_ID=@mysql_connect( $this->Host, $this->User, $this->Password );          	 
    if( !$this->Link_ID )
    {
      $this->Errno = mysql_errno();
      $message = "MYSQL Error-$this->Errno:  Cannot connect to MYSQL server";
      $this->logservice->log($this->module_name, "DEBUG",$message);
      return "fail";
    }
    if( !mysql_query( sprintf( "use %s", $this->Database ), $this->Link_ID ) )
    {
      $this->Errno = mysql_errno();
      $message = "MYSQL Error-$this->Errno: cannot use database $this->Database";

      $this->close($this->Link_ID);
      $this->Link_ID=@mysql_connect( $this->Host, $this->User, $this->Password );        
      mysql_query( sprintf( "use %s", $this->Database ), $this->Link_ID ); 

      $this->logservice->log($this->module_name, "DEBUG",$message);
    }
    return $this->Link_ID;*/
  
    if( null == $this->dbh )
    {
       try
       {
          $this->dbh = new PDO("$this->Db_name:host=$this->Host;dbname=$this->Database", $this->User, $this->Password);
          $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
       }
       catch(PDOException $ex)
       {
          $this->logservice->log($this->module_name,"FATAL","ALARM",$this->IP,$ex->getMessage());
          return "fail";
       }
    }
    
    
    try
    {
       $sql = "USE $this->Database";
       $this->dbh->query($sql);
    }
    catch(PDOException $ex)
    {
       $this->logservice->log($this->module_name,"FATAL","ALARM",$this->IP,$ex->getMessage());
       $this->logservice->log($this->module_name,"FATAL","ALARM",$this->IP,"connection failed .. retrying..\n");
       sleep(2);
       $this->dbh = null;
       try
       {
          $this->dbh = new PDO("$this->Db_name:host=$this->Host;dbname=$this->Database", $this->User, $this->Password);
          $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
       }
       catch(PDOException $ex)
       {
          echo $ex->getMessage();
          return "fail";
       }
    }
  } // end function connect


  /**FUNCTION:    QUERY
  * this function passes the query to the MYSQL using the created database link. 
  * @param string $Query_String
  * @return resource $Query_ID      
  */
  function query( $Query_String ,$param = array())
  {
    /*$con = $this->Connect();
    if($con == "fail")
      return "fail";

    $this->Query_ID = mysql_query( $Query_String,$this->Link_ID );
    $this->Row = 0;
    $this->Errno = mysql_errno();
    $this->Error = mysql_error();
    if( !$this->Query_ID )
    {
      $message = "($this->Errno: $this->Error) Invalid SQL: $Query_String";
      $this->logservice->log($this->module_name, "DEBUG",$message);
      return "fail";
    }
    return $this->Query_ID;*/
    
    $con = $this->Connect();
    if($con == "fail")
      return "fail";
    
    try
    {
      //$this->Query_ID = $this->dbh->query($Query_String);
    	$this->Query_ID = $this->dbh->prepare($Query_String);
    	 if(sizeof($param)>0)
    	    {
    		$i=0;
    		while($i<sizeof($param))
    		{
    		$this->Query_ID->bindParam($i+1,$param[$i]);
    		$i++;
    		}
    	    } 
    	$this->Query_ID->execute();
    	return $this->Query_ID;
    }
    catch(PDOException $ex)
    {
      $this->logservice->log($this->module_name,"ERROR","ALARM",$this->IP,$ex->getMessage());
      $this->Error=$ex->getMessage();
      return "fail";
    }
  } // end function query

/**FUNCTION:    SINGLERECORD
 * this function fetches single row of table data in both nueric as well as 
 * associative array format
 * @param resource $Query_ID
 * @return boolean $stat
 *       
 */
 function SingleRecord($stmt)
 {
   /*$this->Record = @mysql_fetch_array( $Query_ID,MYSQL_BOTH);
   $stat = is_array( $this->Record );
   return $stat;*/
   
   if($stmt != "fail" || $stmt != "false")
   {
     $this->Record = $stmt->fetch(PDO::FETCH_ASSOC);
     return $this->Record;
   }
   return false;
 } 
        
/**FUNCTION:    NUMROWS
 * this function returns the number of rows affected for the query passed  
 * @param resource $Query_ID 
 * @return integer    
 */
 function numrows($stmt)
 {
   if($stmt != "fail")
   {
     $record = $stmt->fetchAll();
     $total = count($record);
     return $total;
   }
   return false;
 }

/**FUNCTION:    NUMFIELDS
 * this function returns the number of fields for the query passed  
 * @param resource $Query_ID 
 * @return integer    
 */
 function numFields($Query_ID)
 {
   return mysql_num_fields( $Query_ID );
 } // end function numFields

/**FUNCTION:    FIELDNAME
 * this function returns the field name for the query passed based on offset  
 * @param resource $Query_ID 
 * @param string $i 
 * @return integer    
 */
  function fieldName($Query_ID, $i)
  {
    return mysql_field_name( $Query_ID , $i );
  } // end function numFields


/**FUNCTION:   FIELDCHECK
 * this function selects a column from a table using the WHERE clause of MYSQL
 * @param string $Query_String
 * @return boolean $stat      
 */
  function FieldCheck($table,$column1,$column2,$value)  //$column1=input , $column2= output
  {
    //SELECT $COLUMN2 FROM $TABLE WHERE $COLUMN1=$VALUE
    $query = sprintf("SELECT %3\$s FROM %1\$s WHERE %2\$s=%4\$s",
                      $table,$column1,$column2,$value
                    );
    //passing this query to MYSQL
    $result= $this->query($query);
    //getting the record for the QUERY
    $stat = $this->SingleRecord($result);
    return $stat;
  }

  /**FUNCTION:   transaction_start
   * this function starts the mysql transaction
  */
  
  function transaction_start()
  {
  	$this->dbh->beginTransaction();
  }
  
  /**FUNCTION:   transaction_commit
   * this function commits the mysql transaction
  */
  function transaction_commit()
  {
  	$this->dbh->commit();
  }
  
  /**FUNCTION:   transaction_start
   * this function rolls back the mysql transaction
  */
  function transaction_rollback()
  {
  	$this->dbh->rollBack();
  }
  /**FUNCTION:   last_insertid
   * this function returns the id of the last inserted row
  */
  function last_insertid()
  {
  	return $this->dbh->lastInsertId();
  }
  
/**FUNCTION:   CLOSE
 * this function closes the existent MYSQL connection 
 * @return boolean $stat     
 */
  function close()
  {
    $stat = mysql_close($this->Link_ID);
    $this->Link_ID = 0;
    return $stat;
  }

}