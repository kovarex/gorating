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
  if (!isset($redirect))
    die("Redirect address not provided.");
  $parameterDelimiter = "?";
  if (str_contains($redirect, "?"))
    $parameterDelimiter = "&";
  header("Location: ".$redirect.$parameterDelimiter."message=".urlencode($message));
  die();
}

function redirectWithMessage($message)
{
  $redirect = @$_POST["redirect"];
  if (!$redirect)
    $redirect = @$_GET["redirect"];
  redirectWithMessageCustom($redirect, $message);
}

function getPlayerPath($userID, $username)
{
  global $page;
  if ($username and !@$page[$username])
    return $username;
  return "player?id=".$userID;
}

function playerLink($playerIDOrUserData, $playerNameOrValuePrefix = null, $username = null, $fullAddress = null)
{
  if (!$playerIDOrUserData)
    return "";

  if (is_array($playerIDOrUserData))
  {
    $prefix = $playerNameOrValuePrefix ? $playerNameOrValuePrefix."_" : "";
    $id = $playerIDOrUserData[$prefix."id"];
    if (!$id)
      return "";
    return playerLink($id,
                      @$playerIDOrUserData[$prefix."name"] ?: $playerIDOrUserData[$prefix."first_name"]." ".$playerIDOrUserData[$prefix."last_name"],
                      $playerIDOrUserData[$prefix."username"],
                      $fullAddress);
  }
  return "<a href=\"".($fullAddress ?: "").getPlayerPath($playerIDOrUserData, $username)."\">".$playerNameOrValuePrefix."</a>";
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

function egdTournamentLink($key)
{
  return "<a href=\"https://www.europeangodatabase.eu/EGD/Tournament_Card.php?&key=".$key."\">".$key."</a>";
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
  $data = query("SELECT * from country ORDER BY name");
  while ($row = $data->fetch_assoc())
    $result .= "<option value=\"".$row["code"]."\"".($selected == $row["code"] ? " selected=\"selected\"" : "").">".$row["name"]."</option>";
  $result .= "</select>";
  return $result;
}

function inviteAddress($inviteID, $secret)
{
  return "http://".$_SERVER['HTTP_HOST']."/register?id=".$inviteID."&secret=".$secret;
}
?>
