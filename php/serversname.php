
<?php

header('Access-Control-Allow-Origin: *');

include('phpseclib/Net/SSH2.php');  //Include this library so that we can manipulate SSH sessions


$db = "(DESCRIPTION =
    (ADDRESS = (PROTOCOL = TCP)(HOST = 10.100.22.85)(PORT = 1521))
    (CONNECT_DATA =
    (SERVER = DEDICATED)
    (SERVICE_NAME = PDBEXPDPT)
    )
)" ;    // Tnsname of the server DEV16 that we will store all our data in it

$conn = oci_connect('EXP_DBA', 'abd', $db);   // Connection to the database EXP_DBA

    
if (!$conn) 
{
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
}

else 
{
    $data=array();  

    $s = oci_parse($conn, "SELECT * FROM servers");    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {
        
        array_push($data, $row["SERVER_NAME"]);
        
    }


    oci_close($conn);  // Close the Oracle connection
    
    // Return data as JSON
    echo json_encode($data);
}

?>