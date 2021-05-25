<?php

require 'conn.php';

try {
    
    require 'smtp.php';
    $mail->Subject = 'Disponibilité des bases de données (' . date("d-m-Y") . ')';   // Set the email subject
    $body = '<ul><li>Disponibilité des serveurs de <b>Développement</b> :</li></ul><br />';
    $body .= '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
    $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA03</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA04</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGHFM08</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">KTPPPROD</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA12</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA13</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA14</th></tr><tr>';

    if (!$conn) 
    {
        $m = oci_error();
        echo $m['message'], "\n";   //Return the error occured when the connection failed
        exit;
    }
    
    else 
    {
        $output = shell_exec("echo 'oracle' | su - oracle -c '/home/oracle/script.sh'");    //Run the shell script to check whether the databases are accessibles or not
        $available_output = explode("\n", $output);
        $s = oci_parse($conn, "SELECT * FROM servers");    // Select all columns from the "servers" table 
        oci_execute($s);    //Execute the query above
        oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
        
        foreach ($res as $row) {

            if ($row["SERVER_NAME"] == "ALGORA05") {
                $body .= '</tr></table>';
                $body .= '<br /><ul><li>Disponibilité des serveurs de <b>Production</b> :</li></ul><br />';
                $body .= '<table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
                $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA05</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA06</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA07</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA08</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGORA09</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">ALGDWH01</th></tr><tr>';
            }

            $index = $row["SERVER_ID"] - 1;
            $available = ($available_output[$index] == "OPEN") ? "Accessible" : "Inaccessible";
            $background_color = ($available == "Accessible") ? '#92D050' : '#e54b4b';
            
            $body .= '<td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:' . $background_color . ';color:#ffffff;text-align:center">';
            $body .= $available;
            $body .= '</td>';
            
            $used_space = shell_exec("echo 'root' | su - root -c 'df -h | grep FsOraDev | grep -oh \"\w*%\"'");   //Execute this command to check available disk space for the Backup partition
            //$used_space = shell_exec("echo 'root' | su - root -c 'df -h | grep FsOraProd | grep -oh \"\w*%\"'");
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
