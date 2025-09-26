<?php
checkLoggedIn();

if (empty($_POST["email"]))
  redirectWithMessage("Email not provided");
$test = query("SELECT * FROM user WHERE email=".escape($_POST["email"]))->fetch_assoc();
if (!empty($test))
  redirectWithMessage("Player with this email is already registered");

$secret = rand();
query("INSERT INTO invite(from_user_id, first_name, last_name, email, secret, rating) VALUES
      (".userID().",".
         escape($_POST["first_name"]).",".
         escape($_POST["last_name"]).",".
         escape($_POST["email"]).",".
         $secret.",".
         escape($_POST["rating"]).")");

$message = "You have been invited to register as ".$_POST["first_name"]." ".$_POST["last_name"]." on gorating.com<br/>\n";
$message .= "If you you already exist in EGD (you participated in any official tournament), ignore this email and request invitation that is connected to it.<br/>\n";
$message .= "Otherwise follow <a href=\"http://".$_SERVER['HTTP_HOST']."/register?id=".lastInsertID()."&secret=".$secret."\">the link</a> to register.";

$from = 'webmaster@gorating.com';
$headers = '';
$headers .= 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
$headers .= 'From: ' . $from . ' ' . "\r\n";
$headers .= 'Reply-To: webmaster@gorating.com\r\n';
$headers .= 'X-Mailer: PHP/' . phpversion();

mail($_POST["email"], "Friendly go rating invitation", $message, $headers);

redirectWithMessage("Invite created");
?>
