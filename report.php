<?php
$opponent = query("SELECT * FROM user WHERE id=".escape($_GET["id"]))->fetch_assoc();
if (empty($opponent))
{
  echo "User with id=".$_GET["id"]." doesn't exist";
  return;
}

echo "<form method=\"post\" action=\"report_action\">";
echo "<table class=\"data-table\">";
echo "<tr><td>Winner:</td><td>".playerLink($opponent["id"], $opponent["first_name"]." ".$opponent["last_name"])."</td><td>Rating ".$opponent["rating"]."</td></tr>";
echo "<tr><td>Loser:</td><td>".playerLink($_SESSION["user"]["id"], $_SESSION["user"]["first_name"]." ".$_SESSION["user"]["last_name"])."<td>Rating ".$_SESSION["user"]["rating"]."</td></tr>";
echo "<tr><td>Game type:</td>";
echo   "<td>";
echo     "<input type=\"radio\" id=\"serious\" name=\"game_type\" value=\"".GAME_TYPE_SERIOUS."\" checked=\"checked\"/>";
echo     "<label for=\"serious\">Serious</label>";
echo     "<input type=\"radio\" id=\"rapid\" name=\"game_type\" value=\"".GAME_TYPE_RAPID."\">";
echo     "<label for=\"rapid\">Rapid</label>";
echo     "<input type=\"radio\" id=\"blitz\" name=\"game_type\" value=\"".GAME_TYPE_BLITZ."\">";
echo     "<label for=\"blitz\">Blitz</label>";
echo   "</td>";
echo "</tr>";
echo "<tr><td>My colour:</td>";
echo   "<td>";
echo     "<input type=\"radio\" id=\"black\" name=\"color\" value=\"Black\" checked=\"checked\"/>";
echo     "<label for=\"black\">Black</label>";
echo     "<input type=\"radio\" id=\"white\" name=\"color\" value=\"White\">";
echo     "<label for=\"white\">White</label>";
echo   "</td>";
echo "</tr>";
echo "<tr><td>Location:</td><td><input type=\"text\" name=\"location\"/></td></tr>";
echo "<tr><td>Comment:</td><td><input type=\"text\" name=\"comment\"/></td></tr>";
echo "</table>";
echo "<input type=\"hidden\" name=\"winner_user_id\" value=\"".$opponent["id"]."\"/>";
echo "<br/>";
echo "<input type=\"submit\" value=\"Report\"/>";
echo "</form>";
?>
