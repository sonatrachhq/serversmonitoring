<?php

include('phpseclib/Net/SSH2.php');  //Include this library so that we can manipulate SSH sessions

require 'conn.php';
    
if (!$conn) 
{
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    $backup_dev = false;
    $backup_prod = false;
    
    $servers = array();
    $partitions = array();
    $spaces = array();
    
    $partitions_backup = array();
    $spaces_backup = array();
    
    $servers_critical = array();
    $partitions_critical = array();
    $spaces_critical = array();

    $s = oci_parse($conn, "SELECT * FROM servers where server_state = 1 order by server_type desc, server_id");    // Select all columns from the "servers" table 
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
            
            if (($partition_name == "algisinfs.corp.sonatrach.dz:/FsOraDev") || ($partition_name == "algisinfs.corp.sonatrach.dz:/FsOraProd")) {
                if ($partition_name == "algisinfs.corp.sonatrach.dz:/FsOraDev") {
                    if (!$backup_dev) $backup_dev = true;
                    else break;
                }
                else {
                    if (!$backup_prod) $backup_prod = true;
                    else break;
                }
                array_push($partitions_backup, $partition_name);
                array_push($spaces_backup, $used_space);
            } else {
                if (($used_space >= 70) && ($used_space < 80)) {
                    array_push($servers, $row["SERVER_NAME"]);
                    array_push($partitions, $partition_name);
                    array_push($spaces, $used_space);
                } else {
                    if ($used_space >= 80) {
                    array_push($servers_critical, $row["SERVER_NAME"]);
                    array_push($partitions_critical, $partition_name);
                    array_push($spaces_critical, $used_space);
                }}
            }
        }
    }
    
    $s = oci_parse($conn, "SELECT * FROM apps where app_state = 1 ORDER BY app_id");    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {

        $ssh = new Net_SSH2($row["APP_ADDRESS"]);     //Open SSH session to specified server in the argument
        
        if (!$ssh->login($row["APP_LOGIN"], $row["APP_PASSWORD"])) {     //Specify the username & the password for our SSH session
            
            echo $row["APP_NAME"] . ': Login Failed. ';   //Message returned when connection failed
        }

        
        $disk_space = $ssh->exec("df -h | awk ' { print $1\"\t\"$5 } '");   //Execute this command to check available disk space for the Backup partition
        $partition_use = preg_split( "/\n|\t/", $disk_space);
        
        for ($i = 1; $i < (count($partition_use) / 2) - 1; $i++) {
            $partition_name = $partition_use[2 * $i];
            $used_space = (int) rtrim($partition_use[2 * $i + 1], "%\n");
            if (($used_space >= 70) && ($used_space < 80)) {
                    array_push($servers, $row["APP_NAME"]);
                    array_push($partitions, $partition_name);
                    array_push($spaces, $used_space);
                } else {
                    if ($used_space >= 80) {
                    array_push($servers_critical, $row["APP_NAME"]);
                    array_push($partitions_critical, $partition_name);
                    array_push($spaces_critical, $used_space);
                }}
        }
    }
        
        require 'smtp.php';
        $mail->Subject = 'Physical Storage (' . date("d-m-Y") . ')';   // Set the email subject
        
        $body = '<ul><li><b>Backup Servers Storage :</b></li></ul>';
        $body .= '<br /><table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
        $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Environment</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Partition</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Used Space</th></tr>';
        $background_color = ($spaces_backup[0] < 80) ? '#f48c0d' : '#e54b4b';
        if ($spaces_backup[0] < 70) $background_color = '#92D050';
        $body .= '<tr><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">Production';
        $body .= '</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">';
        $body .= $partitions_backup[0];    
        $body .= '</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:'.$background_color.';color:#ffffff;text-align:center">';
        $body .= $spaces_backup[0];
        $body .= '%</td></tr>';
        $background_color = ($spaces_backup[1] < 80) ? '#f48c0d' : '#e54b4b';
        if ($spaces_backup[1] < 70) $background_color = '#92D050';
        $body .= '<tr><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">Development';
        $body .= '</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">';
        $body .= $partitions_backup[1];    
        $body .= '</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:'.$background_color.';color:#ffffff;text-align:center">';
        $body .= $spaces_backup[1];
        $body .= '%</td></tr>';
        $body .= '</tr></table><br />';
        
        if (!empty($spaces_critical) || !empty($spaces)) $body .= '<ul><li><b>Database & Application Servers Storage :</b></li></ul>';
        
        if (!empty($spaces_critical)) {
            $body .= '<br /><table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
            $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Server</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Partition</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Used Space</th></tr>';
            
            for ($i = 0; $i < count($spaces_critical); $i++) {
                $body .= '<tr><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">';
                $body .= $servers_critical[$i];
                $body .= '</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">';
                $body .= $partitions_critical[$i];    
                $body .= '</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#e54b4b;color:#ffffff;text-align:center">';
                $body .= $spaces_critical[$i];
                $body .= '%</td></tr>';
            }
            $body .= '</tr></table>';
        }
        
        if (!empty($spaces)) {
            $body .= '<br /><table style="border-collapse:collapse;border-spacing: 0;margin:0px auto;table-layout: fixed; width: 100%; font-family:Tahoma, Geneva, sans-serif;font-size:14px;">';
            $body .= '<tr><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Server</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Partition</th><th style="font-family:Tahoma, Geneva, sans-serif !important;;font-size:14px;font-weight:bold;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#000000;background-color:#656565;color:#ffffff;text-align:center">Used Space</th></tr>';
            
            for ($i = 0; $i < count($spaces); $i++) {
                $body .= '<tr><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">';
                $body .= $servers[$i];
                $body .= '</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#ddd;color:#000;text-align:center">';
                $body .= $partitions[$i];    
                $body .= '</td><td style="padding:10px 15px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:inherit;background-color:#f48c0d;color:#ffffff;text-align:center">';
                $body .= $spaces[$i];
                $body .= '%</td></tr>';
            }    
            $body .= '</tr></table>';
        }
        $mail->Body = $body; // Set the email content
        $mail->send();
        

    oci_close($conn);  // Close the Oracle connection
}

?>