
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';


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
    $tablespaces = array();
    $spaces = array();
    $servers = array();

    $s = oci_parse($conn, "SELECT * FROM servers");    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {

        echo $row["SERVER_ADDRESS"];
        $inst = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = ".$row["SERVER_ADDRESS"].")(PORT = 1521)) (CONNECT_DATA = (SERVER = DEDICATED) (SERVICE_NAME = ".$row["DB_SID"].")))" ;
        $instance  = oci_connect('EXP_DASHBOARD', 'exp_dashboard', $inst);   // Connection to the database EXP_DBA

        $f = oci_parse($instance, "select T1.TABLESPACE_NAME, round(((T1.BYTES-T2.BYTES)/T1.BYTES)*100,2) percent_used from (select TABLESPACE_NAME, sum(BYTES) BYTES from dba_data_files group by TABLESPACE_NAME) T1, (select TABLESPACE_NAME, sum(BYTES) BYTES , max(BYTES) largest from dba_free_space group by TABLESPACE_NAME) T2 where T1.TABLESPACE_NAME=T2.TABLESPACE_NAME order by ((T1.BYTES-T2.BYTES)/T1.BYTES) desc"); // Select each tablespace with its used space
        oci_execute($f);    //Execute the query above
        oci_fetch_all($f, $result, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows

        foreach ($result as $line) {

            if ($line["PERCENT_USED"] >= 70) {
                array_push($servers, $row["SERVER_NAME"]);
                array_push($tablespaces, $line["TABLESPACE_NAME"]);
                array_push($spaces, $line["PERCENT_USED"]);
            }
        }
        oci_close($instance);  // Close the Oracle connection    
    }

    if (!empty($spaces)) {
        
        // Instantiation and passing `true` enables exceptions
        $mail = new PHPMailer(true);
        
        //Server settings
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->SMTPDebug = 2;                                       // Enable verbose debug output
        $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host       = 'webmail.sonatrach.dz';                        // Specify main and backup SMTP servers
        $mail->SMTPAuth   = false;                                  // Disable SMTP authentication
        $mail->setFrom('DG-ISI-DBA@Sonatrach.dz', 'DG-ISI-DBA');        // Add a sender
        $mail->addAddress('DG-ISI-DBA@Sonatrach.dz');     // Add a recipient
        $mail->isHTML(true);                                     // Set email format to HTML
        $mail->Subject = 'Logical Spaces (' . date("d-m-Y") . ')';   // Set the email subject
        $body .= '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
        $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Le serveur</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">La nom de la tablespace</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">L\'espace utilisé</th></tr>';
        
        for ($i = 0; $i < count($spaces); $i++) {
            
            $background_color = ($spaces[$i] < 80) ? '#f48c0d' : '#e54b4b';
            $body .= '<tr><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">';
            $body .= $servers[$i];
            $body .= '</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">';
            $body .= $tablespaces[$i];    
            $body .= '</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:' . $background_color . ';color:#ffffff;text-align:center">';
            $body .= $spaces[$i];
            $body .= '%</td></tr>';
        }
        
        $body .= '</tr></table>';
        $mail->Body = $body; // Set the email content
        $mail->send();
        
    }

    oci_close($conn);  // Close the Oracle connection
}

?>