<?php

include('phpseclib/Net/SSH2.php');  //Include this library so that we can manipulate SSH sessions

require 'conn.php';
    
if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    $data = array();
    $history = array();
    $now = new DateTime();
    
   /* Check PDB servers */
    $s = oci_parse($conn, "SELECT * FROM pdbservers order by server_name");    // Select all columns from the "pdbservers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    $inst = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = 10.100.22.85)(PORT = 1521)) (CONNECT_DATA = (SERVER = DEDICATED) (SERVICE_NAME = dev16)))" ;
    $instance  = oci_connect("SYS", "Gascogne", $inst, null, OCI_SYSDBA);
        
    foreach ($res as $row) {
        $f = oci_parse($instance, "alter session set container =" . $row["SERVER_NAME"]);
        oci_execute($f);
        $f = oci_parse($instance, "select status from v\$instance");
        oci_execute($f);
        $result = oci_fetch_row($f);
        if ($result[0]  == "OPEN") array_push($data, "Accessible");
        else array_push($data, "Inaccessible");  
    }
    oci_close($instance); 
    
    $f = oci_parse($conn, "select AVAILABLE from (select * from pdbhistory order by history_id desc) where rownum <= (select count(*) from pdbservers) order by history_id"); 
    oci_execute($f);
    oci_fetch_all($f, $res, null, null, OCI_FETCHSTATEMENT_BY_COLUMN);
    array_push($history, $res["AVAILABLE"]);
    
    /* Send an email */ 
    if ($data != $history[0]) {
        require 'smtp.php';
        $mail->AddEmbeddedImage($ROOT_PATH . 'img/database_accessible.png', 'database_accessible');
        $mail->AddEmbeddedImage($ROOT_PATH . 'img/database_inaccessible.png', 'database_inaccessible');
        $mail->Subject = 'PDB Databases availability (' . date("d-m-Y H:i", strtotime("1 hour")) . ')';

        $s = oci_parse($conn, "SELECT * FROM pdbservers order by server_name");
        oci_execute($s);
        oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
        
        $body = '<table style="margin:0px auto;width:100%;font-family:Tahoma;font-size:12px;">';
        foreach ($res as $key=>$row) {
            $cid = ($data[$key] == "Accessible") ? 'database_accessible' : 'database_inaccessible';
            $body .= '<th style="padding:0 2%;border:1px solid transparent;text-align:center;font-weight:normal;">';
            $body .= '<img src="cid:' . $cid . '" height="90" />';
            $body .= '<p>' . $row["SERVER_NAME"] . '</p>';
            $body .= '</th>';  
            if (($key + 1)%10 == 0) {
                $body .= '</table><br>';
                $body .= '<table style="margin:0px auto;width:100%;font-family:Tahoma;font-size:12px;">';
            }  
            $h = oci_parse($conn, "INSERT INTO pdbhistory (history_time, server_id, available) VALUES ('" . date('H:i:s', strtotime('1 hour')) . "', " . $row["SERVER_ID"]  . ", '" . $data[$key] . "')");   
            oci_execute($h);
        } 
        $body .= '</table>';
        $mail->Body = $body; // Set the email content
        $mail->send();
    }
    oci_close($conn);  // Close the Oracle connection    
}
?>