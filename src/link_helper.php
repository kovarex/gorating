<?php
function egdAddress($pin)
{
  if (empty($pin))
    return "NULL";
  return "https://www.europeangodatabase.eu/EGD/Player_Card.php?&key=".$pin;
}

function egdLink($pin)
{
  if (empty($pin))
    return "";
  return "<a href=\"".egdAddress($pin)."\">".$pin."</a>";
}

function redirectWithMessageCustom($redirect, $message)
{
  if (empty($redirect))
    die("redirect address not provided.");
  $parameterDelimiter = "?";
  if (str_contains($redirect, "?"))
    $parameterDelimiter = "&";
  header("Location: ".$redirect.$parameterDelimiter."message=".urlencode($message));
  die();
}

function redirectWithMessage($message)
{
  redirectWithMessageCustom($_POST["redirect"], $message);
}

function playerLink($playerID, $playerName)
{
  return "<a href=\"/player?id=".$playerID."\">".$playerName."</a>";
}

function readableTournamentName($tournamentName)
{
  if (empty($tournamentName))
    return "[no name]";
  return $tournamentName;
}

function tournamentLink($tournamentID, $tournamentName)
{
  return "<a href=\"/tournament?id=".$tournamentID."\">".readableTournamentName($tournamentName)."</a>";
}

function generateAddress($url, $get)
{
  if (empty($get))
    return $url;
  $parameters = "";
  foreach($get as $key =>$value)
  {
    if (empty($key))
      continue; // php are you drunk?
    if (empty($parameters))
      $parameters .= "?";
    else
      $parameters .= "&amp;";
    $parameters .= urlencode($key)."=".urlencode($value);
  }
  return $url.$parameters;
}

function countrySelector($selected = NULL)
{
  $result = "<select name=\"country_code\">";
  $result .= "<option value=\"\">Any</option>";
  $data = query("SELECT * from country");
  while ($row = $data->fetch_assoc())
    $result .= "<option value=\"".$row["code"].($selected == $row["code"] ? " selected=\"selected\"" : "")."\">".$row["name"]."</option>";
  $result .= "</select>";
  return $result;
}

?>
