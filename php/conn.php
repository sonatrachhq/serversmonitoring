<?php
    
    $ROOT_PATH = '/var/www/html/php/';
    
    $db = "(DESCRIPTION =
      (ADDRESS = (PROTOCOL = TCP)(HOST = 10.100.22.85)(PORT = 1521))
      (CONNECT_DATA =
      (SERVER = DEDICATED)
      (SERVICE_NAME = PDBEXPDPT)
      )
    )" ;    // Tnsname of the server DEV16 that we will store all our data in it
    
    $conn = oci_connect('EXP_DBA', 'abd', $db);   // Connection to the database EXP_DBA

?>