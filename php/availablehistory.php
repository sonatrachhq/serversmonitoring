<?php

header('Access-Control-Allow-Origin: *');

require 'conn.php';
    
if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    $data = array();
    $history = array();
    $available = "";
    $i = 0;
    $duration = new DateTime('NOW');
    $s = oci_parse($conn, "SELECT TO_CHAR(history_date, 'yyyy-mm-dd HH24:MI:SS') history_date, history_time, available FROM history WHERE server_id=" . $_GET["id"] . " ORDER BY history_id DESC"); 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {
        if ($available != $row["AVAILABLE"]) {    
            $earlier = new DateTime($row["HISTORY_DATE"]);
            $diff = $duration->getTimestamp() - $earlier->getTimestamp();
            if ($diff >= 86400) $diff = ($duration->diff($earlier))->format("%a Days %h Hours %i Minutes");
            elseif ($diff >= 3600) $diff = ($duration->diff($earlier))->format("%h Hours %i Minutes");
            else $diff = ($duration->diff($earlier))->format("%i Minutes");
            $history['duration'] = $diff; 
            
            if ($i > 0) array_push($data, $history);
            
            $history['date'] = $row["HISTORY_DATE"];  
            $history['time'] = $row["HISTORY_TIME"];
            $history['availability'] = $row["AVAILABLE"]; 
            
            $duration = new DateTime($row["HISTORY_DATE"]);
            $i++;
        }    
        $available = $row["AVAILABLE"];
    }
    $history['duration'] = NULL;
    array_push($data, $history);
    
    oci_close($conn);  // Close the Oracle connection
    
    // Return data as JSON
    echo json_encode($data);
}

?>