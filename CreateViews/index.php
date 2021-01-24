
<!DOCTYPE html>
<html lang="en">
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

	$output = shell_exec("echo 'english' | su - oracle -c '/home/oracle/show_pdbs.sh' | grep -oh 'PDB\w*'");    //Run the shell script to check PDB names
	$available_output = explode("\n", $output);

?>
<head>
	<title>Création des vues</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->
	<link rel="icon" type="image/png" href="images/icons/favicon.ico"/>
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/animsition/css/animsition.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/select2/select2.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/daterangepicker/daterangepicker.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="css/util.css">
	<link rel="stylesheet" type="text/css" href="css/main.css">
<!--===============================================================================================-->
</head>
<body>
	<div class="container-contact100">
		<div class="wrap-contact100">
			<form class="contact100-form validate-form">
				<span class="contact100-form-title">
					Création des vues
				</span>

				<div class="wrap-input100 input100-select" data-validate="Name is required">
					<span class="label-input100">PDB source</span>
					<div>
					<select class="selection-2" name="service">
						<?php 
						foreach ($available_output as $value) {
							if ($value != "PDB" && $value != "PDB16" && $value != "") {
								echo "<option>" . $value. "</option>";
							}	
						}
						?>
						</select>
					</div>
					<span class="focus-input100"></span>
				</div>

				<div class="wrap-input100 input100-select" data-validate = "Valid email is required: ex@abc.xyz">
					<span class="label-input100">PDB destination</span>
					<div>
						<select class="selection-2" name="service">
						<?php 
						$s = oci_parse($conn, "select name from v\$pdbs");    // Select all columns from the "servers" table 
					    oci_execute($s); //Execute the query above
					    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
						foreach ($res as $row) {
							if ($row["NAME"] != "PDB" && $row["NAME"] != "PDB16" && $row["NAME"] != "PDB\$SEED") {
								echo "<option>" . $row["NAME"] . "</option>";
							}	
						}
						?>
						</select>
					</div>
					<span class="focus-input100"></span>
				</div>

				<div class="wrap-input100 validate-input">
					<span class="label-input100">Schéma source</span>
					<?php 
					$s = oci_parse($conn, "select name from v\$pdbs");    // Select all columns from the "servers" table 
					oci_execute($s); //Execute the query above
					
					oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
					foreach ($res as $row) {
						$aaa = $row["NAME"];
					}
					?>
					<input class="input100" type="text" name="email" placeholder="Schéma source" value="<?php echo $aaa; ?>">
					<span class="focus-input100"></span>
				</div>

				<div class="wrap-input100 validate-input">
					<span class="label-input100">Schéma destination</span>
					<input class="input100" type="text" name="email" placeholder="Schéma destination">
					<span class="focus-input100"></span>
				</div>

				<div class="wrap-input100 validate-input" data-validate = "Message is required">
					<span class="label-input100">Nom de la vue source</span>
					<input class="input100" type="text" name="email" placeholder="Nom de la vue source">
					<span class="focus-input100"></span>
				</div>

				<div class="wrap-input100 validate-input" data-validate = "Message is required">
					<span class="label-input100">Nom de la vue destination</span>
					<input class="input100" type="text" name="email" placeholder="Nom de la vue destination">
					<span class="focus-input100"></span>
				</div>

				<div class="container-contact100-form-btn">
					<div class="wrap-contact100-form-btn">
						<div class="contact100-form-bgbtn"></div>
						<button class="contact100-form-btn">
							<span>
								Valider
							</span>
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>



	<div id="dropDownSelect1"></div>

<!--===============================================================================================-->
	<script src="vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/animsition/js/animsition.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/bootstrap/js/popper.js"></script>
	<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/select2/select2.min.js"></script>
	<script>
		$(".selection-2").select2({
			minimumResultsForSearch: 20,
			dropdownParent: $('#dropDownSelect1')
		});
	</script>
<!--===============================================================================================-->
	<script src="vendor/daterangepicker/moment.min.js"></script>
	<script src="vendor/daterangepicker/daterangepicker.js"></script>
<!--===============================================================================================-->
	<script src="vendor/countdowntime/countdowntime.js"></script>
<!--===============================================================================================-->
	<script src="js/main.js"></script>

	<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-23581568-13"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-23581568-13');
</script>

</body>
</html>
