<?php
if (!canDeleteAnyGame())
{
  echo "No rights to delete this game.";
  return;
}

$id = $_GET["id"];
if (!$id)
{
  echo "Game id to delete not specified";
  return;
}
if (!is_numeric($id))
{
  echo "Game id to delete is not numeric:".$id;
  return;
}

query("DELETE FROM game WHERE id=".escape($id));
redirectWithMessage("Game with id=".$id." deleted.");
?>
