 <?php
      use PHPMailer\PHPMailer\PHPMailer;
      use PHPMailer\PHPMailer\Exception;
      
      require 'PHPMailer/src/Exception.php';
      require 'PHPMailer/src/PHPMailer.php';
      require 'PHPMailer/src/SMTP.php';

      // Instantiation and passing `true` enables exceptions
      $mail = new PHPMailer(true);
      
      //Server settings
      $mail->CharSet     = 'UTF-8';
      $mail->Encoding    = 'base64';
      $mail->SMTPDebug   = 2;                                            // Enable verbose debug output                                            
      $mail->Host        = '10.111.106.50;10.111.106.51;10.111.106.52';  // Specify main and backup SMTP servers
      $mail->SMTPAutoTLS = false;                                        // Enable TLS protocole
      $mail->SMTPAuth    = false;                                        // Disable SMTP authentication
      $mail->isSMTP();                                                   // Set mailer to use SMTP
      $mail->setFrom('DG-ISI-DBA@Sonatrach.dz', 'DG-ISI-DBA');           // Add a sender
      $mail->addAddress('DG-ISI-DBA@Sonatrach.dz');                      // Add a recipient
      $mail->isHTML(true);                                               // Set email format to HTML
?>