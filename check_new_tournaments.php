<?php
require_once("src/egd_api.php");
require_once("src/process_tournament_helper.php");


$doc = getStringDom(getPageOfLatestTournaments());
$tournamentsToIgnore = getTournamentsToIgnore();

$tables = $doc->getElementsByTagName('table');
foreach ($tables as $table)
{
  if (@$table->attributes->getNamedItem("bordercolor")->textContent == "#396B95")
  {
    $rows = $table->getElementsByTagName('tr');
    $tournamentsToProcessInReversedOrder = [];
    foreach ($rows as $row)
    {
      $cells = $row->getElementsByTagName('td');
      if (empty($cells))
        continue;
      $key = $cells[0]->nodeValue;
      if (isTournamentKey($key) and !@$tournamentsToIgnore[$key])
        array_push($tournamentsToProcessInReversedOrder, $key);
    }
    break;
  }
}

if (!isset($tournamentsToProcessInReversedOrder))
  die("No new tournaments found.");

for ($i = count($tournamentsToProcessInReversedOrder) - 1; $i >= 0; $i--)
  query("INSERT INTO egd_tournament_to_process(egd_key) VALUES(".escape($tournamentsToProcessInReversedOrder[$i]).")");
if (count($tournamentsToProcessInReversedOrder))
  echo "Added ".count($tournamentsToProcessInReversedOrder)." to be processed.<br/>\n";

for ($i = 0; $i < 30; $i++)
{
  if (!processTournament(NULL))
    break;
  sleep(1);
}
?>
