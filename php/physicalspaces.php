
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
    $partitions = array();
    $spaces = array();
    $servers = array();
    $backup_dev = false;
    $backup_prod = false;

    $s = oci_parse($conn, "SELECT * FROM servers");    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {

        $ssh = new Net_SSH2($row["SERVER_ADDRESS"]);     //Open SSH session to specified server in the argument
        
        if (!$ssh->login($row["SERVER_LOGIN"], $row["SERVER_PASSWORD"])) {     //Specify the username & the password for our SSH session
            
            echo $row["SERVER_NAME"] . ': Login Failed. ';   //Message returned when connection failed
        }

        
        $disk_space = $ssh->exec("df -h | awk ' { print $1\"\t\"$5 } '");   //Execute this command to check available disk space for the Backup partition

        $partition_use = preg_split( "/\n|\t/", $disk_space);
        for ($i = 1; $i < (count($partition_use) / 2) - 1; $i++) {
            $partition_name = $partition_use[2 * $i];
            $used_space = (int) rtrim($partition_use[2 * $i + 1], "%\n");
            if ($used_space >= 70) {
                if ($partition_name == "algisinfs.corp.sonatrach.dz:/FsOraDev") {
                    if (!$backup_dev) $backup_dev = true;
                    else break;
                }
                else if ($partition_name == "algisinfs.corp.sonatrach.dz:/FsOraProd") {
                    if (!$backup_prod) $backup_prod = true;
                    else break;
                }
                array_push($servers, $row["SERVER_NAME"]);
                array_push($partitions, $partition_name);
                array_push($spaces, $used_space);
            }
        }
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
        $mail->Subject = 'Physical Storage (' . date("d-m-Y") . ')';   // Set the email subject
        $body = '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
        $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Server</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Partition</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Used Space</th></tr>';
        
        for ($i = 0; $i < count($spaces); $i++) {
            
            $background_color = ($spaces[$i] < 80) ? '#f48c0d' : '#e54b4b';
            $body .= '<tr><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">';
            $body .= $servers[$i];
            $body .= '</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">';
            $body .= $partitions[$i];    
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