<?php
require("src/db.php");
require("src/register_helper.php");
require("src/egd_api.php");
$checkResult = checkRegistrationAvailability($_POST["invite_id"], $_POST["secret"]);
if (is_string($checkResult))
{
  echo $checkResult;
  return;
}

$invite = query("SELECT * FROM invite WHERE id=".escape($_POST["invite_id"]))->fetch_assoc();
assert(!empty($invite));

$rating = "100";
if ($invite["egd_pin"])
{
  $egdInfo = getEgdInfo($invite["egd_pin"]);
  $rating = $egdInfo["rating"];
}

if (empty($_POST["username"]))
{
  echo "Username can't be empty";
  return;
}

$userByUsername = query("SELECT * FROM user where username=".escape($_POST["username"]))->fetch_assoc();
if (!empty($userByUsername))
{
  echo "That username is taken";
  return;
}

query("INSERT INTO
       user(username,
            first_name,
            last_name,
            email,
            egd_pin,
            rating,
            egd_rating,
            password,
            country_id,
            admin_level_id)
        values(".escape($_POST["username"]).",".
                 escape($invite["first_name"]).",".
                 escape($invite["last_name"]).",".
                 escape($invite["email"]).",".
                 escape($invite["egd_pin"]).",".
                 escape($rating).",".
                 (empty($egdInfo) ? "NULL" : $rating).",".
                 escape(password_hash($_POST["password"], PASSWORD_DEFAULT)).",".
                 "1,
                 ".ADMIN_LEVEL_USER.")");
redirectWithMessageCustom("/player?id=".lastInsertID(), "Registration successful.");
?>
