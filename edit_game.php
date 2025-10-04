<?php
if (!canEditGames())
  die("No permission to edit games.");

$row = query("SELECT
                winner.id as winner_id,
                winner.name as winner_name,
                winner.username as winner_username,
                game.winner_old_rating as winner_rating,
                game.winner_old_egd_rating as winner_egd_rating,
                loser.id as loser_id,
                loser.name as loser_name,
                loser.username as loser_username,
                game.loser_old_rating as loser_rating,
                game.loser_old_egd_rating as loser_egd_rating,
                game.winner_is_black as winner_is_black,
                game.handicap as handicap,
                game.komi as komi,
                game.location as location,
                length(game.sgf) > 0 has_sgf
              FROM
                game JOIN user as winner ON game.winner_user_id = winner.id
                     JOIN user as loser ON game.loser_user_id = loser.id
              WHERE
                game.id=".escape($_GET["id"]))->fetch_assoc();
if (empty($row))
  die("Unknown game with id=".$_GET["id"]);

function showSelected($value)
{
  if (!$value)
    return "";
  return " selected=\"selected\"";
}
echo "<form method=\"post\" action=\"/edit_game_action\" enctype=\"multipart/form-data\">";
echo "<table>";
echo "<tr>";
  echo "<td>".playerLink($row, "winner")."</td>";
  echo "<td><select id=\"original_winner\" name=\"original_winner\" oninput=\"fixWinner(this);\"><option value=\"winner\" selected=\"select\">Winner</option><option value=\"loser\">Loser</option></select></td>";
  echo "<td><select id=\"original_winner_color\" name=\"original_winner_color\" oninput=\"fixColors(this);\"><option value=\"black\"".showSelected($row["winner_is_black"]).">Black</option><option value=\"white\"".showSelected(!$row["winner_is_black"]).">White</option></select></td>";
echo "</tr>";

echo "<tr>";
  echo "<td>".playerLink($row, "loser")."</td>";
  echo "<td><select id=\"original_loser\" name=\"original_loser\" oninput=\"fixWinner(this);\"><option value=\"winner\">Winner</option><option value=\"loser\" selected=\"select\">Loser</option></select></td>";
  echo "<td><select id=\"original_loser_color\" name=\"original_loser_color\" oninput=\"fixColors(this);\"><option value=\"black\"".showSelected(!$row["winner_is_black"]).">Black</option><option value=\"white\"".showSelected($row["winner_is_black"]).">White</option></select></td>";
echo "</tr>";

echo "<tr><td><label for=\"handicap\">Handicap:</label></td><td colspan=2><input type=\"text\" name=\"handicap\" value=\"".$row["handicap"]."\"/></td></tr>";
echo "<tr><td><label for=\"komi\">Komi:</label></td><td colspan=2><input type=\"text\" id=\"komi\" name=\"komi\" value=\"".$row["komi"]."\"/></td></tr>";
echo "<tr><td><label for=\"komi\">Location:</label></td><td colspan=2><input type=\"text\" id=\"location\" name=\"location\" value=\"".$row["location"]."\"/></td></tr>";
echo "<tr><td><label for=\"komi\">SGF(currently ".($row["has_sgf"] ? "" : "not ")."present:</label></td><td colspan=2><input type=\"file\" name=\"sgf\" accept=\".sgf\"/></td></tr>";
echo "</table>";
echo "<input type=\"hidden\" name=\"id\" value=\"".$_GET["id"]."\"/>";
echo "<input type=\"hidden\" name=\"redirect\" value=\"".$_GET["redirect"]."\"/>";
echo "<input type=\"submit\" value=\"Confirm changes\"/>";
echo "</form>\n";

echo "<script>\n";
echo "function fixColors(caller)\n";
echo "{\n";
echo "  if (caller.id == 'original_winner_color')\n";
echo "  {\n";
echo "    let winnerIsNowBlack = caller.value == 'black';\n";
echo "    document.getElementById('original_loser_color').value = winnerIsNowBlack ? 'white' : 'black';\n";
echo "  }\n";
echo "  else if (caller.id == 'original_loser_color')\n";
echo "  {\n";
echo "    let winnerIsNowBlack = caller.value == 'white';\n";
echo "    document.getElementById('original_winner_color').value = winnerIsNowBlack ? 'black' : 'white';\n";
echo "  }\n";
echo "}\n";

echo "function fixWinner(caller)\n";
echo "{\n";
echo "  if (caller.id =='original_winner')\n";
echo "  {\n";
echo "    let originalWinnerIsNowWinner = caller.value == 'winner';\n";
echo "    document.getElementById('original_loser').value = originalWinnerIsNowWinner ? 'loser' : 'winner';\n";
echo "  }\n";
echo "  else if (caller.id == 'original_loser')\n";
echo "  {\n";
echo "    let originalWinnerIsNowWinner = caller.value == 'loser';\n";
echo "    document.getElementById('original_winner').value = originalWinnerIsNowWinner ? 'winner' : 'loser';\n";
echo "  }\n";
echo "}\n";

echo "</script>";
?>
