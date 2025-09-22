<?php
require("src/table_viewer.php");
$player = query("SELECT * from user where id=".escape($_GET["id"]))->fetch_assoc();
if (empty($player))
  die("Unknown player with id=".$_GET["id"]);

echo "<div class=\"centered-div\">";
echo "<h1 style=\"display: inline;\">".$player["first_name"]." ".$player["last_name"]."</h1>";
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
  echo "<div class=\"centered-div\"><a class=\"report-loss-link\" href=\"report?id=".$player["id"]."\">Report loss</a></div>";

echo "<table class=\"centered-table\">";
echo "<tr><td>Rating:</td><td>".round($player["rating"])."</td></tr>";
echo "<tr><td>EGD Rating:</td><td>".$player["egd_rating"]."</td></tr>";
echo "</table>";

$table = new TableViewer("game LEFT JOIN egd_tournament ON game.egd_tournament_id = egd_tournament.id,
                          user as winner,
                          user as loser,
                          game_type
                          WHERE
                            game.winner_user_id = winner.id and
                            game.loser_user_id = loser.id and
                            game.game_type_id = game_type.id and
                            (winner_user_id=".escape($_GET["id"])." or loser_user_id=".escape($_GET["id"]).")",
                          $_GET);

$table->setPrimarySort(new SortDefinition("game.timestamp", false));
$table->setLastSort(new SortDefinition("game.egd_tournament_round", false));
$table->addColumn("result",
                  "Result",
                  array(array("IF(winner.id = ".escape($_GET["id"]).", 'WIN', 'LOSS')", "result")),
                  function($row) { echo $row["result"]; },
                  "style=\"text-align:center;\"");

$table->addColumn("rating_change",
                  "Rating change",
                  array(array("IF(winner.id = ".escape($_GET["id"]).", ".
                                "IF(game.winner_new_rating, game.winner_new_rating, game.winner_new_egd_rating) -".
                                "IF(game.winner_old_rating, game.winner_old_rating, game.winner_old_egd_rating),".
                                "IF(game.loser_new_rating, game.loser_new_rating, game.loser_new_egd_rating) -".
                                "IF(game.loser_old_rating, game.loser_old_rating, game.loser_old_egd_rating))", "rating_change"),
                        array("game.winner_old_rating", "winner_old_rating"),
                        array("game.winner_new_rating", "winner_new_rating"),
                        array("game.winner_old_egd_rating", "winner_old_egd_rating"),
                        array("game.winner_new_egd_rating", "winner_new_egd_rating"),
                        array("game.loser_old_rating", "loser_old_rating"),
                        array("game.loser_new_rating", "loser_new_rating"),
                        array("game.loser_old_egd_rating", "loser_old_egd_rating"),
                        array("game.loser_new_egd_rating", "loser_new_egd_rating")),
                  function($row)
                  {
                    $winner = ($row["result"] == "WIN");
                    $myResultName = $winner ? "winner" : "loser";
                    $suffix = "_rating";
                    if (empty($row[$myResultName."_old_rating"]))
                    {
                      $suffix = "_egd_rating";
                      echo "(EGD) ";
                    }
                    echo   "<span class=\"".$myResultName."\">".round($row[$myResultName."_old".$suffix])."&rarr;".round($row[$myResultName."_new".$suffix])."</span>";
                  },
                  "style=\"text-align:center;\"");

$table->addColumn("opponent_name",
                  "Opponent",
                  array(array("IF(winner.id = ".escape($_GET["id"]).", CONCAT(loser.first_name, ' ', loser.last_name), CONCAT(winner.first_name, ' ', winner.last_name))", "opponent_name"),
                        array("IF(winner.id = ".escape($_GET["id"]).", game.loser_user_id, game.winner_user_id)", "opponent_id"),
                        array("IF(winner.id = ".escape($_GET["id"]).", loser.egd_pin, winner.egd_pin)", "opponent_egd_pin"),
                        array("IF(winner.id = ".escape($_GET["id"]).", game.winner_new_rating, game.loser_new_rating)", "opponent_rating"),
                        array("IF(winner.id = ".escape($_GET["id"]).", game.winner_new_egd_rating, game.loser_new_egd_rating)", "opponent_egd_rating")),
                  function($row)
                  {
                    $ratingToShow = $row["opponent_rating"];
                    if (empty($ratingToShow))
                      $ratingToShow = $row["opponent_egd_rating"];
                    echo playerLink($row["opponent_id"], $row["opponent_name"])." (".round($ratingToShow).")";
                  });

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
                  function($row)
                  {
                    if ($row["handicap"] == 0 and ($row["komi"] == 6.5 or $row["komi"] == 7.5))
                      echo "Even";
                    else
                    {
                      if ($row["handicap"] != 0)
                      {
                        echo $row["handicap"]."h";
                        if ($row["komi"] != 0.5)
                          echo " komi ".$row["komi"];
                      }
                      else
                        echo "komi ".$row["komi"];
                    }
                  },
                  "style=\"text-align:center;\"");

$table->addColumn("game_timestamp",
                  "Time",
                  array(array("game.timestamp", "game_timestamp")),
                  function($row) { echo date("d. m. Y H:i", strtotime($row["game_timestamp"])); });

$table->addColumn("egd_tournament_name",
                  "Tournament",
                  array(array("egd_tournament.name", "egd_tournament_name"),
                        array("egd_tournament.egd_key", "egd_tournament_key"),
                        array("egd_tournament.id", "egd_tournament_id")),
                  function($row)
                  {
                    if (empty($row["egd_tournament_id"]))
                      return;
                    $shortenedTournamentName = substr(readableTournamentName($row["egd_tournament_name"]), 0, 30);
                    echo tournamentLink($row["egd_tournament_id"], $shortenedTournamentName);
                  });

$table->addColumn("game_location",
                  "Location",
                  array(array("game.location", "game_location")),
                  function($row){ echo $row["game_location"]; });

$table->addColumn("comment",
                  "Comment",
                  array(array("IF(winner.id = ".escape($_GET["id"]).", game.winner_comment, game.loser_comment)", "comment")),
                  function($row){ echo $row["comment"]; });

$table->addColumn("opponent_comment",
                  "Opponent comment",
                  array(array("IF(winner.id = ".escape($_GET["id"]).", game.loser_comment, game.winner_comment)", "opponent_comment")),
                  function($row){ echo $row["opponent_comment"]; });

$table->addColumn("has_sgf",
                  "SGF",
                  array(array("length(game.sgf) > 0", "has_sgf"),
                        array("game.id", "game_id")),
                  function($row){ if ($row["has_sgf"]) echo "<a href=\"/sgf?id=".$row["game_id"]."\">SGF</a>"; });

$table->render();
?>
