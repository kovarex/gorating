<?php
require_once("src/rating_helper.php");
$opponent = query("SELECT * FROM user WHERE id=".escape($_GET["id"]))->fetch_assoc();
if (empty($opponent))
{
  echo "User with id=".$_GET["id"]." doesn't exist";
  return;
}

echo "<form method=\"post\" enctype=\"multipart/form-data\" action=\"report_action\" onsubmit=\"return tryToSubmit();\">";
echo "<table class=\"data-table\">";
echo "<tr><td>Winner:</td><td>".playerLink($opponent)." (".round($opponent["rating"]).")</td></tr>";
//echo "<tr><td>Loser:</td><td>".playerLink($_SESSION["user"])." (".round($_SESSION["user"]["rating"]).")</td></tr>";
echo "<tr><td>Game type:</td>";
echo   "<td>";
echo     "<input type=\"radio\" id=\"serious\" name=\"game_type\" value=\"".GAME_TYPE_SERIOUS."\" checked=\"checked\" oninput=\"updateRatingPreview();\"/>";
echo     "<label for=\"serious\">Serious</label>";
echo     "<input type=\"radio\" id=\"rapid\" name=\"game_type\" value=\"".GAME_TYPE_RAPID."\"  oninput=\"updateRatingPreview();\">";
echo     "<label for=\"rapid\">Rapid</label>";
echo     "<input type=\"radio\" id=\"blitz\" name=\"game_type\" value=\"".GAME_TYPE_BLITZ."\"  oninput=\"updateRatingPreview();\">";
echo     "<label for=\"blitz\">Blitz</label>";
echo   "</td>";
echo "</tr>";
echo "<tr><td>My colour:</td>";
echo   "<td>";
echo     "<input type=\"radio\" id=\"black\" name=\"color\" value=\"black\" checked=\"checked\" oninput=\"updateRatingPreview();\"/>";
echo     "<label for=\"black\">Black</label>";
echo     "<input type=\"radio\" id=\"white\" name=\"color\" value=\"white\" oninput=\"updateRatingPreview();\">";
echo     "<label for=\"white\">White</label>";
echo   "</td>";
echo "</tr>";
echo "<tr><td><label for=\"handicap\">Handicap:</label></td><td><input type=\"text\" name=\"handicap\" id=\"handicap\" oninput=\"updateRatingPreview();\" value=\"0\"/></td></tr>";
echo "<tr><td><label for=\"komi\">Komi:</label></td><td><input type=\"text\" name=\"komi\" id=\"komi\" value=\"6.5\" oninput=\"updateRatingPreview();\"/></td></tr>";
echo "<tr><td>Location:</td><td><input type=\"text\" name=\"location\"/></td></tr>";
echo "<tr><td>Comment:</td><td><input type=\"text\" name=\"comment\"/></td></tr>";
echo "<tr><td>SGF:</td><td><input type=\"file\" name=\"sgf\" accept=\".sgf\"/></td></tr>";
echo "<tr><td>My handicap adjusted rating:</td><td><span id=\"adjusted-rating\"></span></td></tr>";
echo "<tr><td>Rating change preview:</td><td><span id=\"rating-preview\" class=\"loser\"></span></td></tr>";
echo "</table>";
echo "<input type=\"hidden\" name=\"winner_user_id\" value=\"".$opponent["id"]."\"/>";
echo "<input type=\"submit\" value=\"Report\"/>";
echo "<br/>";
echo "</form>";

echo "<script>\n";
echo createJsFunctionToCalculateRating();
echo "var myOldRating = ".$_SESSION["user"]["rating"].";\n";
echo "var opponentOldRating = ".$opponent["rating"].";\n";
echo "function showRating(rating)\n";
echo "{\n";
echo "  return Math.round(rating * 10)/10;\n";
echo "}\n";

echo "function showRatingChange(ratingChange)\n";
echo "{\n";
echo "  return (ratingChange > 0 ? '+' : '') + Math.round(ratingChange * 10)/10;\n";
echo "}\n";

echo "function currentGameType()\n";
echo "{\n";
echo "  if (document.getElementById('serious').checked)\n";
echo "    return ".GAME_TYPE_SERIOUS.";\n";
echo "  if (document.getElementById('rapid').checked)\n";
echo "    return ".GAME_TYPE_RAPID.";\n";
echo "  if (document.getElementById('blitz').checked)\n";
echo "    return ".GAME_TYPE_BLITZ.";\n";
echo "}\n";
echo "function updateRatingPreview()\n";
echo "{\n";
echo " let myColorIsBlack = document.getElementById('black').checked;\n";
echo " let myExtraKomi = (myColorIsBlack ? -1 : 1) * (document.getElementById('komi').value - 6.5);\n";
echo " let myExtraHandicap = (myColorIsBlack ? 1 : -1) * document.getElementById('handicap').value;\n";
echo " let myNewRating = calculateNewRating(myOldRating,\n";
echo "                                      opponentOldRating,\n";
echo "                                      0,\n";
echo "                                      currentGameType(),\n";
echo "                                      myExtraHandicap,\n";
echo "                                      myExtraKomi);\n";
echo " let cell = document.getElementById('rating-preview');\n";
echo " cell.innerHTML=showRatingChange(myNewRating - myOldRating) + '&nbsp;&nbsp;(' + showRating(myOldRating) + '&rarr;' + showRating(myNewRating) + ')'\n";
echo " document.getElementById('adjusted-rating').innerHTML=showRating(calculateAdjustedRating(myOldRating, myExtraHandicap, myExtraKomi));\n";
echo "}\n";
echo "updateRatingPreview();\n";
echo "function tryToSubmit()\n";
echo "{\n";
echo "  let myColorIsBlack = document.getElementById('black').checked;\n";
echo "  let myExtraKomi = (myColorIsBlack ? -1 : 1) * (document.getElementById('komi').value - 6.5);\n";
echo "  let myExtraHandicap = (myColorIsBlack ? 1 : -1) * document.getElementById('handicap').value;\n";
echo "  let myOldAdjustedRating = calculateAdjustedRating(myOldRating, myExtraHandicap, myExtraKomi);\n";
echo "  if (myOldRating > opponentOldRating &&\n";
echo "      myOldAdjustedRating > myOldRating &&\n";
echo "      !window.confirm(\"You are stronger than your opponent, yet you gave yourself handicap, are you sure?\"))\n";
echo "    return false;\n";
echo "  if (myOldRating < opponentOldRating &&\n";
echo "      myOldAdjustedRating < myOldRating &&\n";
echo "      !window.confirm(\"You are weaker than your opponent, but you gave your opponent handicap, are you sure?\"))\n";
echo "    return false;\n";
echo "  return true;\n";
echo "}\n";
echo "</script>\n\n";

?>
