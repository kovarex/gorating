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

if ($invite["egd_pin"])
{
  $egdInfo = getEgdInfo($invite["egd_pin"]);
  $rating = $egdInfo["rating"];
}
elseif ($invite["rating"])
  $rating = $invite["rating"];
else
  $rating = "100";

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
            password,
            country_id,
            admin_level_id,
            invited_by_user_id)
        values(".escape($_POST["username"]).",".
                 escape($invite["first_name"]).",".
                 escape($invite["last_name"]).",".
                 escape($invite["email"]).",".
                 escape($invite["egd_pin"]).",".
                 escape($rating).",".
                 escape(password_hash($_POST["password"], PASSWORD_DEFAULT)).",".
                 "1,".
                 ADMIN_LEVEL_USER.",".
                 $invite["from_user_id"].")");
$newUserID = lastInsertID();
query("DELETE FROM invite WHERE id=".$invite["id"]);
redirectWithMessageCustom("/player?id=".$newUserID, "Registration successful.");
?>
