<?php

if (empty($_GET["id"]))
{
  echo "Sgf id not provided.";
  return;
}


if (@$_GET["from"] == "change_old")
{
  $sgf = query("SELECT id, old_value as sgf FROM `change` WHERE id=".escape($_GET["id"]))->fetch_assoc();
  $filename = "old_value_from_change_".$_GET["id"];
}
else if (@$_GET["from"] == "change_new")
{
  $sgf = query("SELECT id, new_value as sgf FROM `change` WHERE id=".escape($_GET["id"]))->fetch_assoc();
  $filename = "new_value_from_change_".$_GET["id"];
}
else
{
  $sgf = query("SELECT \n".
               "  sgf, \n".
               "  winner.name as winner_name, \n".
               "  loser.name as loser_name \n".
               "FROM \n".
               "  game \n".
               "    JOIN user as winner ON game.winner_user_id=winner.id\n".
               "    JOIN user as loser ON game.loser_user_id=loser.id\n".
               "WHERE \n".
               "  game.id=".escape($_GET["id"]))->fetch_assoc();
  $filename = $_GET["id"]." ".$sgf["winner_name"]." vs ".$sgf["loser_name"];
}
if (empty($sgf))
{
  echo "Game with id=".$_GET["id"]." not found.";
  return;
}

header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"".$filename.".sgf\"");
echo $sgf["sgf"];
die();
?>
