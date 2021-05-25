<?php

include('phpseclib/Net/SSH2.php');  

require 'conn.php';
    
if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";
    exit;
} else {
    $s = oci_parse($conn, "SELECT * FROM servers where server_state = 1 order by server_type desc, server_id");
    oci_execute($s);
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
    
    foreach ($res as $row) {

        $i++;
        $ssh = new Net_SSH2($row["SERVER_ADDRESS"]);
        if (!$ssh->login($row["SERVER_LOGIN"], $row["SERVER_PASSWORD"])) echo $row["SERVER_NAME"] . ': Login Failed. ';

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