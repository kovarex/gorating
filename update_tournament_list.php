<?php
require("src/egd_api.php");

$data = getUrlContent("https://www.europeangodatabase.eu/EGD/EGD_2_0/downloads/tlist.html");
if (empty($data))
  die("Couldn't load the page.".$url);

$processedTournamentsResult = query("select egd_key FROM egd_tournament");
while ($row = $processedTournamentsResult->fetch_assoc())
  $tournamentsToIgnore[$row["egd_key"]] = true;

$tournamentsMarkedForUpdate = query("select egd_key FROM egd_tournament_to_process");
while ($row = $tournamentsMarkedForUpdate->fetch_assoc())
  $tournamentsToIgnore[$row["egd_key"]] = true;

$separator = "\r\n";
$line = strtok($data, $separator);
$tournamentsToProcessInReversedOrder = [];

while ($line !== false)
{
  $keyCandidate = substr($line, 0, 11);
  if ($keyCandidate[0] == " " and
      $keyCandidate[10] == " " and
      ($keyCandidate[1] == "T" or
       $keyCandidate[1] == "W" or
       $keyCandidate[1] == "E" or
       $keyCandidate[1] == "G"))
  {
    $key = trim($keyCandidate);
    if (!@$tournamentsToIgnore[$key])
      array_push($tournamentsToProcessInReversedOrder, $key);
  }
  $line = strtok($separator);
}

for ($i = count($tournamentsToProcessInReversedOrder) - 1; $i >= 0; $i--)
  query("INSERT INTO egd_tournament_to_process(egd_key) VALUES(".escape($tournamentsToProcessInReversedOrder[$i]).")");
echo "Inserted ".count($tournamentsToProcessInReversedOrder)." tournaments to be processed";
?>
