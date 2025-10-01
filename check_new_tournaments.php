<?php
require_once("src/egd_api.php");
require_once("src/process_tournament_helper.php");

function checkNewTournaments()
{
  $doc = getStringDom(getPageOfLatestTournaments());
  $tournamentsToIgnore = getTournamentsToIgnore();

  $tables = $doc->getElementsByTagName('table');
  $tournamentsIgnored = 0;
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
        if (isTournamentKey($key))
        {
          if (!@$tournamentsToIgnore[$key])
            array_push($tournamentsToProcessInReversedOrder, $key);
          else
            $tournamentsIgnored++;
        }
      }
      break;
    }
  }

  if (!isset($tournamentsToProcessInReversedOrder))
    throw new Exception("Didn't find a single entry that would look like a tournament.");

  if (count($tournamentsToProcessInReversedOrder) == 0)
    return "";

  for ($i = count($tournamentsToProcessInReversedOrder) - 1; $i >= 0; $i--)
    query("INSERT INTO egd_tournament_to_process(egd_key) VALUES(".escape($tournamentsToProcessInReversedOrder[$i]).")");
  return "Added ".count($tournamentsToProcessInReversedOrder)." to be processed.<br/>\n";
}

try
{
  echo checkNewTournaments();
  for ($i = 0; $i < TOURNAMENT_COUNT_TO_PRCESS_PER_BATCH; $i++)
  {
    if (!processTournament(NULL))
      break;
    usleep(500 * 1000);
  }
}
catch (Exception $e)
{
  echo "Error: ".$e;
}
?>
