<?php

require 'conn.php';

$file = '/var/www/html/php/dev16_accessibility.txt';

function smtp($color, $content) {
    require 'smtp.php';                                     
    $mail->Subject = 'ALGORA16 server availability';      // Set the email subject
    $body = 'Hello,<br /><br />';
    $body .= 'The serveur <b style="color:' . $color . '">ALGORA16 (10.100.22.85)</b> is ' . $content . '.<br /><br />';
    $body .= 'Cordially.';   
    $mail->Body = $body; // Set the email content
    $mail->send();
}
   
if (!$conn) {    
    if (file_get_contents($file) == "OPEN") {
      file_put_contents($file, "CLOSE");
      smtp('red', 'inaccessible');
    }     
} else {
    if (file_get_contents($file) == "CLOSE") {
      file_put_contents($file, "OPEN");
      smtp('green', 'accessible again');
    }
}  

oci_close($conn);  // Close the Oracle connection

?>
