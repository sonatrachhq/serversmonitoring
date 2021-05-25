<?php

header('Access-Control-Allow-Origin: *');

require 'conn.php';
    
if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    $data = array();
    $backup = array();
    $date = "";
    $s = oci_parse($conn, "SELECT TO_CHAR(backup_date, 'yyyy-mm-dd') backup_date, available FROM backups WHERE backup_type='logFile' AND server_id=" . $_GET["id"] . " ORDER BY backup_id DESC");
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {
      if ($date != $row["BACKUP_DATE"]) {
        $backup['date'] = $row["BACKUP_DATE"];    
        $backup['availability'] = ($row["AVAILABLE"] == "Disponible") ? "Available" : "Unavailable";
        array_push($data, $backup);     
      }  
      $date = $row["BACKUP_DATE"];
    }

    oci_close($conn);  // Close the Oracle connection
    
    // Return data as JSON
    echo json_encode($data);
}

?>