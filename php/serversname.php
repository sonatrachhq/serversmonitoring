<?php

header('Access-Control-Allow-Origin: *');

require 'conn.php';
  
if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    $data=array();  
    $s = oci_parse($conn, "SELECT * FROM servers where server_state = 1 order by server_type desc, server_id");    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_COLUMN);  
    array_push($data, $res["SERVER_NAME"]);
    oci_close($conn);  // Close the Oracle connection
    
    // Return data as JSON
    echo json_encode($data[0]);
}

?>