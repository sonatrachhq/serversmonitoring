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
    (SERVICE_NAME = pdbexpdpt)
    )
)" ;    // Tnsname of the server DEV13 that we will store all our data in it

$conn = oci_connect('EXP_DBA', 'abd', $db);   // Connection to the database EXP_DBA

if (!$conn) 
{
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
}
 
else 
{
    $dump_array = array("ktpdev", "hfmdev", "pphfm", "ktppprod", "dev12", "dev13", "dev14", "prod05", "prod06", "ora07", "ktpprod", "prod09", "dwh01");
    $recreate_db_array = array("ktpdev", "prod04", "epm", "KTPPREPROD", "dev12", "dev13", "dev14", "prod05", "prod06", "rac7", "ktpprod", "prod09", "dwh01");
    $dump_data = array();
    $recreate_db_data = array();
    
    $s = oci_parse($conn, "SELECT * FROM servers");    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {

        $dump_check = file_exists($row["SERVER_BACKUP"].$dump_array[$row["SERVER_ID"] - 1].'_'.date("Y_m_d", strtotime("-1 days")).'.dmp') ? "Disponible" : "Indisponible";   //Run the shell script to check whether the database is accessible or not
        
        $recreate_db_check = file_exists($row["SERVER_BACKUP"].'recreate_db_'.$recreate_db_array[$row["SERVER_ID"] - 1].'_'.date("Y_m_d", strtotime("-1 days")).'.txt') ? "Disponible" : "Indisponible";   //Run the shell script to check whether the database is accessible or not
        
        array_push($dump_data, $dump_check);
        array_push($recreate_db_data, $recreate_db_check);

        $l = oci_parse($conn, "INSERT INTO backups (server_id, backup_type, available)
        VALUES (" . $row["SERVER_ID"] . ", 'logFile', '" . $dump_check . "')");    // Insert in the backups table, the reference to the server, the backup type and check its avaibility
        oci_execute($l);

        $r = oci_parse($conn, "INSERT INTO backups (server_id, backup_type, available)
        VALUES (" . $row["SERVER_ID"] . ", 'recreateDb', '" . $recreate_db_check . "')");    // Insert in the backups table, the reference to the server, the backup type and check its avaibility
        oci_execute($r);
        
        }

    oci_close($conn);  // Close the Oracle connection
    
    
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
    $mail->Subject = 'Backups and database recreation files availability (' . date("d-m-Y", strtotime("1 hour")) . ')';   // Set the email subject
    $body = '<ul><li><b>Development backups</b> :</li></ul><br />';
    $body .= '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
    $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA03</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA04</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGHFM08</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">KTPPPROD</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA12</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA13</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA14</th></tr><tr>';
        
    for ($i = 0; $i < count($dump_data); $i++) {

        if ($i == 7) {
            $body .= '</tr></table>';
            $body .= '<br /><ul><li><b>Production backups</b> :</li></ul><br />';
            $body .= '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
            $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA05</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA06</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA07</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA08</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA09</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGDWH01</th></tr><tr>';
        }
        
        $background_color = ($dump_data[$i] == "Disponible") ? '#92D050' : '#e54b4b';       
        $body .= '<td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:' . $background_color . ';color:#ffffff;text-align:center">';
        $body .= ($dump_data[$i] == "Disponible") ? 'Available' : 'Unavailable';
        $body .= '</td>';
    }
    
    $body .= '</tr></table><br />';
    
    $body .= '<ul><li><b>Development recreation files</b> :</li></ul><br />';
    $body .= '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
    $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA03</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA04</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGHFM08</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">KTPPPROD</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA12</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA13</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA14</th></tr><tr>';
        
    for ($i = 0; $i < count($recreate_db_data); $i++) {

        if ($i == 7) {
            $body .= '</tr></table>';
            $body .= '<br /><ul><li><b>Production recreation files</b> :</li></ul><br />';
            $body .= '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
            $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA05</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA06</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA07</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA08</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA09</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGDWH01</th></tr><tr>';
        }
        
        $background_color = ($recreate_db_data[$i] == "Disponible") ? '#92D050' : '#e54b4b';       
        $body .= '<td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:' . $background_color . ';color:#ffffff;text-align:center">';
        $body .= ($recreate_db_data[$i] == "Disponible") ? 'Available' : 'Unavailable';
        $body .= '</td>';
    }
    
    $body .= '</tr></table><br /><br />';
    $bodies = explode("<ul>", $body);
    $body = $bodies[2].$bodies[4].$bodies[1].$bodies[3];
    $mail->Body = $body; // Set the email content
    $mail->send();
}

?>