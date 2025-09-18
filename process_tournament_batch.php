<?php
require_once("src/link_helper.php");
require_once("src/constants.php");
require_once("src/process_tournament_helper.php");
for ($i = 0; $i < 30; $i++)
{
  if (!processTournament(NULL))
    break;
  sleep(1);
}
?>
