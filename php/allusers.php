<?php
    
header('Access-Control-Allow-Origin: *');

require 'conn.php';
    
if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    
    $data=array();
    $user=array();   

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