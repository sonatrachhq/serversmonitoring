
<?php

header('Access-Control-Allow-Origin: *');

require 'conn.php';

if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    $data=array();  

    $s = oci_parse($conn, "SELECT * FROM apps ORDER BY app_id");    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {
        array_push($data, $row["APP_NAME"]);
    }


    oci_close($conn);  // Close the Oracle connection
    
    // Return data as JSON
    echo json_encode($data);
}

?>