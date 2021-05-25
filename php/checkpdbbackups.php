<?php

include('phpseclib/Net/SSH2.php');  //Include this library so that we can manipulate SSH sessions

require 'conn.php';
    
if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";   //Return the error occured when the connection failed
    exit;
} else {
    require 'smtp.php';
    $mail->AddEmbeddedImage($ROOT_PATH . 'img/pdbbackrec.png', 'pdb_backup_recreate');
    $mail->AddEmbeddedImage($ROOT_PATH . 'img/pdbbacknotrec.png', 'pdb_backup_norecreate');
    $mail->AddEmbeddedImage($ROOT_PATH . 'img/pdbnotbackrec.png', 'pdb_nobackup_recreate');
    $mail->AddEmbeddedImage($ROOT_PATH . 'img/pdbnotbacknotrec.png', 'pdb_nobackup_norecreate'); 
    $mail->AddEmbeddedImage($ROOT_PATH . 'img/notpdbbackrec.png', 'nopdb_backup_recreate');
    $mail->AddEmbeddedImage($ROOT_PATH . 'img/notpdbbacknotrec.png', 'nopdb_backup_norecreate');
    $mail->AddEmbeddedImage($ROOT_PATH . 'img/notpdbnotbackrec.png', 'nopdb_nobackup_recreate');
    $mail->AddEmbeddedImage($ROOT_PATH . 'img/notpdbnotbacknotrec.png', 'nopdb_nobackup_norecreate'); 
    $mail->Subject = 'PDB Databases and Backups availability (' . date("d-m-Y", strtotime("1 hour")) . ')';   // Set the email subject
    $body = '<table style="margin:0px auto;width:100%;font-family:Tahoma;font-size:12px;">';
    
    $s = oci_parse($conn, "SELECT * FROM pdbservers order by server_name");    // Select all columns from the "servers" table 
    oci_execute($s);    //Execute the query above
    oci_fetch_all($s, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);     // Return a single sub array by rows
    
    foreach ($res as $key=>$row) {

        $f = oci_parse($conn, "select AVAILABLE from (select * from (select * from pdbhistory order by history_id desc) where rownum <= (select count(*) from pdbservers) order by history_id) WHERE SERVER_ID=". $row['SERVER_ID']); 
        oci_execute($f);
        $result = oci_fetch_row($f);
        $available = $result[0];
        $cid = ($available == "Accessible") ? 'pdb_' : 'nopdb_';  
        
        $backup = (file_exists('/data/dbBackupsDev/algora16/exports/export_'. $row["SERVER_NAME"] . '_' . date("Y_m_d", strtotime('-1 days')) . '.dmp')) ? "Available" : "Unavailable"; 
        $l = oci_parse($conn, "INSERT INTO pdbbackups (server_id, backup_type, available) VALUES (" . $row["SERVER_ID"] . ", 'logFile', '" . $backup . "')");
        oci_execute($l);
        $cid .= ($backup == "Available") ? 'backup_' : 'nobackup_';

        $recreatedb = (file_exists('/data/dbBackupsDev/algora16/exports/recreate_db_'. $row["SERVER_NAME"] . '_' . date("Y_m_d", strtotime('-1 days')) . '.txt')) ? "Available" : "Unavailable";
        $r = oci_parse($conn, "INSERT INTO pdbbackups (server_id, backup_type, available) VALUES (" . $row["SERVER_ID"] . ", 'recreateDb', '" . $recreatedb . "')");    
        oci_execute($r);
        $cid .= ($recreatedb == "Available") ? 'recreate' : 'norecreate';

        $body .= '<th style="padding:0 2%;border:1px solid transparent;text-align:center;font-weight:normal;">';
        $body .= '<img src="cid:' . $cid . '" height="90" />';
        $body .= '<p>' . $row["SERVER_NAME"] . '</p>';
        $body .= '</th>';
        
        if (($key + 1)%10 == 0) {
            $body .= '</table><br>';
            $body .= '<table style="margin:0px auto;width:100%;font-family:Tahoma;font-size:12px;">';
        } 
    }
    oci_close($conn);  // Close the Oracle connection
    
    $body .= '</table>';
    $mail->Body = $body; // Set the email content
    $mail->send();  
}
?>