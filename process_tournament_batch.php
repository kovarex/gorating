<?php
require_once("src/link_helper.php");
require_once("src/constants.php");
require_once("src/process_tournament_helper.php");
$count = query("SELECT COUNT(*) as count FROM egd_tournament_to_process")->fetch_assoc()["count"];
if ($count > 0)
  echo "Remaining tournaments to process:".$count."<br/>\n";
for ($i = 0; $i < 50; $i++)
{
  if (!processTournament(NULL))
    break;
  usleep( 500 * 1000 );
}
?>
