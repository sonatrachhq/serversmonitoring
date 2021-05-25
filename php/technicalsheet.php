<?php

header('Access-Control-Allow-Origin: *');

include('phpseclib/Net/SSH2.php');  //Include this library so that we can manipulate SSH sessions

require 'conn.php';
   
if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    $stats = array();
    $data=array();  

    $s = oci_parse($conn, "SELECT * FROM servers WHERE server_id=" . $_GET["id"]);
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {

        $ssh = new Net_SSH2($row["SERVER_ADDRESS"]);
        if (!$ssh->login($row["SERVER_LOGIN"], $row["SERVER_PASSWORD"])) echo $row["SERVER_NAME"] . ': Login Failed. ';   //Message returned when connection failed
        
         $cpu = $ssh->exec("echo " . $row["SERVER_PASSWORD"] . " | su - " . $row["SERVER_LOGIN"] . " -c lscpu | sed -n '4,2p' | awk ' { print $2 }'; echo 'processors of type'; echo " . $row["SERVER_PASSWORD"] . " | su - " . $row["SERVER_LOGIN"] . " -c lscpu | sed -n '13,2p' | awk ' { for(i=NF-6;i<=NF;++i)print \$i}'");
         
         $ram = $ssh->exec("free -m -h | awk '{print $2}' | sed -n '2 p'");
    
         $disk = $ssh->exec("echo " . $row["SERVER_SUDOPW"] . " | su  - root -c 'fdisk -l | grep -Eo \"Disk([[:space:]]+[^[:space:]]+){3}\" | grep -v \"dos\"'");
         $disk = str_replace(",", "", $disk);
         
         $ip = $row["SERVER_ADDRESS"];
         
         $os =  $ssh->exec("cat /etc/centos-release");
          
         $oracle = $ssh->exec("echo " . $row["SERVER_PASSWORD"] . " | su - " . $row["SERVER_LOGIN"] . " -c 'echo 'exit' | echo `sqlplus / as sysdba` | grep -Eo \"Oracle([[:space:]]+[^[:space:]]+){6}\" | head -1'");
         
         $nls = $ssh->exec("echo " . $row["SERVER_PASSWORD"] . " | su - " . $row["SERVER_LOGIN"] . " -c 'echo \"exit\" | echo \"select value from (select value, rownum as rn from (select n.value from v\\\$nls_parameters n order by parameter)) where rn in(9,15,2);\" |  echo `sqlplus / as sysdba`  | grep -Eo \"VALUE([[:space:]]+[^[:space:]]+){4}\"'");
         $nls = str_replace("-", "", $nls);
         $nls = explode(" ", $nls);
         $nls = $nls[4] . '_' . $nls[5] . '.' . $nls[3];
                
         $stats['cpu'] = str_replace("Password: ", "", $cpu);  
         $stats['ram'] = $ram;  
         $stats['disk'] = str_replace("Password: ", "\n", $disk);
         $stats['ip'] = $ip;  
         $stats['os'] = $os;  
         $stats['oracle'] = str_replace("Password: ", "", $oracle);
         $stats['nls'] = $nls;
         
         array_push($data, $stats);
    }
    
    $s = oci_parse($conn, "SELECT * FROM servers WHERE server_id=" . $_GET["id"]);    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows    
    foreach ($res as $row) {
        
        $host = $row["SERVER_ADDRESS"];
        $instance = $row["DB_SID"];
        
      	$db2 = "(DESCRIPTION =
      		(ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = 1521))
      		(CONNECT_DATA =
      		(SERVER = DEDICATED)
      		(SERVICE_NAME = $instance)
      		)
      	)" ;    // Tnsname of the server
      	
  	    $conn2 = oci_connect("SYS", $row["DB_PASSWORD"], $db2, null, OCI_SYSDBA);   // Connection to the database EXP_DBA
  
      	$s2 = oci_parse($conn2, "SELECT case when u.default_tablespace='DATA' then 'OP' else  MIN(USERNAME) end as USERNAME, MIN(ACCOUNT_STATUS) STATUS FROM dba_users u WHERE USERNAME NOT IN ('INTERDB_LINKS', 'COMMUNS') AND INITIAL_RSRC_CONSUMER_GROUP = 'DEFAULT_CONSUMER_GROUP' AND regexp_like(DEFAULT_TABLESPACE, 'TBS*|*DATA') GROUP BY DEFAULT_TABLESPACE"); 
      	oci_execute($s2); //Execute the query above
      	
        oci_fetch_all($s2, $res2, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
      	foreach ($res2 as $row2) {    
             $user['name'] = $row2["USERNAME"];  
             $user['status'] = $row2["STATUS"]; 
             array_push($data, $user);    	
      	}						
  	    oci_close($conn2);     
    }
    
    oci_close($conn);  // Close the Oracle connection
    
    // Return data as JSON
    echo json_encode($data);
}

?>
