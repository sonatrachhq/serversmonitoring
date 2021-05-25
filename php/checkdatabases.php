<?php

header('Access-Control-Allow-Origin: *');

require 'conn.php';
    
if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    $data=array();
    
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

    oci_close($conn);  // Close the Oracle connection
    
    // Return data as JSON
    echo json_encode($data);
}

?>
