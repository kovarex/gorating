<?php
if (!canProcessTournament())
{
  echo "No right to pricess tournaments";
  return;
}

$key = $_GET["egd_tournament_key"];

if (empty($key))
{
  echo "No tournament key provided";
  return;
}

$existingTournament = query("SELECT * FROM egd_tournament WHERE egd_key =".escape($key))->fetch_assoc();

if (!empty($existingTournament))
{
  echo "Tournament already exist";
  return;
}
  
?>
