<?php
checkLoggedIn();

if (empty($_POST["user_id"]))
  redirectWithMessage("user_id of player to register not provided");

$user = query("SELECT * FROM user WHERE user.id=".escape($_POST["user_id"]))->fetch_assoc();

if (empty($user))
  redirectWithMessage("User with id=".$_POST["user_id"]." not found.");

if (@$user["username"])
  redirectWithMessage(playerLink($test["id"], $test["first_name"]." ".$test["last_name"], $test["username"])." is already registered.");

if (empty($_POST["email"]))
  redirectWithMessage("Email not provided");

$test = query("SELECT * FROM user WHERE email=".escape($_POST["email"]))->fetch_assoc();
if (!empty($test))
  redirectWithMessage("Player with this email is already registered");

$secret = rand();
query("INSERT INTO invite(from_user_id, user_id, email, secret) VALUES
      (".userID().",".
         escape($_POST["user_id"]).",".
         escape($_POST["email"]).",".
         $secret.")");


$message = "You have been invited to register as ".playerLink($user, NULL, NULL, "http://".$_SERVER['HTTP_HOST']."/")."<br/>\n";
$message .= "Follow this <a href=\"http://".$_SERVER['HTTP_HOST']."/register?id=".lastInsertID()."&secret=".$secret."\">link</a> to register.";

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
