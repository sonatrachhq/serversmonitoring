
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
	<title>	Script création PDB</title>
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
			<form class="contact100-form validate-form" method="post" action="php/action.php">
				<span class="contact100-form-title">
				   Script création PDB
				</span>

				<div class="wrap-input100 input100-select" data-validate="Nom requis">
					<span class="label-input100">Nom du PDB</span>
					<div>
					<select class="selection-2" name="pdbname" required>
						<?php 
						foreach ($available_output as $value) {
							if ($value != "PDB" && $value != "PDB16" && $value != "") {
								echo "<option value='$value'>" . $value. "</option>";
							}	
						}
						?>
						</select>
					</div>
					<span class="focus-input100"></span>
				</div>

				<div class="wrap-input100 validate-input" data-validate = "Nom du schéma requis">
					<span class="label-input100">Nom du nouveau schéma</span>
					<input class="input100" type="text" name="schemaname" placeholder="" required>
					<span class="focus-input100"></span>
				</div>

				<div class="wrap-input100 validate-input" data-validate = "Mot de passe requis">
					<span class="label-input100">Mot de passe du nouveau schéma</span>
					<input class="input100" type="password" name="schemapw" placeholder="" required>
					<span class="focus-input100"></span>
				</div>

				<div class="wrap-input100 validate-input" data-validate = "Mot de passe requis">
					<span class="label-input100">Mot de passe du schéma DATASHARE</span>
					<input class="input100" type="password" name="datasharepw" placeholder="" required>
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

</body>
</html>
