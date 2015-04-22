<?php

/* -----------------------------------------
     Messages from Device
--------------------------------------------*/
$DEVICE_REG_MSG = "REG_F";          // Device registration message
$DEVICE_DEREG_MSG = "DEREG_F";      // Device deregistration message
$STATUS_MSG = "STATUS";             // Status message from the device
$EVENT_MSG = "EVENT";               // Event messages from the device
$DIAGNOSTIC_MSG = "DIAGNOSTIC";     // Diagnostics messages from the device
$KPI_MSG = "KPI_DATA"; 
$BUS_MSG = "BUS_DATA";				//Business Data


/* -----------------------------------------
     Messages to Device
--------------------------------------------*/
$DEVICE_REG_RESP = "REG_R";              // response to device registration
$DEVICE_CONFIG_MSG = "DEVICE_CONFIG";   // SMS number
$DOWNLOAD_MSG = "DOWNLOAD";            // download trigger to device
$RESET_MSG = "RESET";
$QUERY_MSG = "QUERY";
$NOTIFY_APP_MSG = "NOTIFY";


/* -----------------------------------------
     Messages to Utility
--------------------------------------------*/
$DEVICE_DETAILS = "DEV_DETAIL"; // device details to utility
$DEVICE_DEREG = "DEV_DEREG";   // device deregistration to utility
$DOWNLOAD_TIMEOUT = 120;
$NOTIFY_TIMEOUT = 60;
$GPS_NOTIFY_TIMEOUT = 300;
$DOWNLOAD_TIME_LIMIT = 25;
$HEARTBEAT_INTERVAL = 86400;
$HEARTBEAT_EVENT_INTERVAL = 1800;
$HEARTBEAT_GRACE_PERIOD = 10;
$WAKEUP_PERIOD = 300;
//$EVENT_MSG = "EVENT";       // Event messages from the device

?>
