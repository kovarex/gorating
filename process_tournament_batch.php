<?php
require("src/process_tournament_helper.php");
for ($i = 0; $i < 30; $i++)
{
  if (!processTournament(NULL))
    break;
  sleep(1);
}
?>
