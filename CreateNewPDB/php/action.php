<?php 
	header('Access-Control-Allow-Origin: *');

	$db = "(DESCRIPTION =
		(ADDRESS = (PROTOCOL = TCP)(HOST = 10.100.22.85)(PORT = 1521))
		(CONNECT_DATA =
		(SERVER = DEDICATED)
		(SERVICE_NAME = DEV16)
		)
	)" ;    // Tnsname of the server DEV16 that we will store all our data in it
	
    $conn = oci_connect("SYS", "Gascogne", $db, null, OCI_SYSDBA);   // Connection to the database EXP_DBA
	
	/* The call */
	$sql = "CALL create_new_pdbs(:pdbname, :schemaname, :schemapw, :datasharepw)";

	/* Parse connection and sql */
	$stmt= oci_parse($conn, $sql);
	
	$schemaname = strtoupper($_POST['schemaname']);

	/* Binding Parameters */
	oci_bind_by_name($stmt, ':pdbname', $_POST['pdbname']) ;
	oci_bind_by_name($stmt, ':schemaname', $schemaname) ;
	oci_bind_by_name($stmt, ':schemapw', $_POST['schemapw']) ;
	oci_bind_by_name($stmt, ':datasharepw', $_POST['datasharepw']) ;

	/* Execute */
	$res = oci_execute($stmt);
	oci_close($conn);

	header("Location: ../index.php");

?>





