<?php
$pageStart = microtime(true);
require_once("src/link_helper.php");
require_once("src/constants.php");
require_once("src/process_tournament_helper.php");
$count = query("SELECT COUNT(*) as count FROM egd_tournament_to_process")->fetch_assoc()["count"];
if ($count > 0)
  echo "Remaining tournaments to process:".$count."<br/>\n";
for ($i = 0; $i < TOURNAMENT_COUNT_TO_PRCESS_PER_BATCH; $i++)
{
  if (!processTournament(NULL))
    break;
  usleep( 500 * 1000 );
}
$count = query("SELECT COUNT(*) as count FROM egd_tournament_to_process")->fetch_assoc()["count"];
if ($count > 0)
  echo "Remaining after processing :".$count."<br/>\n";
$pageEnd = microtime(true);
$execution_time = ($pageEnd - $pageStart);
echo "Executed in ".round($execution_time, 4)." seconds";
?>
