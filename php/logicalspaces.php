<?php

require 'conn.php';

if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    $tablespaces = array();
    $spaces = array();
    $servers = array();

    $s = oci_parse($conn, "SELECT * FROM servers where server_state = 1 order by server_type desc, server_id");
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    

    foreach ($res as $row) {
        $inst = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = ".$row["SERVER_ADDRESS"].")(PORT = 1521)) (CONNECT_DATA = (SERVER = DEDICATED) (SERVICE_NAME = ".$row["DB_SID"].")))" ;
        $instance  = oci_connect("SYS", $row["DB_PASSWORD"] , $inst, null, OCI_SYSDBA);

        $f = oci_parse($instance, "select T1.TABLESPACE_NAME, round(((T1.BYTES-T2.BYTES)/T1.BYTES)*100,2) percent_used from (select TABLESPACE_NAME, sum(BYTES) BYTES from dba_data_files group by TABLESPACE_NAME) T1, (select TABLESPACE_NAME, sum(BYTES) BYTES , max(BYTES) largest from dba_free_space group by TABLESPACE_NAME) T2 where T1.TABLESPACE_NAME=T2.TABLESPACE_NAME order by ((T1.BYTES-T2.BYTES)/T1.BYTES) desc"); // Select each tablespace with its used space
        oci_execute($f);    //Execute the query above
        oci_fetch_all($f, $result, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows

        foreach ($result as $line) {

            if ($line["PERCENT_USED"] >= 70) {
                array_push($servers, $row["SERVER_NAME"]);
                array_push($tablespaces, $line["TABLESPACE_NAME"]);
                array_push($spaces, $line["PERCENT_USED"]);
            }
        }
        oci_close($instance);
    }
    
    $inst = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = 10.100.22.85)(PORT = 1521)) (CONNECT_DATA = (SERVER = DEDICATED) (SERVICE_NAME = dev16)))" ;
    $instance  = oci_connect("SYS", "Gascogne", $inst, null, OCI_SYSDBA);
    
    $s = oci_parse($conn, "SELECT * FROM pdbservers order by server_name");
    oci_execute($s); //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    foreach ($res as $row) { 
        $f = oci_parse($instance, "alter session set container=" . $row["SERVER_NAME"]);
        oci_execute($f);   
        $f = oci_parse($instance, "select T1.TABLESPACE_NAME, round(((T1.BYTES-T2.BYTES)/T1.BYTES)*100,2) percent_used from (select TABLESPACE_NAME, sum(BYTES) BYTES from dba_data_files group by TABLESPACE_NAME) T1, (select TABLESPACE_NAME, sum(BYTES) BYTES , max(BYTES) largest from dba_free_space group by TABLESPACE_NAME) T2 where T1.TABLESPACE_NAME=T2.TABLESPACE_NAME order by ((T1.BYTES-T2.BYTES)/T1.BYTES) desc"); // Select each tablespace with its used space
        oci_execute($f);    //Execute the query above
        oci_fetch_all($f, $result, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows

        foreach ($result as $line) {

            if ($line["PERCENT_USED"] >= 70) {
                array_push($servers, $row["SERVER_NAME"]);
                array_push($tablespaces, $line["TABLESPACE_NAME"]);
                array_push($spaces, $line["PERCENT_USED"]);
            }
        } 
    }
    oci_close($instance);  // Close the Oracle connection   
    

    if (!empty($spaces)) {
        
        require 'smtp.php';
        $mail->Subject = 'Logical Spaces (' . date("d-m-Y") . ')';   // Set the email subject
        $body = '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
        $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Server</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Tablespace</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Used space</th></tr>';
        
        for ($i = 0; $i < count($spaces); $i++) {
            
            $background_color = ($spaces[$i] < 80) ? '#f48c0d' : '#e54b4b';
            $body .= '<tr><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">';
            $body .= $servers[$i];
            $body .= '</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">';
            $body .= $tablespaces[$i];    
            $body .= '</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:' . $background_color . ';color:#fff;text-align:center">';
            $body .= $spaces[$i];
            $body .= '%</td></tr>';
        }
        
        $body .= '</tr></table>';
        $mail->Body = $body; // Set the email content
        $mail->send();
        
    }

    oci_close($conn);  // Close the Oracle connection
}

?>