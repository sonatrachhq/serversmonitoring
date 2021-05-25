<?php

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Instantiation and passing `true` enables exceptions
$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->SMTPDebug = 2;                                       // Enable verbose debug output
    $mail->isSMTP();                                           // Set mailer to use SMTP
    /*$mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );*/
    $mail->Host       = 'webmail.sonatrach.dz';                        //10.111.106.51  //mail.ep.sonatrach.dz // Specify main and backup SMTP servers
    //$mail->Host       = '10.111.106.50;10.111.106.51;10.111.106.52';                        // Specify main and backup SMTP servers
    $mail->SMTPAutoTLS = false;
    $mail->SMTPAuth   = false;                                   // Disable SMTP authentication
    
    #$mail->Username   = 'son8115@corp.sonatrach.dz';                              // SMTP username
    #$mail->Password   = 'Aissa1994';                           // SMTP password
    #$mail->SMTPSecure = 'tls';                                 // Enable TLS encryption, `ssl` also accepted
    #$mail->Port       = 25;                                    // TCP port to connect to

    //Recipients
    $mail->setFrom('DG-ISI-DBA@Sonatrach.dz', 'DG-ISI-DBA');
    $mail->addAddress('DG-ISI-DBA@Sonatrach.dz', 'DG-ISI-DBA');     // Add a recipient
    #$mail->addAddress('ellen@example.com');               // Name is optional
    #$mail->addReplyTo('info@example.com', 'Information');
    #$mail->addCC('cc@example.com');
    #$mail->addBCC('bcc@example.com');

    // Attachments
    #$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    #$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Disponibilité des bases de données';
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    #$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>