<?php
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

if (empty($_POST["username"]))
{
  echo "Username can't be empty";
  return;
}

if ($invite["user_id"])
{
  $existingUser = query("SELECT * FROM user WHERE id=".escape($invite["user_id"]))->fetch_assoc();
  if (!$existingUser)
  {
    echo "The user you are supposed to register as doesn't exist, this shouldn't happen.";
    return;
  }
}
elseif ($invite["rating"])
  $rating = $invite["rating"];
else
  $rating = "100";


$db->begin_transaction();

if (empty($existingUser))
{
  query("INSERT INTO
         user(username,
              first_name,
              last_name,
              email,
              rating,
              password,
              country_id,
              admin_level_id,
              invited_by_user_id,
              register_timestamp)
          values(".escape($_POST["username"]).",".
                   escape($invite["first_name"]).",".
                   escape($invite["last_name"]).",".
                   escape($invite["email"]).",".
                   escape($rating).",".
                   escape(password_hash($_POST["password"], PASSWORD_DEFAULT)).",".
                   "1,".
                   ADMIN_LEVEL_USER.",".
                   $invite["from_user_id"].",
                   now())");
  $newUserID = lastInsertID();
}
else
{
  $userByUsername = query("SELECT * FROM user where username=".escape($_POST["username"]))->fetch_assoc();
  if (!empty($userByUsername))
  {
    echo "The username \"".$_POST["username"]."\" is taken";
    return;
  }

  query("UPDATE user
         SET
           username=".escape($_POST["username"]).",
           email=".escape($invite["email"]).",
           password=".escape(password_hash($_POST["password"], PASSWORD_DEFAULT)).",
           admin_level_id=".ADMIN_LEVEL_USER.",
           invited_by_user_id=".$invite["from_user_id"].",
           register_timestamp=now()
         WHERE
           user.id=".escape($invite["user_id"]));
  $newUserID = $existingUser["id"];
}
query("DELETE FROM invite WHERE user_id=".$newUserID);
$db->commit();
redirectWithMessageCustom(getPlayerPath($newUserID, $_POST["username"]), "Registration successful.");
?>
