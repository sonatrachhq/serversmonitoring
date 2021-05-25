<?php

header('Access-Control-Allow-Origin: *');

require 'conn.php';
    
if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    $output = shell_exec("echo 'english' | su - oracle -c '/home/oracle/create_views.sh'"); 
    oci_close($conn);  // Close the Oracle connection
}

?>
