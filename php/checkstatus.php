
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
    
    // Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        
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
        $mail->Subject = 'Disponibilité des bases de données (' . date("d-m-Y") . ')';   // Set the email subject
        $body = '<ul><li>Disponibilité des serveurs de <b>Développement</b> :</li></ul><br />';
        $body .= '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
        $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA03</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA04</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGHFM08</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">KTPPPROD</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA11</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA12</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA13</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA14</th></tr><tr>';

        if (!$conn) 
        {
            $m = oci_error();
            echo $m['message'], "\n";   //Return the error occured when the connection failed
            exit;
        }
        
        else 
        {
            $s = oci_parse($conn, "SELECT * FROM servers");    // Select all columns from the "servers" table 
            oci_execute($s);    //Execute the query above
            oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
            
            foreach ($res as $row) {

                $ssh = new Net_SSH2($row["SERVER_ADDRESS"]);     //Open SSH session to specified server in the argument
                
                if (!$ssh->login($row["SERVER_LOGIN"], $row["SERVER_PASSWORD"])) {     //Specify the username & the password for our SSH session
                    
                    echo $row["SERVER_NAME"] . ': Login Failed';   //Message returned when connection failed
                }

                if ($row["SERVER_NAME"] == "ALGORA05") {
                    $body .= '</tr></table>';
                    $body .= '<br /><ul><li>Disponibilité des serveurs de <b>Production</b> :</li></ul><br />';
                    $body .= '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
                    $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA05</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA06</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA07</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA08</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA09</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGDWH01</th></tr><tr>';
                }

                $available = $ssh->exec('./checkavaibility') ? "Accessible" : "Inaccessible";   //Run the shell script to check whether the database is accessible or not

                $background_color = ($available == "Accessible") ? '#92D050' : '#e54b4b';
                
                $body .= '<td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:' . $background_color . ';color:#ffffff;text-align:center">';
                $body .= $available;
                $body .= '</td>';
                
                $used_space = $ssh->exec('df -h | grep algisinfs.corp | grep -oh "\w*%"');   //Execute this command to check available disk space for the Backup partition
                $disk_space =  $used_space ? 100 - (int) rtrim($used_space, "%\n") : "NULL";

                $h = oci_parse($conn, "INSERT INTO history (history_time, server_id, available, disk_space)
                VALUES ('" . date('H:i:s', strtotime('1 hour')) . "', " . $row["SERVER_ID"] . ", '" . $available . "', " . $disk_space . ")");    // Insert in the history table, the exact time of execution, the reference to the server, the server avaibility and its free disk space for the Backup partition
                oci_execute($h);
            }
            $body .= '</tr></table>';
            oci_close($conn);  // Close the Oracle connection
        }
        
        $mail->Body = $body; // Set the email content
        $mail->send();

    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

?>