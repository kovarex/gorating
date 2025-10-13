<?php
require("src/table_viewer.php");
$player = query("SELECT * from user where id=".escape($_GET["id"]))->fetch_assoc();
if (empty($player))
  die("Unknown player with id=".$_GET["id"]);

echo "<div class=\"centered-div\">";
echo "<h1 style=\"display: inline;\">".playerNameWithRank($player)."</h1>";
if (canEditPlayerName())
  echo "<button type=\"button\" onclick=\"showEditDialog(event);\">Edit</button>";
echo "</div>";

echo "<div id=\"edit-dialog\" style=\"position:absolute;background: white;display:none;\">";
echo "<form action=\"edit_player_name_action\" method=\"post\" class=\"data-form\">";
echo "<table>";
echo "<tr><td><label for=\"first_name\">First name:</label></td><td><input type=\"text\" name=\"first_name\" value=\"".$player["first_name"]."\"/></td></tr>";
echo "<tr><td><label for=\"last_name\">Last name:</label></td><td><input type=\"text\" name=\"last_name\" value=\"".$player["last_name"]."\"/></td></tr>";
echo "</table>";
echo "<input type=\"hidden\" name=\"id\" value=\"".$player["id"]."\"/>";
echo "<input type=\"submit\" value=\"Submit\"/>";
echo "</form>";
echo "</div>";

if (userID() && userID() != $player["id"])
{
  echo "<div class=\"centered-div\"><a class=\"report-loss-link\" href=\"";
  if (@$player["username"])
    echo "report?id=".$player["id"]."\">Report loss";
  else
    echo "invite_existing_user?user_id=".$player["id"]."\">Invite";
  echo "</a></div>";
}

echo "<table class=\"centered-table\">";
echo "<tr><td>Rating:</td><td>".showRating($player["rating"])."</td></tr>";
echo "<tr><td>EGD Rating:</td><td>".showRating($player["egd_rating"])."</td></tr>";
if (!empty($player["egd_pin"]))
  echo "<tr><td>EGD PIN:</td><td>".egdLink($player["egd_pin"])."</td></tr>";
echo "</table>";

echo "<script>\n";
echo "function tryToDeleteGame(id)\n";
echo "{\n";
echo "  if (confirm('Are you sure to delete the game?'))\n";
echo "    window.location.replace('/delete_game_action?id=' + id + '&redirect=".urlencode($_SERVER["REQUEST_URI"])."');\n";
echo "}\n";
echo "</script>\n";

$table = new TableViewer("  game LEFT JOIN egd_tournament ON game.egd_tournament_id = egd_tournament.id,
                            user as winner,
                            user as loser,
                            game_type
                          WHERE
                            game.deleted = false and
                            game.winner_user_id = winner.id and
                            game.loser_user_id = loser.id and
                            game.game_type_id = game_type.id and
                            (winner_user_id=".escape($_GET["id"])." or loser_user_id=".escape($_GET["id"]).")",
                          $_GET);
$table->addSecondaryUnionAll("    rating_change\n".
                             "      JOIN rating_change_type ON rating_change.rating_change_type_id=rating_change_type.id\n".
                             "      LEFT JOIN user as executed_by ON rating_change.executed_by_user_id=executed_by.id\n".
                             "  WHERE\n".
                             "    rating_change.user_id=".escape($_GET["id"])."\n");
$table->setSpecialRow(function($row)
                      {
                        if (empty($row["rating_change_type_id"]))
                          return false;
                        echo "<tr>";
                        echo "<td colspan=".(userID() ? "2" : "1")."></td>";

                        echo "<td ".(getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ? "style=\"text-align:right;border-right:none;\"" : "style=\"text-align:center;\"").">".
                             ($row["old_rating"] ?
                               showRatingChange($row["old_rating"],
                                                $row["new_rating"],
                                                getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ?
                                                  "</td><td style=\"text-align:right;border-left:none;\">" :
                                                  "&nbsp;") :
                               (getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ? "</td><td style=\"border-left:none;\">" : ""))."</td>";

                        echo "<td ".(getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ? "style=\"text-align:right;border-right:none;\"" : "style=\"text-align:center;\"").">".
                             ($row["old_egd_rating"] ?
                               showRatingChange($row["old_egd_rating"],
                                                $row["new_egd_rating"],
                                                getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ?
                                                  "</td><td style=\"text-align:right;border-left:none;\">" :
                                                  "&nbsp;") :
                               (getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ? "</td><td>" : ""))."</td>";
                        switch ($row["rating_change_type_id"])
                        {
                          case RATING_CHANGE_TYPE_AUTOMATIC_EGD_RATING_RANK_RESET_OLD: $typeName = "Automatic"; break;
                          case RATING_CHANGE_TYPE_AUTOMATIC_EGD_RATING_RANK_RESET_NEW: $typeName = "Automatic"; break;
                          case RATING_CHANGE_TYPE_AUTOMATIC_EGD_RATING_RANK_RESET_UNKNOWN: $typeName = "Unknown"; break;
                          case RATING_CHANGE_TYPE_MANUAL_EGD_RANK_PROMOTION: $typeName = "Manual"; break;
                          case RATING_CHANGE_TYPE_MANUAL_RANK_PROMOTION: $typeName = "Manual"; break;
                          case RATING_CHANGE_TYPE_ERROR: $typeName = "Errornous"; break;
                        }
                        echo "<td colspan=4>".$typeName." rating change issued by ".(empty($row["opponent_id"]) ? "EGD" : playerLink($row, "opponent"))."</td>";
                        echo "<td>".date("d. m. Y H:i", strtotime($row["timestamp"]))."</td>";
                        echo "<td colspan=".(canDeleteAnyGame() ? "6" : "5").">".$row["comment"]."</td>";
                        echo "</tr>";
                        return true;
                      });

$table->setPrimarySort(new SortDefinition("timestamp", false));
$table->setLastSort(new SortDefinition("egd_tournament_round", false));

if (canEditAnyGame() or userID() == $_GET["id"] or userID() == $_GET["id"])
  $table->addColumn("",
                    "",
                    array(),
                    function($row)
                    {
                      global $player;
                      if (canEditGame($row["opponent_id"], $_GET["id"], $row["timestamp"]))
                        echo "<a href=\"/edit_game?id=".$row["game_id"]."&redirect=".urlencode(getPlayerPath($player["id"], @$player["username"]))."\">Edit</a>";
                    });

$table->addColumn("result",
                  "",
                  array(array("IF(game.jigo, 'JIGO', IF(winner.id = ".escape($_GET["id"]).", 'WIN', 'LOSS'))", "result"),
                        array("NULL", "rating_change_type_id")),
                  function($row) { echo resultToImage($row["result"]); },
                  "style=\"text-align:center;\"")
      ->addSecondary(array(array("NULL", "result"),
                           array("rating_change_type_id", "rating_change_type_id")));

$table->addColumn("rating_change",
                  "Rating",
                  array(array("IF(winner.id = ".escape($_GET["id"]).", ".
                                "IFNULL(game.winner_new_rating, 0) -".
                                "IFNULL(game.winner_old_rating, 0),".
                                "IFNULL(game.loser_new_rating, 0) -".
                                "IFNULL(game.loser_old_rating, 0))", "rating_change"),
                        array("IF(winner.id = ".escape($_GET["id"]).", game.winner_old_rating, game.loser_old_rating)", "old_rating"),
                        array("IF(winner.id = ".escape($_GET["id"]).", game.winner_new_rating, game.loser_new_rating)", "new_rating")),
                  function($row)
                  {
                    if ($row["old_rating"])
                      echo showRatingChange($row["old_rating"],
                                            $row["new_rating"],
                                            getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ?
                                              "</td><td style=\"text-align:right;border-left:none;\">" :
                                              "&nbsp;");
                    else
                      echo getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ? "</td><td style=\"border-left: none;\">" : "";
                  },
                  getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ? "style=\"text-align:right;border-right:none;\"" : "style=\"text-align:center;\"")
      ->addSecondary(array(array("rating_change.new_rating - rating_change.old_rating", "rating_change"),
                           array("rating_change.old_rating", "old_rating"),
                           array("rating_change.new_rating", "new_rating")))
      ->setHeaderAttributes(getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ? "colspan=2" : "");

$table->addColumn("egd_rating_change",
                  "EGD Rating",
                  array(array("IF(winner.id = ".escape($_GET["id"]).", ".
                                "IFNULL(game.winner_new_egd_rating, 0) -".
                                "IFNULL(game.winner_old_egd_rating, 0),".
                                "IFNULL(game.loser_new_egd_rating, 0) -".
                                "IFNULL(game.loser_old_egd_rating, 0))", "egd_rating_change"),
                        array("IF(winner.id = ".escape($_GET["id"]).", game.winner_old_egd_rating, game.loser_old_egd_rating)", "old_egd_rating"),
                        array("IF(winner.id = ".escape($_GET["id"]).", game.winner_new_egd_rating, game.loser_new_egd_rating)", "new_egd_rating")),
                  function($row)
                  {
                    if ($row["old_egd_rating"])
                      echo showRatingChange($row["old_egd_rating"],
                                            $row["new_egd_rating"],
                                            getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ?
                                              "</td><td style=\"text-align:right;border-left:none;\">" :
                                              "&nbsp;");
                    else
                      echo getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ? "</td><td style=\"border-left: none;\">" : "";
                  },
                  getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ? "style=\"text-align:right;border-right:none;\"" : "style=\"text-align:center;\"")
       ->addSecondary(array(array("rating_change.new_egd_rating - rating_change.old_egd_rating", "egd_rating_change"),
                            array("rating_change.old_egd_rating", "old_egd_rating"),
                            array("rating_change.new_egd_rating", "new_egd_rating")))
       ->setHeaderAttributes(getRatingChangeFormat() == RATING_CHANGE_FORMAT_CHANGE_AND_RESULT ? "colspan=2" : "");

$table->addColumn("opponent_name",
                  "Opponent",
                  array(array("IF(winner.id = ".escape($_GET["id"]).", loser.name, winner.name)", "opponent_name"),
                        array("IF(winner.id = ".escape($_GET["id"]).", loser.username, winner.username)", "opponent_username"),
                        array("IF(winner.id = ".escape($_GET["id"]).", game.loser_user_id, game.winner_user_id)", "opponent_id"),
                        array("IF(winner.id = ".escape($_GET["id"]).", game.loser_new_rating, game.winner_new_rating)", "opponent_rating"),
                        array("IF(winner.id = ".escape($_GET["id"]).", game.loser_new_egd_rating, game.winner_new_egd_rating)", "opponent_egd_rating")),
                  function($row) { echo playerLink($row, "opponent"); })
       ->addSecondary(array(array("executed_by.name", "opponent_name"),
                            array("executed_by.username", "opponent_username"),
                            array("executed_by.id", "opponent_id"),
                            array("executed_by.rating", "opponent_rating"),
                            array("executed_by.egd_rating", "opponent_egd_rating")));

$table->addColumn("game_type_name",
                  "Game type",
                  array(array("game_type.name", "game_type_name")),
                  function($row) { echo $row["game_type_name"]; },
                  "style=\"text-align:center;\"");

$table->addColumn("color",
                  "Color",
                  array(array("IF((winner.id = ".escape($_GET["id"])." = winner_is_black), 'Black', 'White')", "color")),
                  function($row) { echo $row["color"]; },
                  "style=\"text-align:center;\"");

$table->addColumn("handicap",
                  "Handicap",
                  array(array("game.handicap", "handicap"),
                        array("game.komi", "komi"),),
                  function($row) { echo showHandicap($row["handicap"], $row["komi"]); },
                  "style=\"text-align:center;\"");

$table->addColumn("game_timestamp",
                  "Time",
                  array(array("game.timestamp", "timestamp")),
                  function($row) { echo date("d. m. Y H:i", strtotime($row["timestamp"])); })
      ->addSecondary(array(array("rating_change.timestamp", "timestamp")));

$table->addColumn("egd_tournament_name",
                  "Tournament",
                  array(array("egd_tournament.name", "egd_tournament_name"),
                        array("egd_tournament.egd_key", "egd_tournament_key"),
                        array("egd_tournament.id", "egd_tournament_id"),
                        array("game.egd_tournament_round", "egd_tournament_round")),
                  function($row)
                  {
                    if (empty($row["egd_tournament_id"]))
                      return;
                    $shortenedTournamentName = substr(readableTournamentName($row["egd_tournament_name"]), 0, 30);
                    echo tournamentLink($row["egd_tournament_id"], $shortenedTournamentName);
                  })
       ->addSecondary(array(array("NULL", "egd_tournament_name"),
                            array("NULL", "egd_tournamekt_key"),
                            array("NULL", "egd_tournament_id"),
                            array("9999", "egd_tournament_round")));

$table->addColumn("game_location",
                  "Location",
                  array(array("game.location", "game_location")),
                  function($row){ echo $row["game_location"]; });

$table->addColumn("comment",
                  "Comment",
                  array(array("IF(winner.id = ".escape($_GET["id"]).", game.winner_comment, game.loser_comment)", "comment")),
                  function($row){ echo $row["comment"]; })
      ->addSecondary(array(array("rating_change.comment", "comment")));

$table->addColumn("opponent_comment",
                  "Opponent comment",
                  array(array("IF(winner.id = ".escape($_GET["id"]).", game.loser_comment, game.winner_comment)", "opponent_comment")),
                  function($row){ echo $row["opponent_comment"]; });

$table->addColumn("has_sgf",
                  "SGF",
                  array(array("length(game.sgf) > 0", "has_sgf"),
                        array("game.id", "game_id")),
                  function($row){ if ($row["has_sgf"]) echo SGFLink($row["game_id"]); });

if (canDeleteAnyGame())
  $table->addColumn("delete",
                    "Delete",
                    array(),
                    function($row) { echo "<button onclick=\"tryToDeleteGame(".$row["game_id"].")\">X</button>"; },
                    "style=\"align:center;\"");


$table->render();
?>
