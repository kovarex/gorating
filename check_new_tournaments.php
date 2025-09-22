<?php
require_once("src/egd_api.php");
require_once("src/process_tournament_helper.php");

$post_data = array('ricerca' => '1',
                   'orderBy' => 'orderBy=Tournament_Date,Tournament_Code',
                   'viewStart' => 'viewStart=0',
                   'orderDir' => 'orderDir=DESC',
                   'tournament_code' => '',
                   'date_from' => '',
                   'date_to' => '',
                   'tournament_description' => '',
                   'country_code' => '*',
                   'city' => '*',
                   'filter' => 'All');

$doc = getPageDom("https://www.europeangodatabase.eu/EGD/Find_Tournament.php", $post_data);


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
