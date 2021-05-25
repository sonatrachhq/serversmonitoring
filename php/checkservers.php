<?php

header('Access-Control-Allow-Origin: *');

include('phpseclib/Net/SSH2.php');  //Include this library so that we can manipulate SSH sessions

require 'conn.php';
    
if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    $data=array();  

    $s = oci_parse($conn, "SELECT * FROM servers where server_state = 1 order by server_type desc, server_id");
    oci_execute($s);
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
    
    foreach ($res as $row) {    
        $ip = $row["SERVER_ADDRESS"];
        $ping = exec("ping -c 1 $ip", $output, $return_var);
        $available = ($return_var == 1) ? "Inaccessible" : "Accessible";        
        //$available = (@fsockopen($ip, 22)===false) ? "Inaccessible" : "Accessible";
        array_push($data, $available);    
    }
    
    $s = oci_parse($conn, "SELECT * FROM apps where app_state = 1 order by app_id"); 
    oci_execute($s);
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
  
    foreach ($res as $row) {

      if ($row["APP_ID"] < 4) {
          $ssh = new Net_SSH2($row["APP_ADDRESS"]);
          if (!$ssh->login($row["APP_LOGIN"], $row["APP_PASSWORD"])) echo $row["APP_NAME"] . ': Login Failed. ';
          $available = $ssh->exec('/home/oracle/OraHomefrm/opmn/bin/opmnctl ping 4 | grep succeeded') ? "Accessible" : "Inaccessible";
      } else {
          $ip = $row["APP_ADDRESS"];
          $ping = exec("ping -c 1 $ip", $output, $return_var); 
          $available = ($return_var == 1) ? "Inaccessible" : "Accessible";
      }
      array_push($data, $available);
    }

    oci_close($conn);
    
    // Return data as JSON  
    echo json_encode($data);
}

?>
