<?php
require_once("src/process_rating_update_helper.php");
if (!canDeleteAnyGame())
{
  echo "No rights to restore this game.";
  return;
}

$id = $_GET["id"];
if (!$id)
{
  echo "Game id to restore not specified";
  return;
}
if (!is_numeric($id))
{
  echo "Game id to restore is not numeric:".$id;
  return;
}

query("UPDATE game SET deleted=false WHERE id=".escape($id));
$message = "Game with id=".$id." restored.<br/>\n";
$message .= processRating(50);
redirectWithMessage($message);
?>
