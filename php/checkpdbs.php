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
    (SERVICE_NAME = DEV16)
    )
)" ;    // Tnsname of the server DEV16 that we will store all our data in it

$conn = oci_connect('SYS', 'Gascogne', $db, null, OCI_SYSDBA);   // Connecting to SYS

    
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
    
   
    //($data != $history) || (($now->format('H:i') >= "07:00") && ($now->format('H:i') < "07:05"))
    if (1 == 1) {
        
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
        $mail->Subject = 'PDB Databases availability (' . date("d-m-Y H:i", strtotime("1 hour")) . ')';   // Set the email subject
        $body = '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 40%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
        $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">PDB name</th><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;font-weight:bold;border-color:inherit;background-color:#656565;color:#ffffff;text-align:center">PDB status</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;font-weight:bold;border-color:inherit;background-color:#656565;color:#ffffff;text-align:center">PDB backup</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;font-weight:bold;border-color:inherit;background-color:#656565;color:#ffffff;text-align:center">PDB recreatedb</td></tr>';
        
        $s = oci_parse($conn, "select name, open_mode from v\$pdbs order by open_mode, name");    // Select all columns from the "servers" table 
        oci_execute($s); //Execute the query above
        oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
        
        foreach ($res as $row) {
            if ($row["NAME"] != "PDB" && $row["NAME"] != "PDB16" && $row["NAME"] != "PDB\$SEED") {
                $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:white;color:black;text-align:center">' . $row["NAME"] . '</th>';
                $available = ($row["OPEN_MODE"] == "READ WRITE") ? "Accessible" : "Inaccessible";
                array_push($data, $available);
                $background_color = ($available == "Accessible") ? '#92D050' : '#e54b4b';       
                $body .= '<td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:' . $background_color . ';color:#ffffff;text-align:center">' . $available . '</td>';
                $backup = (file_exists('/data/dbBackupsDev/algora16/exports/export_'. $row["NAME"] . '_' . date("Y_m_d", strtotime('-1 days')) . '.dmp')) ? "Available" : "Unavailable";
                $background_backup = ($backup == "Available") ? '#92D050' : '#e54b4b'; 
                $body .= '<td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:' . $background_backup . ';color:#ffffff;text-align:center">' . $backup . '</td>';
                $recreatedb = (file_exists('/data/dbBackupsDev/algora16/exports/recreate_db_'. $row["NAME"] . '_' . date("Y_m_d", strtotime('-1 days')) . '.txt')) ? "Available" : "Unavailable";
                $background_recreatedb = ($recreatedb == "Available") ? '#92D050' : '#e54b4b'; 
                $body .= '<td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:' . $background_recreatedb . ';color:#ffffff;text-align:center">' . $backup . '</td></tr>';
            }	
        }
        
        /*$f = oci_parse($conn, "select * from (select * from history order by history_id desc) where rownum<35 order by history_id"); // Select last 13 inserted rows from the "history" table to check if one of them change its availability status
        oci_execute($f);    //Execute the query above
        oci_fetch_all($f, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
        foreach ($res as $row) {
            array_push($history, $row["AVAILABLE"]);
        }*/
            
         
              //$h = oci_parse($conn, "INSERT INTO history (history_time, server_id, available) VALUES ('" . date('H:i:s', strtotime('1 hour')) . "', " . ($i + 1)   . ", '" . $data[$i] . "')");    // Insert in the history table, the exact time of execution, the reference to the server and the server avaibility
              //oci_execute($h);
          
        $body .= '</table>';
        $mail->Body = $body; // Set the email content
        $mail->send();
    }
        
}

oci_close($conn);  // Close the Oracle connection


?>