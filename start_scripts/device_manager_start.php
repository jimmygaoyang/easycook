<?php



declare(ticks = 1);
  class Device_Manager_Start
  { 
   
    public function index()
    {
      switch ($pid = pcntl_fork()) 
      {
        case -1:
          die('Fork failed');
          break;
        case 0:
          $this->child_process();
          break;
        default:
          $this->parent_process();
          break;
      }
    }
    
    
    public function parent_process()
    {
      pcntl_signal(SIGTERM,array(&$this,"kill_handler"),false);
      pcntl_signal(SIGINT,array(&$this,"kill_handler"),false);
      while(1)
      {
        echo "parent after fork:", getmypid(), PHP_EOL;
        pcntl_wait($status);
        /*Writing about crash report to a file */
        $today = gmdate('Y.m.d');
        $myFile = "/home/gy/CodeIgniter-2.2-stable/easycook/Logs/device_manager_err-$today.txt";
        $fh = fopen($myFile, 'a') or die("can't open file");
        $today = gmdate("F j, Y, g:i:s a\n");
        $stringData = "Device Manager server Crashed at ";
        fwrite($fh, $stringData);
        fwrite($fh,$today);
        fclose($fh);
        sleep(5);
        $id = pcntl_fork();
        if($id == 0)
          $this->child_process();
      }
    }
    
    public function child_process()
    {
      echo "child after fork:", getmypid(), PHP_EOL;
      $filename = "/var/run/dm_child.pid";
      $handle = fopen($filename, "w+");
      fwrite($handle,getmypid());
      fclose($handle);
      $dm = array("/home/gy/CodeIgniter-2.2-stable/easycook/device_management/device_manager.php");
      pcntl_exec("/usr/bin/php",$dm);
    }
    
    
    public function kill_handler($no)
    {
      $filename = "/var/run/dm_child.pid";
      $handle = fopen($filename, "r");
      $contents = fread($handle, filesize($filename));
      fclose($handle);
      $contents = trim($contents);
      exec("kill -9 $contents");
      exec("rm $filename");
      exit;
    }
  
  }

  $dms = new Device_Manager_Start();
  $dms->index();
?>
