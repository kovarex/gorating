<?php
if (!canEditPlayerName())
{
  echo "No rights to edit names";
  return;
}

$user = query("SELECT * FROM user WHERE user.id=".escape($_POST["id"]))->fetch_assoc();
query("UPDATE user SET first_name=".escape($_POST["first_name"]).", last_name=".escape($_POST["last_name"])." WHERE user.id=".escape($_POST["id"]));
redirectWithMessageCustom("/player?id=".$_POST["id"], "Named changed from ".$user["first_name"]." ".$user["last_name"]." to ".$_POST["first_name"]." ".$_POST["last_name"]);
?>
