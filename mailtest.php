<?php

require_once "Mail.php";

$from = "PawPrints Support <pawprints@baylor.edu>";
$to = "joseph_rafferty@baylor.edu";
$subject = "Pear Mail test";
$body = "test test test";

$host = "smtp_server";
$port = "25";
$username = "username";
$password = "password";

$headers = array ('From' => $from, 'To' => $to, 'Subject' => $subject);
$smtp = Mail::factory('smtp', array ('host' => $host, 'port' => $port, 'auth' => true, 'username' => $username, 'password' => $password, 'debug' => true));

$mail = $smtp->send($to, $headers, $body);

if (PEAR::isError($mail)) {
  echo("<p>" . $mail->getMessage() . "</p>");
} else {
  echo("<p>Message successfully sent!</p>");
}

?>