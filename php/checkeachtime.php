<?php

include('phpseclib/Net/SSH2.php');  //Include this library so that we can manipulate SSH sessions

require 'conn.php';
      
if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    $data = array();
    $data_app = array();
    $history = array();
    $history_app = array();
    $now = new DateTime();
    
    /* Check DB servers */
    $s = oci_parse($conn, "SELECT * FROM servers where server_state = 1 order by server_type desc, server_id");    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {
        $inst = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = ".$row["SERVER_ADDRESS"].")(PORT = 1521)) (CONNECT_DATA = (SERVER = DEDICATED) (SERVICE_NAME = ".$row["DB_SID"].")))" ;
        $instance  = oci_connect("SYS", $row["DB_PASSWORD"], $inst, null, OCI_SYSDBA);
        $f = oci_parse($instance, "select status from v\$instance");
        oci_execute($f);
        $result = oci_fetch_row($f);
        if ($result[0] == "OPEN") array_push($data, "Accessible");
        else array_push($data, "Inaccessible");
        oci_close($instance);   
    }
  
    $f = oci_parse($conn, "select AVAILABLE from (select * from history order by history_id desc) where rownum <= (select count(*) from servers where server_state = 1) order by history_id"); 
    oci_execute($f);
    oci_fetch_all($f, $res, null, null, OCI_FETCHSTATEMENT_BY_COLUMN);
    array_push($history, $res["AVAILABLE"]);

    /* Check APP servers */
    $s = oci_parse($conn, "SELECT * FROM apps where app_state = 1 ORDER BY app_id"); 
    oci_execute($s);
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
    
    foreach ($res as $row) {
        if ($row["APP_ID"] < 4) {
          $ssh = new Net_SSH2($row["APP_ADDRESS"]);     //Open SSH session to specified server in the argument
          if (!$ssh->login($row["APP_LOGIN"], $row["APP_PASSWORD"])) echo $row["APP_NAME"] . ': Login Failed. ';   // Message returned when connection failed
          $available = $ssh->exec('/home/oracle/OraHomefrm/opmn/bin/opmnctl ping 4 | grep succeeded') ? "Accessible" : "Inaccessible";
        } else {
          $ip = $row["APP_ADDRESS"];
          $ping = exec("ping -c 1 $ip", $output, $return_var); 
          $available = ($return_var == 1) ? "Inaccessible" : "Accessible";
        }
        array_push($data_app, $available);
    }
    
    $f = oci_parse($conn, "select AVAILABLE from (select * from history_app order by history_app_id desc) where rownum<= (select count(*) from apps where app_state = 1)  order by history_app_id"); 
    oci_execute($f);
    oci_fetch_all($f, $res, null, null, OCI_FETCHSTATEMENT_BY_COLUMN);
    array_push($history_app, $res["AVAILABLE"]);
 
    /* Send an email */    
    if (($data != $history[0]) || ($data_app != $history_app[0]) || (($now->format('H:i') >= "06:00") && ($now->format('H:i') < "06:05"))) { 
    
        require 'smtp.php';
        $mail->AddEmbeddedImage($ROOT_PATH . 'img/database_accessible.png', 'database_accessible');
        $mail->AddEmbeddedImage($ROOT_PATH . 'img/database_inaccessible.png', 'database_inaccessible');
        $mail->AddEmbeddedImage($ROOT_PATH . 'img/desktop_accessible.png', 'desktop_accessible');
        $mail->AddEmbeddedImage($ROOT_PATH . 'img/desktop_inaccessible.png', 'desktop_inaccessible');
        $mail->Subject = 'Databases availability (' . date("d-m-Y H:i", strtotime("1 hour")) . ')';   // Set the email subject
        $body = '<ul><li><b>Database Production Servers :</b></li></ul><br />';
        $body .= '<table style="margin:0px auto;width:100%;font-family:Tahoma;font-size:14px;font-weight:normal;">';
        $s = oci_parse($conn, "SELECT * FROM servers where server_state = 1 order by server_type desc, server_id");
        oci_execute($s);
        oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
        $change = true;
        foreach ($res as $key=>$row) {
            if ($row["SERVER_TYPE"] == 'DEV' && $change) {
                $body .= '</table><br />';
                $body .= '<br /><ul><li><b>Database Development Servers :</b></li></ul><br />';
                $body .= '<table style="margin:0px auto;width:100%;font-family:Tahoma;font-size:14px;">';
                $change = false;
            }
            $cid = ($data[$key] == "Accessible") ? 'database_accessible' : 'database_inaccessible';
            $body .= '<th style="padding:0 2%;border:1px solid transparent;text-align:center;font-weight:normal;">';
            $body .= '<img src="cid:' . $cid . '" height="90" />';
            $body .= '<p>' . $row["SERVER_NAME"] . '</p>';
            $body .= '</th>';    
            $h = oci_parse($conn, "INSERT INTO history (history_time, server_id, available) VALUES ('" . date('H:i:s', strtotime('1 hour')) . "', " . $row["SERVER_ID"]  . ", '" . $data[$key] . "')");   
            oci_execute($h);
        }
        $body .= '</table><br />';
        
        $body .= '<br /><ul><li><b>Application Servers :</b></li></ul><br />';
        $body .= '<table style="margin:0px auto;width:100%;font-family:Tahoma;font-size:14px;">';
        $s = oci_parse($conn, "SELECT * FROM apps where app_state = 1 order by app_id");
        oci_execute($s);
        oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
        foreach ($res as $key=>$row) {
            $cid = ($data_app[$key] == "Accessible") ? 'desktop_accessible' : 'desktop_inaccessible';
            $body .= '<th style="padding:0 2%;border:1px solid transparent;text-align:center;font-weight:normal;">';
            $body .= '<img src="cid:' . $cid . '" height="90" />';
            $body .= '<p>' . $row["APP_NAME"] . '</p>';
            $body .= '</th>';
            $h = oci_parse($conn, "INSERT INTO history_app (history_app_time, app_id, available) VALUES ('" . date('H:i:s', strtotime('1 hour')) . "', " . $row["APP_ID"]  . ", '" . $data_app[$key] . "')");
            oci_execute($h);
        }
        $body .= '</table><br />';           
        $mail->Body = $body; // Set the email content
        $mail->send();
    }
    oci_close($conn);  // Close the Oracle connection
}

?>