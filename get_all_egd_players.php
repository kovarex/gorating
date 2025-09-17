<?php
require("src/egd_api.php");

if (!empty(query("SELECT id from user")->fetch_assoc()))
{
  echo "This can be ran only on database with no players.";
  return;
}

$data = getUrlContent("https://www.europeangodatabase.eu/EGD/EGD_2_0/downloads/alleuro_lp.html");
if (empty($data))
  die("Couldn't load the page.".$url);
$countriesDBResult = query("SELECT * from country");
while($countryRow = $countriesDBResult->fetch_assoc())
  $country[$countryRow["code"]] = $countryRow["id"];

$separator = "\r\n";
$line = strtok($data, $separator);
$count = 0;
$db->begin_transaction();
while ($line !== false)
{
  $pin = substr($line, 1, 8);
  if (is_numeric($pin))
  {
    $name = trim(substr($line, 11, 39));
    $nameSplit = explode(" ", $name);
    $firstName = $nameSplit[0];
    $lastName = $nameSplit[1];
    if (empty($lastName))
    {
      $lastName = $firstName;
      $lastName = " "; // some people have just a space as a first name
    }
    $countryCode = substr($line, 49, 2);
    $countryID = $country[$countryCode];
    if (empty($countryID))
      die("Unknown country code encountered:".$countryCode);
    $club = trim(substr($line, 53, 6));
    $rating = trim(substr($line, 69, 6));
    $escapedRating = ($rating == 0) ? "0" : escape($rating);
    if (!is_numeric($rating))
      die("pin=".$pin." Encountered non-numeric rating of:".$rating."");
    query("INSERT INTO user(first_name, last_name, egd_pin, egd_rating, rating, country_id, admin_level_id)".
          " VALUES(".escape($firstName).",".escape($lastName).",".escape($pin).",".$escapedRating.",".$escapedRating.",".escape($countryID).",".ADMIN_LEVEL_UNREGISTERED.")", true);
    $count++;
  }
    
  $line = strtok($separator);
}
$db->commit();
echo "<br/><br/>Added ".$count." players.";
?>
