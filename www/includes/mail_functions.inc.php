<?php

require_once "/opt/PHPMailer/src/PHPMailer.php";
require_once "/opt/PHPMailer/src/SMTP.php";
require_once "/opt/PHPMailer/src/Exception.php";

function send_email($recipient_email,$recipient_name,$subject,$body) {

  global $EMAIL, $SMTP, $SITE_URL, $log_prefix;

  $mail = new PHPMailer\PHPMailer\PHPMailer();
  $mail->isSMTP();

  $mail->SMTPDebug = $SMTP['debug_level'];
  $mail->Debugoutput = function($message, $level) { error_log("$log_prefix SMTP (level $level): $message"); };

  $mail->Host = $SMTP['host'];
  $mail->Port = $SMTP['port'];
  
  if (isset($MAIL['username'])) {
    $mail->SMTPAuth = true;
    $mail->Username = $SMTP['user'];
    $mail->Password = $SMTP['pass'];
  }

  if ($MAIL['tls'] == TRUE) { $mail->SMTPSecure = "tls"; }

  $mail->setFrom($EMAIL['from_address'], $EMAIL['from_name']);
  $mail->addAddress($recipient_email, $recipient_name);
  $mail->Subject = $subject;
  $mail->Body = $body;
  $mail->send();

}

?>
