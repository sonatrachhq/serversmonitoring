<?php

include('phpseclib/Net/SSH2.php');  //Include this library so that we can manipulate SSH sessions

$dev_servers = array('60','97','209','116','80','81','48','28');    //Development server addresses
$prod_servers = array('2.233','2.94','2.191','22.91','2.154','22.5');   //Production server addresses

//Check development servers
for ($i = 0; $i < count($dev_servers); $i++) {
    
    $ssh = new Net_SSH2('10.100.2.' . $dev_servers[$i]);     //Open SSH session to specified server in the argument
    
    if (!$ssh->login('oracle', 'jordan')) {     //Specify the username & the password for our SSH session
        
        exit('Login Failed');   //Message returned when connection failed
    }

    echo $ssh->exec('./checkavaibility') ? "Accessible" : "Inaccessible";   //Run the shell script to check whether the database is accessible or not
    echo "\n";
    if ($i == 2) echo 100 - rtrim($ssh->exec('df -h | grep algisinfs.corp | grep -oh "\w*%"'), "%\n") . "%\n";       //Execute this command to check available disk space for the Backup partition

}

//Check production servers
for ($i = 0; $i < count($prod_servers); $i++) {
    
    $ssh = new Net_SSH2('10.100.' . $prod_servers[$i]);     //Open SSH session to specified server in the argument
    
    if (!$ssh->login('oracle', 'babel')) exit('Login Failed');
    echo $ssh->exec('./checkavaibility') ? "Accessible" : "Inaccessible";   //Run the shell script to check whether the database is accessible or not
    echo "\n";
    if ($i == 2) echo 100 - rtrim($ssh->exec('df -h | grep algisinfs.corp | grep -oh "\w*%"'), "%\n") . "%\n";       //Execute this command to check available disk space for the Backup partition

}

require 'conn.php';

if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    $s = oci_parse($conn, "CREATE TABLE backups 
    ( backup_id number(20) PRIMARY KEY,
    backup_date DATE DEFAULT sysdate,
    server_id number(5) NOT NULL,  
    backup_type varchar2(11) NOT NULL CHECK  (backup_type in ('logFile', 'recreateDb')),
    available varchar2(12) NOT NULL CHECK  (available in ('Disponible', 'Indisponible')),
    CONSTRAINT db_server
        FOREIGN KEY (server_id)
        REFERENCES servers(server_id)
    )");    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_close($conn);  // Close the Oracle connection
}
?>