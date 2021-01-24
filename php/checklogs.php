
<?php

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
    $s = oci_parse($conn, "SELECT * FROM servers");    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {

        $ssh = new Net_SSH2($row["SERVER_ADDRESS"]);     //Open SSH session to specified server in the argument
        
        if (!$ssh->login($row["SERVER_LOGIN"], $row["SERVER_PASSWORD"])) {     //Specify the username & the password for our SSH session
            
            exit('Login Failed');   //Message returned when connection failed
        }

        $log_file_check = $ssh->exec('./checklogfile') ? "Disponible" : "Indisponible";   //Run the shell script to check whether the database is accessible or not
        
        $recreate_db_check = $ssh->exec('./checkrecreatedb') ? "Disponible" : "Indisponible";   //Run the shell script to check whether the database is accessible or not

        $l = oci_parse($conn, "INSERT INTO backups (server_id, backup_type, available)
        VALUES (" . $row["SERVER_ID"] . ", 'logFile', '" . $log_file_check . "')");    // Insert in the backups table, the reference to the server, the backup type and check its avaibility
        oci_execute($l);

        $r = oci_parse($conn, "INSERT INTO backups (server_id, backup_type, available)
        VALUES (" . $row["SERVER_ID"] . ", 'recreateDb', '" . $recreate_db_check . "')");    // Insert in the backups table, the reference to the server, the backup type and check its avaibility
        oci_execute($r);
        
    }

    oci_close($conn);  // Close the Oracle connection
}

?>