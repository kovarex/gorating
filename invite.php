<?php
checkLoggedIn();

if (!empty($_POST["egd_pin"]))
{
  $test = query("SELECT * FROM user WHERE egd_pin=".escape($_POST["egd_pin"]))->fetch_assoc();
  if (!empty($test) and $test["username"])
    redirectWithMessage("Player with pin ".$_POST["egd_pin"]." is already registered.");
}

if (empty($_POST["email"]))
  redirectWithMessage("Email not provided");
$test = query("SELECT * FROM user WHERE email=".escape($_POST["email"]))->fetch_assoc();
if (!empty($test))
  redirectWithMessage("Player with this email is already registered");

$secret = rand();
query("INSERT INTO invite(from_user_id, egd_pin, first_name, last_name, email, secret, rating) VALUES
      (".userID().",".
         escape($_POST["egd_pin"]).",".
         escape($_POST["first_name"]).",".
         escape($_POST["last_name"]).",".
         escape($_POST["email"]).",".
         $secret.",".
         escape($_POST["rating"]).")");


$message = "You have been invited to register as ".$_POST["first_name"]." ".$_POST["last_name"]."\n";
if (!empty($_POST["egd_pin"]))
  $message .= "The proposed associated egd profile:".egdAddress($_POST["egd_pin"])."\n";
$message .= "If that is you and you want to participate, follow the link http://go.kovarex.com/register?id=".lastInsertID()."&secret=".$secret." to register.";
$headers = 'Reply-To: webmaster@kovarex.com\r\n'.
           'X-Mailer: PHP/' . phpversion();

mail($_POST["email"], "Friendly go rating invitation", $message, $headers);

redirectWithMessage("Invite created");         
?>
