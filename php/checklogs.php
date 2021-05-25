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
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {

        $dump_check = glob($row["SERVER_BACKUP"].'*_'.date("Y_m_d", strtotime("-1 days")).'.dmp') ? "Disponible" : "Indisponible"; 
        $recreate_db_check = glob($row["SERVER_BACKUP"].'recreate_db_*_'.date("Y_m_d", strtotime("-1 days")).'.txt') ? "Disponible" : "Indisponible";
        
        array_push($data, $dump_check);
        array_push($data, $recreate_db_check);
    }

    oci_close($conn);  // Close the Oracle connection
    
    // Return data as JSON
    echo json_encode($data);
}

?>