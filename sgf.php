<?php

if (empty($_GET["id"]))
{
  echo "Sgf id not provided.";
  return;
}

$sgf = query("SELECT id, sgf FROM game WHERE id=".escape($_GET["id"]))->fetch_assoc();
if (empty($sgf))
{
  echo "Game with id=".$_GET["id"]." not found.";
  return;
}

header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"game_".$sgf["id"].".sgf\"");
echo $sgf["sgf"];
die();
?>
