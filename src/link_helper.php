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
    return "NULL";
  return "<a href=\"".egdAddress($pin)."\">".$pin."</a>";
}

function redirectWithMessage($message)
{
  if (empty($_POST["redirect"]))
    die("redirect address not provided.");
  header("Location: ".$_POST["redirect"]."?message=".urlencode($message));
  die();
}

function playerLink($playerID, $playerName)
{
  return "<a href=\"/player?id=".$playerID."\">".$playerName."</a>";
}
?>
