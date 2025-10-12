<?php
if (!canEditPlayerName())
{
  echo "No rights to edit names";
  return;
}

$user = query("SELECT * FROM user WHERE user.id=".escape($_POST["id"]))->fetch_assoc();

$message = "";
if ($user["first_name"] != $_POST["first_name"] or $user["last_name"] != $_POST["last_name"])
{

  beginTransaction();
  query("UPDATE user SET first_name=".escape($_POST["first_name"]).", last_name=".escape($_POST["last_name"])." WHERE user.id=".escape($_POST["id"]));
  if ($user["first_name"] != $_POST["first_name"])
    query("INSERT INTO ".
          "  `change`(change_type_id, user_id, executed_by_user_id, old_value, new_value)".
          "  VALUES(".escape(CHANGE_USER_FIRST_NAME).",".escape($user["id"]).",".escape(userID()).",".escape($user["first_name"]).",".escape($_POST["first_name"]).")");
  if ($user["last_name"] != $_POST["last_name"])
    query("INSERT INTO ".
          "  `change`(change_type_id, user_id, executed_by_user_id, old_value, new_value)".
          "  VALUES(".escape(CHANGE_USER_LAST_NAME).",".escape($user["id"]).",".escape(userID()).",".escape($user["last_name"]).",".escape($_POST["last_name"]).")");
  $message .= "Changed name from ".$user["first_name"]." ".$user["last_name"]." to ".$_POST["first_name"]." ".$_POST["last_name"];
  commitTransaction();
}

if (empty($message))
  $message = "No change.";
redirectWithMessageCustom("/player?id=".$_POST["id"], $message);
?>
