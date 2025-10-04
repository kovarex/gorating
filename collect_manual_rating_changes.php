<?php
require_once("src/rating_change_helper.php");

$userID = @$_GET["id"];
if ($userID)
{
  $user = query("SELECT * FROM user WHERE id=".escape($userID))->fetch_assoc();
  if (!$user)
    return "User with id=\"".$userID."\" doesn't exist.";
}
else
{
  $username = @$_GET["username"];
  if ($username)
  {
    $user = query("SELECT * FROM user WHERE username=".escape($username))->fetch_assoc();
    if (!$user)
      return "User with username=\"".$username."\" doesn't exist.";
    $userID = $user["id"];
  }
  else
    return "User id nor username provided";
}

checkUserRatingChanges($userID);
?>
