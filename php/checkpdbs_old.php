
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

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
    $data = array();
    $history = array();
    $now = new DateTime();
    
    
    $output = shell_exec("echo 'english' | su - oracle -c '/home/oracle/scriptPDB.sh'");    //Run the shell script to check whether the databases are accessibles or not
    echo $output;
    $available_output = explode("\n", $output);
    $s = oci_parse($conn, "SELECT * FROM pdbservers");    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {
        $index = $row["SERVER_ID"] - 1;
        $available = ($available_output[$index] == "OPEN") ? "Accessible" : "Inaccessible";
        array_push($data, $available);
    }
    
    $f = oci_parse($conn, "select * from (select * from history order by history_id desc) where rownum<35 order by history_id"); // Select last 13 inserted rows from the "history" table to check if one of them change its availability status
    oci_execute($f);    //Execute the query above
    oci_fetch_all($f, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows

    foreach ($res as $row) {
        array_push($history, $row["AVAILABLE"]);
    }
    
   
    
    if (($data != $history) || (($now->format('H:i') >= "07:00") && ($now->format('H:i') < "07:05"))) {
        
        // Instantiation and passing `true` enables exceptions
        $mail = new PHPMailer(true);
        
        //Server settings
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->SMTPDebug = 2;                                       // Enable verbose debug output
        $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host = 'webmail.sonatrach.dz';                        // Specify main and backup SMTP servers
        $mail->SMTPAuth = false;                                  // Disable SMTP authentication
        $mail->setFrom('DG-ISI-DBA@Sonatrach.dz', 'DG-ISI-DBA');        // Add a sender
        $mail->addAddress('DG-ISI-DBA@Sonatrach.dz');     // Add a recipient
        $mail->isHTML(true);                                     // Set email format to HTML
        $mail->Subject = 'Databases availability (' . date("d-m-Y H:i", strtotime("1 hour")) . ')';   // Set the email subject
        $body = '<ul><li><b>Database Development Servers :</b></li></ul><br />';
        $body .= '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
        $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA03</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA04</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGHFM08</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">KTPPPROD</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA12</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA13</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA14</th></tr><tr>';
        
        for ($i = 0; $i < count($data); $i++) {

            if ($i == 7) {
                $body .= '</tr></table><br />';
                $body .= '<br /><ul><li><b>Database Production Servers :</b></li></ul><br />';
                $body .= '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
                $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA05</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA06</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA07</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA08</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA09</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGDWH01</th></tr><tr>';
            }
            
         
              $h = oci_parse($conn, "INSERT INTO history (history_time, server_id, available) VALUES ('" . date('H:i:s', strtotime('1 hour')) . "', " . ($i + 1)   . ", '" . $data[$i] . "')");    // Insert in the history table, the exact time of execution, the reference to the server and the server avaibility
              oci_execute($h);
           
        }
        
        $body .= '</tr></table>';
        $bodies = explode("<ul>", $body);
        $body = $bodies[2].$bodies[1].$bodies[3];
        $mail->Body = $body; // Set the email content
        $mail->send();
        
    }

    oci_close($conn);  // Close the Oracle connection
}

?>