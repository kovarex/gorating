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

function tournamentLink($tournamentID, $tournamentName)
{
  return "<a href=\"/tournament?id=".$tournamentID."\">".$tournamentName."</a>";
}
?>
