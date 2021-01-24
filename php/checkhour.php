
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

        $i++;
        $ssh = new Net_SSH2($row["SERVER_ADDRESS"]);     //Open SSH session to specified server in the argument
        
        if (!$ssh->login($row["SERVER_LOGIN"], $row["SERVER_PASSWORD"])) {     //Specify the username & the password for our SSH session
            
            echo $row["SERVER_NAME"] . ': Login Failed. ';   //Message returned when connection failed
        }

        $available = $ssh->exec('./checkavaibility') ? "Accessible" : "Inaccessible";   //Run the shell script to check whether the database is accessible or not
        
        $used_space = $ssh->exec('df -h | grep algisinfs.corp | grep -oh "\w*%"');   //Execute this command to check available disk space for the Backup partition

        $disk_space =  $used_space ? 100 - (int) rtrim($used_space, "%\n") : "NULL";

        $h = oci_parse($conn, "INSERT INTO history (history_time, server_id, available, disk_space)
        VALUES ('" . date('H:i:s', strtotime('1 hour')) . "', " . $row["SERVER_ID"] . ", '" . $available . "', " . $disk_space . ")");    // Insert in the history table, the exact time of execution, the reference to the server, the server avaibility and its free disk space for the Backup partition
        oci_execute($h);
    }


    oci_close($conn);  // Close the Oracle connection
}

?>