<?php
require("src/auth.php");
require("src/egd_api.php");
require("src/db.php");
if (!canProcessTournament())
{
  echo "No right to pricess tournaments";
  return;
}

$key = $_GET["egd_tournament_key"];

if (empty($key))
{
  echo "No tournament key provided.";
  return;
}

$existingTournament = query("SELECT * FROM egd_tournament WHERE egd_key =".escape($key))->fetch_assoc();

if (!empty($existingTournament))
{
  echo "Tournament already exists.";
  return;
}

function addEGDPlayerIfNotPresent($pin, $firstName, $lastName)
{
  $player = query("SELECT * FROM user WHERE egd_pin =".escape($pin))->fetch_assoc();
  if (!empty($player))
    return;
  $info = getEgdInfo($pin);
  query("INSERT INTO
           user(first_name,
                last_name,
                egd_pin,
                egd_rating,
                rating,
                country_id,
                admin_level_id)
           VALUES(".escape($firstName).",".
                    escape($lastName).",".
                    escape($pin).",".
                    escape($info["rating"]).",".
                    escape($info["rating"]).",".
                    escape($info["country"]["id"]).",".
                    ADMIN_LEVEL_UNREGISTERED.")");
}

$doc = getPageDom("https://www.europeangodatabase.eu/EGD/Tournament_ShowGoR.php?key=".$key);

$inputs = $doc->getElementsByTagName('input');
foreach ($inputs as $input)
{
  $inputName = $input->attributes->getNamedItem("name")->textContent;
  $value = $input->attributes->getNamedItem("value")->textContent;
  if ($inputName == "nation")
    $country = getCountryCodeAndID($value);
  elseif ($inputName == "class")
    $gameTypeID = getGameTypeID($value);
  elseif ($inputName == "tournament_date")
    $date = $value;
  elseif ($inputName == "city")
    $city = $value;
}

$spans = $doc->getElementsByTagName('span');
foreach ($spans as $span)
  if ($span->attributes->getNamedItem("class")->textContent == "plain5")
  {
    $tournamentName = $span->nodeValue;
    break;
  }

if (empty($tournamentName))
  die("Tournament name couldn't be determined.");
if (empty($country))
  die("Tournament country info couldn't be determined.");
if (empty($gameTypeID))
  die("Game type info couldn't be determined.");
if (empty($date))
  die("Tournament date couldn't be determined.");
if (empty($city))
  die("Tournament city couldn't be determined.");

$divs = $doc->getElementsByTagName("div");

// process players first, before the transaction
foreach ($divs as $div)
  if ($div->attributes->getNamedItem("class")->textContent == "thisdiv")
  {
    $playerLink = $div->getElementsByTagname("a")[0]->nodeValue;
    $pieces = explode(" ", $playerLink);
    $pin = $pieces[0];
    if (!is_numeric($pin))
      die("Pin ".$pin." isn't numeric");
    $firstName = $pieces[2];
    $lastName = $pieces[3];
    for ($i = 4; $i < count($pieces); $i++)
      $lastName .= " ".$pieces[$i];
    addEGDPlayerIfNotPresent($pin, $firstName, $lastName);
  }
  
$db->begin_transaction();
query("INSERT INTO
         egd_tournament(egd_key,
                        timestamp,
                        country_id,
                        game_type_id,
                        city,
                        name)
         VALUES(".escape($pin).",".
                  escape(date("Y-m-d H:i:s", strtotime($date))).",".
                  escape($country["id"]).",".
                  escape($gameTypeID).",".
                  escape($city).",".
                  escape($tournamentName).")");
$db->commit();
echo "Tournament processed succesfully";
?>
