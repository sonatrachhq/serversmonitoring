<?php

require 'conn.php';

if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    
    $change = true;
    require 'smtp.php';
    $mail->AddEmbeddedImage($ROOT_PATH . 'img/backrec.png', 'backup_recreate');
    $mail->AddEmbeddedImage($ROOT_PATH . 'img/backnotrec.png', 'backup_norecreate');
    $mail->AddEmbeddedImage($ROOT_PATH . 'img/notbackrec.png', 'nobackup_recreate');
    $mail->AddEmbeddedImage($ROOT_PATH . 'img/notbacknotrec.png', 'nobackup_norecreate');  
    $mail->Subject = 'Backups and database recreation scripts availability (' . date("d-m-Y", strtotime("1 hour")) . ')';   // Set the email subject
    
    $body = '<ul><li><b>Production backups and recreation scripts</b> :</li></ul><br />';
    $body .= '<table style="margin:0px auto;width:100%;font-family:Tahoma;font-size:14px;font-weight:normal;">';
    
    $s = oci_parse($conn, "SELECT * FROM servers where server_state = 1 order by server_type desc, server_id");    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $row) {

        $dump_check = glob($row["SERVER_BACKUP"].'*_'.date("Y_m_d", strtotime("-1 days")).'.dmp') ? "Disponible" : "Indisponible"; 
        $recreate_db_check = glob($row["SERVER_BACKUP"].'recreate_db_*_'.date("Y_m_d", strtotime("-1 days")).'.txt') ? "Disponible" : "Indisponible";

        $l = oci_parse($conn, "INSERT INTO backups (server_id, backup_type, available) VALUES (" . $row["SERVER_ID"] . ", 'logFile', '" . $dump_check . "')");
        oci_execute($l);

        $r = oci_parse($conn, "INSERT INTO backups (server_id, backup_type, available) VALUES (" . $row["SERVER_ID"] . ", 'recreateDb', '" . $recreate_db_check . "')");    
        oci_execute($r);
        
        if ($row["SERVER_TYPE"] == 'DEV' && $change) {
            $body .= '</table><br />';
            $body .= '<br /><ul><li><b>Development backups and recreation scripts</b> :</li></ul><br />';
            $body .= '<table style="margin:0px auto;width:100%;font-family:Tahoma;font-size:14px;">';
            $change = false;
        }
        
        if ($dump_check == "Disponible" && $recreate_db_check == "Disponible") $cid = 'backup_recreate';
        elseif ($dump_check == "Disponible" && $recreate_db_check == "Indisponible") $cid = 'backup_norecreate';
        elseif ($dump_check == "Indisponible" && $recreate_db_check == "Disponible") $cid = 'nobackup_recreate';
        else $cid = 'nobackup_norecreate';

        $body .= '<th style="padding:0 2%;border:1px solid transparent;text-align:center;font-weight:normal;">';
        $body .= '<img src="cid:' . $cid . '" height="90" />';
        $body .= '<p>' . $row["SERVER_NAME"] . '</p>';
        $body .= '</th>';
        
    }
    oci_close($conn);  // Close the Oracle connection
    
    $body .= '</table><br />';
    $mail->Body = $body; // Set the email content
    $mail->send();
}

?>