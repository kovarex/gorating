<?php
require_once("src/egd_api.php");
require_once("src/process_tournament_helper.php");

$data = getUrlContent("https://www.europeangodatabase.eu/EGD/EGD_2_0/downloads/tlist.html");
if (empty($data))
  die("Couldn't load the page.".$url);

$tournamentsToIgnore = getTournamentsToIgnore();

$separator = "\r\n";
$line = strtok($data, $separator);
$tournamentsToProcessInReversedOrder = [];

while ($line !== false)
{
  $keyCandidate = substr($line, 0, 11);
  if ($keyCandidate[0] == " " and $keyCandidate[10] == " ")
  {
    $key = trim($keyCandidate);
    if (isTournamentKey($key) && !@$tournamentsToIgnore[$key])
      array_push($tournamentsToProcessInReversedOrder, $key);
  }
  $line = strtok($separator);
}

for ($i = count($tournamentsToProcessInReversedOrder) - 1; $i >= 0; $i--)
  query("INSERT INTO egd_tournament_to_process(egd_key) VALUES(".escape($tournamentsToProcessInReversedOrder[$i]).")");
echo "Inserted ".count($tournamentsToProcessInReversedOrder)." tournaments to be processed";

for ($i = 0; $i < TOURNAMENT_COUNT_TO_PRCESS_PER_BATCH; $i++)
{
  if (!processTournament(NULL))
    break;
  usleep(500 * 1000);
}
