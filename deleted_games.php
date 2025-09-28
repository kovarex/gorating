<?php
if (!canAccessDeletedGames())
{
  echo "No rights to access deleted games.";
  return;
}

require("src/table_viewer.php");

echo "<script>\n";
echo "function tryToRestoreGame(id)\n";
echo "{\n";
echo "  if (confirm('Are you sure to restore the game?'))\n";
echo "    window.location.replace('/restore_game_action?id=' + id + '&redirect=".urlencode($_SERVER["REQUEST_URI"])."');\n";
echo "}\n";
echo "</script>\n";

$table = new TableViewer("game LEFT JOIN egd_tournament ON game.egd_tournament_id = egd_tournament.id,
                          user as winner,
                          user as loser,
                          game_type
                          WHERE
                            game.deleted = true and
                            game.winner_user_id = winner.id and
                            game.loser_user_id = loser.id and
                            game.game_type_id = game_type.id",
                          $_GET);

$table->setPrimarySort(new SortDefinition("game.timestamp", false));
$table->setLastSort(new SortDefinition("game.egd_tournament_round", false));

$table->addColumn("winner_name",
                  "Winner",
                  array(array("winner.name", "winner_name"),
                        array("winner.username", "winner_username"),
                        array("game.winner_user_id", "winner_id"),
                        array("winner.egd_pin", "winner_egd_pin"),
                        array("game.winner_new_rating", "winner_rating"),
                        array("game.winner_new_egd_rating", "winner_egd_rating")),
                  function($row)
                  {
                    $ratingToShow = $row["winner_rating"];
                    if (empty($ratingToShow))
                      $ratingToShow = $row["winner_egd_rating"];
                    echo playerLink($row, "winner")." (".round($ratingToShow, 1).")";
                  });

$table->addColumn("loser_name",
                  "Loser",
                  array(array("loser.name", "loser_name"),
                        array("loser.username", "loser_username"),
                        array("game.loser_user_id", "loser_id"),
                        array("loser.egd_pin", "loser_egd_pin"),
                        array("game.loser_new_rating", "loser_rating"),
                        array("game.loser_new_egd_rating", "loser_egd_rating")),
                  function($row)
                  {
                    $ratingToShow = $row["loser_rating"];
                    if (empty($ratingToShow))
                      $ratingToShow = $row["loser_egd_rating"];
                    echo playerLink($row, "loser")." (".round($ratingToShow, 1).")";
                  });

$table->addColumn("game_type_name",
                  "Game type",
                  array(array("game_type.name", "game_type_name")),
                  function($row) { echo $row["game_type_name"]; },
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

$table->addColumn("winner_comment",
                  "Winner Comment",
                  array(array("game.winner_comment", "winner_comment")),
                  function($row){ echo $row["winner_comment"]; });

$table->addColumn("loser_comment",
                  "Loser Comment",
                  array(array("game.loser_comment", "loser_comment")),
                  function($row){ echo $row["loser_comment"]; });

$table->addColumn("has_sgf",
                  "SGF",
                  array(array("length(game.sgf) > 0", "has_sgf"),
                        array("game.id", "game_id")),
                  function($row){ if ($row["has_sgf"]) echo "<a href=\"/sgf?id=".$row["game_id"]."\">SGF</a>"; });

if (canDeleteAnyGame())
  $table->addColumn("restore",
                    "Restore",
                    array(),
                    function($row) { echo "<button onclick=\"tryToRestoreGame(".$row["game_id"].")\">Restore</button>"; });

$table->render();
?>
